<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/KegiatanController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Perjadin\Kegiatan;
use App\Models\Perjadin\KegiatanBidang;
use App\Services\KegiatanService;
use Illuminate\Http\Request;
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
        $kegiatan = Kegiatan::with('details.bidang')
            ->find($id);

        if (!$kegiatan) {
            return response()->json(['success' => false, 'message' => 'Kegiatan tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $kegiatan]);
    }

    public function store(Request $request)
    {
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
            return response()->json(['success' => true, 'message' => 'Kegiatan berhasil ditambahkan.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan kegiatan. Error: ' . $e->getMessage()], 500);
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
            return response()->json(['success' => true, 'message' => 'Kegiatan berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui kegiatan. Error: ' . $e->getMessage()], 500);
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
    
    /**
     * 📊 Enhanced Excel export with modern styling and filtering
     */
    public function exportExcel(Request $request)
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

            // Create new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('DPMD System')
                ->setTitle('Data Kegiatan Perjalanan Dinas')
                ->setSubject('Export Data Kegiatan')
                ->setDescription('Data kegiatan perjalanan dinas yang diekspor dari sistem DPMD');

            // Header styling
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e293b']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'e2e8f0']
                    ]
                ]
            ];

            // Data styling
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'e2e8f0']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ];

            // Title
            $sheet->setCellValue('A1', 'DATA KEGIATAN PERJALANAN DINAS');
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '1e293b']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Date info
            $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d F Y H:i'));
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 10, 'color' => ['rgb' => '64748b']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);

            // Headers
            $headers = [
                'A4' => 'No',
                'B4' => 'Nomor SP',
                'C4' => 'Nama Kegiatan',
                'D4' => 'Lokasi',
                'E4' => 'Tanggal Mulai',
                'F4' => 'Tanggal Selesai',
                'G4' => 'Jumlah Personil',
                'H4' => 'Bidang Terlibat',
                'I4' => 'Keterangan'
            ];

            foreach ($headers as $cell => $header) {
                $sheet->setCellValue($cell, $header);
            }

            $sheet->getStyle('A4:I4')->applyFromArray($headerStyle);

            // Data rows
            $row = 5;
            foreach ($kegiatan as $index => $item) {
                $personilCount = $item->details->sum(function($detail) {
                    return count(array_filter(explode(', ', $detail->personil ?? '')));
                });

                $bidangList = $item->details->pluck('bidang.nama')->filter()->join(', ');

                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $item->nomor_sp ?? '-');
                $sheet->setCellValue('C' . $row, $item->nama_kegiatan ?? '-');
                $sheet->setCellValue('D' . $row, $item->lokasi ?? '-');
                $sheet->setCellValue('E' . $row, $item->tanggal_mulai ? date('d/m/Y', strtotime($item->tanggal_mulai)) : '-');
                $sheet->setCellValue('F' . $row, $item->tanggal_selesai ? date('d/m/Y', strtotime($item->tanggal_selesai)) : '-');
                $sheet->setCellValue('G' . $row, $personilCount);
                $sheet->setCellValue('H' . $row, $bidangList ?: '-');
                $sheet->setCellValue('I' . $row, $item->keterangan ?? '-');

                // Apply alternating row colors
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'f8fafc']
                        ]
                    ]);
                }

                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataStyle);
                $row++;
            }

            // Column widths
            $sheet->getColumnDimension('A')->setWidth(8);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(35);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(25);
            $sheet->getColumnDimension('I')->setWidth(30);

            // Row heights
            $sheet->getRowDimension(1)->setRowHeight(30);
            $sheet->getRowDimension(4)->setRowHeight(25);
            for ($i = 5; $i < $row; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(20);
            }

            // Generate filename
            $filename = 'kegiatan-perjadin-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
            
            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ], 500);
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