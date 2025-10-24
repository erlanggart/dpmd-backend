<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/KegiatanController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Perjadin\Kegiatan;
use App\Models\Perjadin\KegiatanBidang;
use App\Services\KegiatanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KegiatanController extends Controller
{
    // protected $kegiatanService;

    // public function __construct(KegiatanService $kegiatanService)
    // {
    //     $this->kegiatanService = $kegiatanService;
    // }

    public function index(Request $request)
    {
        $query = Kegiatan::with('details.bidang')
            ->latest('tanggal_mulai');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('nama_kegiatan', 'like', $search)
                  ->orWhere('nomor_sp', 'like', $search)
                  ->orWhere('lokasi', 'like', $search)
                  ->orWhere('keterangan', 'like', $search);
            })->orWhereHas('details', function ($q) use ($search) {
                $q->where('personil', 'like', $search);
            });
        }
        
        if ($request->filled('id_bidang')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('id_bidang', $request->id_bidang);
            });
        }
        
        if ($request->date_filter === 'mingguan') {
            $query->whereBetween('tanggal_mulai', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        if ($request->date_filter === 'bulanan') {
            $query->whereBetween('tanggal_mulai', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $kegiatan = $query->paginate($request->limit ?? 5);

        return response()->json([
            'success' => true,
            'data' => $kegiatan->items(),
            'total' => $kegiatan->total(),
            'current_page' => $kegiatan->currentPage(),
            'last_page' => $kegiatan->lastPage(),
        ]);
    }

    public function show($id)
    {
        try {
            $kegiatan = Kegiatan::with(['details.bidang'])
                ->find($id);

            if (!$kegiatan) {
                return response()->json(['success' => false, 'message' => 'Kegiatan tidak ditemukan.'], 404);
            }



            // Format data untuk frontend
            $formattedKegiatan = [
                'id_kegiatan' => $kegiatan->id_kegiatan,
                'nama_kegiatan' => $kegiatan->nama_kegiatan,
                'nomor_surat' => $kegiatan->nomor_sp,
                'tanggal_kegiatan' => $kegiatan->tanggal_mulai . ' - ' . $kegiatan->tanggal_selesai,
                'tanggal_mulai' => $kegiatan->tanggal_mulai,
                'tanggal_selesai' => $kegiatan->tanggal_selesai,
                'lokasi' => $kegiatan->lokasi,
                'keterangan' => $kegiatan->keterangan,
                'status' => 'approved',
                'bidang' => [],
                'personil' => []
            ];

            // Format bidang dan personil
            $addedBidangs = []; // Track added bidangs to avoid duplicates
            
            if ($kegiatan->details) {
                foreach ($kegiatan->details as $detail) {
                    // Add bidang info (avoid duplicates)
                    if ($detail->bidang && !in_array($detail->bidang->id, $addedBidangs)) {
                        $formattedKegiatan['bidang'][] = [
                            'id_bidang' => $detail->bidang->id ?? null,
                            'nama_bidang' => $detail->bidang->nama ?? 'Tidak diketahui',
                            'status' => 'aktif'
                        ];
                        $addedBidangs[] = $detail->bidang->id;
                    }

                    // Add personil info - handle both comma-separated and JSON formats
                    if (!empty($detail->personil)) {

                        
                        $personilData = null;
                        
                        // Check if it's JSON string
                        if (is_string($detail->personil)) {
                            $jsonDecoded = json_decode($detail->personil, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                                // It's valid JSON
                                $personilData = $jsonDecoded;
                            } else {
                                // Handle comma-separated personil data intelligently
                                // Use regex to find patterns of "Name, Degree" and keep them together
                                $personilString = $detail->personil;
                                
                                // First, try to identify complete name-degree patterns
                                // Pattern: Any text followed by comma and space, then a degree pattern
                                $degreePatterns = [
                                    'S\.H\.', 'S\.E\.', 'M\.M', 'M\.Si', 'S\.STP', 'M\.IP', 'S\.AP', 
                                    'A\.Md', 'S\.AK', 'S\.Psi', 'S\.I\.Kom', 'S\.Sos', 'MM', 'S\.Pd', 'S\.Kom',
                                    'S\.P\.W\.K', 'S\.Tr\.I\.P\.', 'S\.Tr\.Sos', 'S\.M\.', 'S\.T\.', 'S\.IK',
                                    'M\.PA', 'M\.A', 'S\.Ak\.', 'B\.M\.', 'M\.IP', 'S\.Tr\.', 'Drs\.',
                                    'S\. E\.', 'S\. IP', 'S\. AP', 'S\. Md', 'S\. AK', 'S\. STP', 'S\. Ak',
                                    'M\. Si', 'M\. M\.', 'M\. PA', 'S\. Kom', 'S\. Pd'
                                ];
                                
                                $processedNames = [];
                                
                                // Replace pattern "Name, Degree" with "Name|Degree" temporarily to preserve
                                foreach ($degreePatterns as $degree) {
                                    $personilString = preg_replace('/([^,]+),\s*(' . $degree . ')/', '$1|$2', $personilString);
                                }
                                
                                // Now split by comma to get individual entries
                                $rawNames = array_map('trim', explode(',', $personilString));
                                
                                foreach ($rawNames as $name) {
                                    $name = trim($name);
                                    if (!empty($name)) {
                                        // Restore the comma in degree if it was replaced
                                        $name = str_replace('|', ', ', $name);
                                        $processedNames[] = $name;
                                    }
                                }
                                
                                $personilData = array_map(function($name) {
                                    return ['nama' => trim($name)];
                                }, $processedNames);
                            }
                        } elseif (is_array($detail->personil)) {
                            $personilData = $detail->personil;
                        }
                        
                        if (is_array($personilData) && !empty($personilData)) {
                            foreach ($personilData as $person) {
                                if (is_array($person)) {
                                    // Ensure the full name with degrees/titles is preserved
                                    $namaLengkap = $person['nama'] ?? 'Tidak diketahui';
                                    $formattedKegiatan['personil'][] = [
                                        'nama' => $namaLengkap,
                                        'bidang' => $detail->bidang ? $detail->bidang->nama : 'Tidak diketahui'
                                    ];
                                } elseif (is_string($person)) {
                                    // Handle simple string names - preserve full name with degrees/titles
                                    $formattedKegiatan['personil'][] = [
                                        'nama' => trim($person),
                                        'bidang' => $detail->bidang ? $detail->bidang->nama : 'Tidak diketahui'
                                    ];
                                }
                            }
                        }
                    }
                }
            }



            return response()->json(['success' => true, 'data' => $formattedKegiatan]);
            
        } catch (\Exception $e) {
            Log::error('Error in KegiatanController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat mengambil data kegiatan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Debug: Log incoming data
        Log::info('KegiatanController@store - Incoming data:', $request->all());
        
        // Validation
        try {
            $request->validate([
                'nama_kegiatan' => 'required|string|max:255',
                'nomor_sp' => 'required|string|max:100',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
                'lokasi' => 'required|string|max:255',
                'keterangan' => 'nullable|string',
                'personil_bidang_list' => 'required|array|min:1',
                'personil_bidang_list.*.id_bidang' => 'required|integer',
                'personil_bidang_list.*.personil' => 'required|array|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('KegiatanController@store - Validation errors:', $e->errors());
            return response()->json([
                'status' => 'error', 
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        }

        // $conflictMessage = $this->kegiatanService->checkPersonilConflict(
        //     $request->personil_bidang_list,
        //     $request->tanggal_mulai,
        //     $request->tanggal_selesai
        // );

        // if ($conflictMessage) {
        //     return response()->json(['status' => 'error', 'message' => $conflictMessage], 409);
        // }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::create($request->all());

            foreach ($request->personil_bidang_list as $item) {
                if (!empty($item['personil'])) {
                    KegiatanBidang::create([
                        'id_kegiatan' => $kegiatan->id_kegiatan,
                        'id_bidang' => $item['id_bidang'],
                        'personil' => implode(', ', $item['personil']),
                    ]);
                }
            }
            DB::commit();
            
            // Load kegiatan with details for response
            $kegiatan->load('details.bidang');
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Kegiatan berhasil ditambahkan.', 
                'data' => $kegiatan
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            Log::error('Database error in KegiatanController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal menyimpan data ke database. Silakan coba lagi.'
            ], 500);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('General error in KegiatanController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', 
                'message' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // $conflictMessage = $this->kegiatanService->checkPersonilConflict(
        //     $request->personil_bidang_list,
        //     $request->tanggal_mulai,
        //     $request->tanggal_selesai,
        //     $id
        // );

        // if ($conflictMessage) {
        //     return response()->json(['status' => 'error', 'message' => $conflictMessage], 409);
        // }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::findOrFail($id);
            $kegiatan->update($request->all());
            $kegiatan->details()->delete();

            foreach ($request->personil_bidang_list as $item) {
                if (!empty($item['personil'])) {
                    KegiatanBidang::create([
                        'id_kegiatan' => $kegiatan->id_kegiatan,
                        'id_bidang' => $item['id_bidang'],
                        'personil' => implode(', ', $item['personil']),
                    ]);
                }
            }

            DB::commit();
            
            // Load kegiatan with details for response
            $kegiatan->load('details.bidang');
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Kegiatan berhasil diperbarui.', 
                'data' => $kegiatan
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            Log::error('Database error in KegiatanController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal memperbarui data di database. Silakan coba lagi.'
            ], 500);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('General error in KegiatanController@update: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', 
                'message' => 'Terjadi kesalahan sistem. Silakan hubungi administrator.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::findOrFail($id);
            $kegiatan->details()->delete();
            $kegiatan->delete();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Kegiatan berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus kegiatan. Error: ' . $e->getMessage()]);
        }
    }
    


    public function checkPersonnelConflict(Request $request)
    {
        $personnelName = $request->query('personnel_name');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $excludeId = $request->query('exclude_id');

        if (!$personnelName || !$startDate || !$endDate) {
            return response()->json([
                'conflicts' => [],
                'message' => 'Parameter tidak lengkap'
            ], 400);
        }

        try {
            $query = Kegiatan::with('details.bidang')
                ->whereHas('details', function ($q) use ($personnelName) {
                    $q->where('personil', 'like', '%' . $personnelName . '%');
                })
                ->where(function ($q) use ($startDate, $endDate) {
                    // Cek overlap tanggal
                    $q->where(function ($subQuery) use ($startDate, $endDate) {
                        // Kegiatan yang dimulai di antara periode yang akan dijadwalkan
                        $subQuery->whereBetween('tanggal_mulai', [$startDate, $endDate])
                            // Kegiatan yang berakhir di antara periode yang akan dijadwalkan
                            ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                            // Kegiatan yang mencakup seluruh periode yang akan dijadwalkan
                            ->orWhere(function ($innerQuery) use ($startDate, $endDate) {
                                $innerQuery->where('tanggal_mulai', '<=', $startDate)
                                          ->where('tanggal_selesai', '>=', $endDate);
                            });
                    });
                });

            // Exclude kegiatan yang sedang di-edit (untuk update)
            if ($excludeId) {
                $query->where('id_kegiatan', '!=', $excludeId);
            }

            $conflicts = $query->get()->map(function ($kegiatan) {
                return [
                    'id_kegiatan' => $kegiatan->id_kegiatan,
                    'nama_kegiatan' => $kegiatan->nama_kegiatan,
                    'nomor_sp' => $kegiatan->nomor_sp,
                    'tanggal_mulai' => $kegiatan->tanggal_mulai,
                    'tanggal_selesai' => $kegiatan->tanggal_selesai,
                    'lokasi' => $kegiatan->lokasi,
                ];
            });

            return response()->json([
                'conflicts' => $conflicts,
                'total_conflicts' => $conflicts->count(),
                'message' => $conflicts->count() > 0 ? 'Ditemukan konflik jadwal' : 'Tidak ada konflik jadwal'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'conflicts' => [],
                'message' => 'Gagal mengecek konflik: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * 📄 Get formatted data for PDF export (frontend will handle PDF generation)
     */
    public function exportData(Request $request)
    {
        try {
            // Get filtered data
            $query = Kegiatan::with('details.bidang')
                ->latest('tanggal_mulai');

            if ($request->filled('search')) {
                $search = '%' . $request->search . '%';
                $query->where(function($q) use ($search) {
                    $q->where('nama_kegiatan', 'like', $search)
                      ->orWhere('nomor_sp', 'like', $search)
                      ->orWhere('lokasi', 'like', $search)
                      ->orWhere('keterangan', 'like', $search);
                })->orWhereHas('details', function ($q) use ($search) {
                    $q->where('personil', 'like', $search);
                });
            }
            
            if ($request->filled('bidang')) {
                $query->whereHas('details', function ($q) use ($request) {
                    $q->where('id_bidang', $request->bidang);
                });
            }

            $kegiatan = $query->get();

            // Format data for export
            $formattedData = $kegiatan->map(function($item) {
                $personilCount = $item->details->sum(function($detail) {
                    return count(array_filter(explode(', ', $detail->personil ?? '')));
                });

                $bidangList = $item->details->pluck('bidang.nama')->filter()->join(', ');

                return [
                    'id' => $item->id,
                    'nomor_sp' => $item->nomor_sp ?? '-',
                    'nama_kegiatan' => $item->nama_kegiatan ?? '-',
                    'lokasi' => $item->lokasi ?? '-',
                    'tanggal_mulai' => $item->tanggal_mulai,
                    'tanggal_selesai' => $item->tanggal_selesai,
                    'personil_count' => $personilCount,
                    'bidang_list' => $bidangList ?: '-',
                    'keterangan' => $item->keterangan ?? '-',
                    'details' => $item->details
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'total' => $formattedData->count(),
                'exported_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data untuk export: ' . $e->getMessage()
            ], 500);
        }
    }
}