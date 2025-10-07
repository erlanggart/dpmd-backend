<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bumdes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BumdesController extends Controller
{
    /**
     * Helper function untuk mengunggah berkas dan menghapus berkas lama.
     *
     * @param Request $request
     * @param string $fileKey
     * @param string|null $currentFilePath
     * @return string|null
     */
    private function uploadFile(Request $request, string $fileKey, ?string $currentFilePath = null): ?string
    {
        if ($request->hasFile($fileKey)) {
            // Hapus berkas lama jika ada
            if ($currentFilePath && Storage::disk('public')->exists($currentFilePath)) {
                Storage::disk('public')->delete($currentFilePath);
            }
            // Simpan berkas baru di folder 'bumdes_files'
            return $request->file($fileKey)->store('bumdes_files', 'public');
        }
        return $currentFilePath;
    }

    /**
     * Mengambil semua data BUMDes dan mengemasnya dalam format yang benar untuk frontend.
     */
    public function index()
    {
        $bumdes = Bumdes::all();
        // PERBAIKAN: Mengemas data dalam kunci 'data'
        return response()->json(['data' => $bumdes]); 
    }

    /**
     * Menyimpan data BUMDes baru.
     */
    public function store(Request $request)
    {
        try {
            // Cek apakah desa sudah punya BUMDes
            $existingBumdes = Bumdes::where('kode_desa', $request->kode_desa)->first();
            if ($existingBumdes) {
                return response()->json([
                    'message' => 'Desa ini sudah memiliki data BUMDes. Setiap desa hanya dapat memiliki satu BUMDes.',
                    'errors' => [
                        'kode_desa' => ['Desa ini sudah memiliki data BUMDes.']
                    ]
                ], 422);
            }

            // Validasi data masukan dengan pesan error custom
            $validatedData = $request->validate([
                'kode_desa' => ['required', 'string'],
                'kecamatan' => 'required|string',
                'desa' => 'required|string',
                'namabumdesa' => 'required|string',
                'status' => 'required|string',
                'keterangan_tidak_aktif' => 'nullable|string',
                'NIB' => 'nullable|string',
                'LKPP' => 'nullable|string',
                'NPWP' => 'nullable|string',
                'badanhukum' => 'nullable|string',
                'NamaPenasihat' => 'nullable|string',
                'JenisKelaminPenasihat' => 'nullable|string',
                'HPPenasihat' => 'nullable|string',
                'NamaPengawas' => 'nullable|string',
                'JenisKelaminPengawas' => 'nullable|string',
                'HPPengawas' => 'nullable|string',
                'NamaDirektur' => 'nullable|string',
                'JenisKelaminDirektur' => 'nullable|string',
                'HPDirektur' => 'nullable|string',
                'NamaSekretaris' => 'nullable|string',
                'JenisKelaminSekretaris' => 'nullable|string',
                'HPSekretaris' => 'nullable|string',
                'NamaBendahara' => 'nullable|string',
                'JenisKelaminBendahara' => 'nullable|string',
                'HPBendahara' => 'nullable|string',
                'TahunPendirian' => 'nullable|string',
                'AlamatBumdesa' => 'nullable|string',
                'Alamatemail' => 'nullable|string|email',
                'TotalTenagaKerja' => 'nullable|numeric',
                'TelfonBumdes' => 'nullable|string',
                'JenisUsaha' => 'nullable|string',
                'JenisUsahaUtama' => 'nullable|string',
                'JenisUsahaLainnya' => 'nullable|string',
                'Omset2023' => 'nullable|numeric',
                'Laba2023' => 'nullable|numeric',
                'Omset2024' => 'nullable|numeric',
                'Laba2024' => 'nullable|numeric',
                'PenyertaanModal2019' => 'nullable|numeric',
                'PenyertaanModal2020' => 'nullable|numeric',
                'PenyertaanModal2021' => 'nullable|numeric',
                'PenyertaanModal2022' => 'nullable|numeric',
                'PenyertaanModal2023' => 'nullable|numeric',
                'PenyertaanModal2024' => 'nullable|numeric',
                'SumberLain' => 'nullable|numeric',
                'JenisAset' => 'nullable|string',
                'NilaiAset' => 'nullable|numeric',
                'KerjasamaPihakKetiga' => 'nullable|string',
                'TahunMulai-TahunBerakhir' => 'nullable|string',
                'KontribusiTerhadapPADes2021' => 'nullable|numeric',
                'KontribusiTerhadapPADes2022' => 'nullable|numeric',
                'KontribusiTerhadapPADes2023' => 'nullable|numeric',
                'KontribusiTerhadapPADes2024' => 'nullable|numeric',
                'Ketapang2024' => 'nullable|string',
                'Ketapang2025' => 'nullable|string',
                'BantuanKementrian' => 'nullable|string',
                'BantuanLaptopShopee' => 'nullable|string',
                'NomorPerdes' => 'nullable|string',
                'DesaWisata' => 'nullable|string',
                'LaporanKeuangan2021' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2022' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2023' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2024' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'Perdes' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'ProfilBUMDesa' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'BeritaAcara' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'AnggaranDasar' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'AnggaranRumahTangga' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'ProgramKerja' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'SK_BUM_Desa' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
            ]);

            $bumdes = Bumdes::create($validatedData);

            $fileFields = [
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
                'ProgramKerja', 'SK_BUM_Desa'
            ];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $path = $this->uploadFile($request, $field);
                    if ($path) {
                        $bumdes->$field = $path;
                    }
                }
            }
            $bumdes->save();

            return response()->json(['message' => 'Data BUMDes berhasil disimpan.', 'data' => $bumdes], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error during BUMDes store: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan data BUMDes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan data BUMDes tunggal.
     */
    public function show(Bumdes $bumdes)
    {
        return response()->json($bumdes);
    }

    /**
     * Memperbarui data BUMDes yang ada.
     * Menggunakan metode PUT.
     */
    public function update(Request $request, Bumdes $bumdes)
    {
        try {
            // Custom validation untuk kode_desa
            $kode_desa_baru = $request->input('kode_desa');
            if ($kode_desa_baru && $kode_desa_baru !== $bumdes->kode_desa) {
                $existing = Bumdes::where('kode_desa', $kode_desa_baru)
                                  ->where('id', '!=', $bumdes->id)
                                  ->first();
                if ($existing) {
                    return response()->json([
                        'message' => 'Validasi gagal.',
                        'errors' => [
                            'kode_desa' => ['Kode desa sudah digunakan oleh BUMDes lain.']
                        ]
                    ], 422);
                }
            }
            
            // Validasi data masukan
            $validatedData = $request->validate([
                'kode_desa' => ['nullable', 'string'],
                'kecamatan' => 'nullable|string',
                'desa' => 'nullable|string',
                'namabumdesa' => 'nullable|string',
                'status' => 'nullable|string',
                'keterangan_tidak_aktif' => 'nullable|string',
                'NIB' => 'nullable|string',
                'LKPP' => 'nullable|string',
                'NPWP' => 'nullable|string',
                'badanhukum' => 'nullable|string',
                'NamaPenasihat' => 'nullable|string',
                'JenisKelaminPenasihat' => 'nullable|string',
                'HPPenasihat' => 'nullable|string',
                'NamaPengawas' => 'nullable|string',
                'JenisKelaminPengawas' => 'nullable|string',
                'HPPengawas' => 'nullable|string',
                'NamaDirektur' => 'nullable|string',
                'JenisKelaminDirektur' => 'nullable|string',
                'HPDirektur' => 'nullable|string',
                'NamaSekretaris' => 'nullable|string',
                'JenisKelaminSekretaris' => 'nullable|string',
                'HPSekretaris' => 'nullable|string',
                'NamaBendahara' => 'nullable|string',
                'JenisKelaminBendahara' => 'nullable|string',
                'HPBendahara' => 'nullable|string',
                'TahunPendirian' => 'nullable|string',
                'AlamatBumdesa' => 'nullable|string',
                'Alamatemail' => 'nullable|string|email',
                'TotalTenagaKerja' => 'nullable|numeric',
                'TelfonBumdes' => 'nullable|string',
                'JenisUsaha' => 'nullable|string',
                'JenisUsahaUtama' => 'nullable|string',
                'JenisUsahaLainnya' => 'nullable|string',
                'Omset2023' => 'nullable|numeric',
                'Laba2023' => 'nullable|numeric',
                'Omset2024' => 'nullable|numeric',
                'Laba2024' => 'nullable|numeric',
                'PenyertaanModal2019' => 'nullable|numeric',
                'PenyertaanModal2020' => 'nullable|numeric',
                'PenyertaanModal2021' => 'nullable|numeric',
                'PenyertaanModal2022' => 'nullable|numeric',
                'PenyertaanModal2023' => 'nullable|numeric',
                'PenyertaanModal2024' => 'nullable|numeric',
                'SumberLain' => 'nullable|numeric',
                'JenisAset' => 'nullable|string',
                'NilaiAset' => 'nullable|numeric',
                'KerjasamaPihakKetiga' => 'nullable|string',
                'TahunMulai-TahunBerakhir' => 'nullable|string',
                'KontribusiTerhadapPADes2021' => 'nullable|numeric',
                'KontribusiTerhadapPADes2022' => 'nullable|numeric',
                'KontribusiTerhadapPADes2023' => 'nullable|numeric',
                'KontribusiTerhadapPADes2024' => 'nullable|numeric',
                'Ketapang2024' => 'nullable|string',
                'Ketapang2025' => 'nullable|string',
                'BantuanKementrian' => 'nullable|string',
                'BantuanLaptopShopee' => 'nullable|string',
                'NomorPerdes' => 'nullable|string',
                'DesaWisata' => 'nullable|string',
                'LaporanKeuangan2021' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2022' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2023' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'LaporanKeuangan2024' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'Perdes' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'ProfilBUMDesa' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'BeritaAcara' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'AnggaranDasar' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'AnggaranRumahTangga' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'ProgramKerja' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
                'SK_BUM_Desa' => 'nullable|file|mimes:pdf,docx,doc|max:5120',
            ]);

            // Fill the model with validated data (non-file fields)
            $bumdes->fill($validatedData);

            $fileFields = [
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
                'ProgramKerja', 'SK_BUM_Desa'
            ];
            
            // Proses unggahan berkas satu per satu
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $path = $this->uploadFile($request, $field, $bumdes->$field);
                    $bumdes->$field = $path;
                }
            }
            
            // Simpan perubahan jika ada
            $bumdes->save();

            // Muat ulang model untuk mendapatkan data terbaru dan kirimkan sebagai respons
            return response()->json(['message' => 'Data BUMDes berhasil diperbarui.', 'data' => $bumdes->fresh()]);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error during BUMDes update: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus data BUMDes dan berkas terkait.
     */
    public function destroy($id)
    {
        try {
            Log::info('Destroy method called for BUMDes ID: ' . $id);
            
            // Find the BUMDes record
            $bumdes = Bumdes::findOrFail($id);
            Log::info('Found BUMDes: ' . $bumdes->namabumdesa . ' (ID: ' . $bumdes->id . ')');
            
            $fileFields = [
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
                'ProgramKerja', 'SK_BUM_Desa'
            ];
            
            // Delete associated files
            foreach ($fileFields as $field) {
                if ($bumdes->$field && Storage::disk('public')->exists($bumdes->$field)) {
                    Storage::disk('public')->delete($bumdes->$field);
                    Log::info("Deleted file: {$bumdes->$field}");
                }
            }
            
            // Delete the record
            $deleted = $bumdes->delete();
            Log::info('Delete result: ' . ($deleted ? 'success' : 'failed'));

            if ($deleted) {
                return response()->json([
                    'message' => 'Data BUMDes berhasil dihapus.',
                    'success' => true
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Gagal menghapus data BUMDes.',
                    'success' => false
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error deleting BUMDes: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    
    /**
     * Get BUMDes statistics
     */
    public function statistics()
    {
        try {
            $total = Bumdes::count();
            $aktif = Bumdes::where('status', 'like', '%aktif%')->count();
            $tidakAktif = Bumdes::where('status', 'like', '%tidak aktif%')->count();
            
            // Count by badanhukum status
            $terbitSertifikat = Bumdes::where('badanhukum', 'like', '%Terbit Sertifikat Badan Hukum%')->count();
            $namaTermerifikasi = Bumdes::where('badanhukum', 'like', '%Nama Terverifikasi%')->count();
            $perbaikanDokumen = Bumdes::where('badanhukum', 'like', '%Perbaikan Dokumen%')->count();
            $belumProses = Bumdes::where('badanhukum', 'like', '%Belum Melakukan Proses%')
                ->orWhere('badanhukum', '')
                ->orWhereNull('badanhukum')
                ->count();
            
            // Calculate percentages based on target 416 BUMDes
            $targetTotal = 416;
            $percentageAktif = $targetTotal > 0 ? round(($aktif / $targetTotal) * 100, 1) : 0;
            $percentageSertifikat = $targetTotal > 0 ? round(($terbitSertifikat / $targetTotal) * 100, 1) : 0;
            
            // Statistics for Usaha Utama (Main Business Types)
            $usahaUtamaStats = Bumdes::select('JenisUsahaUtama')
                ->whereNotNull('JenisUsahaUtama')
                ->where('JenisUsahaUtama', '!=', '')
                ->groupBy('JenisUsahaUtama')
                ->selectRaw('JenisUsahaUtama as type, COUNT(*) as count')
                ->orderByDesc('count')
                ->get()
                ->map(function($item) {
                    return [
                        'type' => $item->type ?: 'Tidak Disebutkan',
                        'count' => $item->count
                    ];
                });

            // Statistics for Ketahanan Pangan (Food Security Business)
            $panganKeywords = [
                'pertanian', 'pangan', 'makanan', 'warung', 'toko', 'mart', 'sembako', 
                'beras', 'sayur', 'buah', 'ternak', 'ikan', 'perikanan', 'dagang'
            ];
            
            $ketahananPanganQuery = Bumdes::query();
            foreach ($panganKeywords as $keyword) {
                $ketahananPanganQuery->orWhere('JenisUsaha', 'like', "%{$keyword}%")
                    ->orWhere('JenisUsahaUtama', 'like', "%{$keyword}%")
                    ->orWhere('JenisUsahaLainnya', 'like', "%{$keyword}%");
            }
            
            $ketahananPanganTotal = $ketahananPanganQuery->count();
            
            // Categories for food security businesses
            $ketahananPanganCategories = [
                ['type' => 'Perdagangan/Toko', 'keywords' => ['toko', 'warung', 'mart', 'sembako', 'dagang']],
                ['type' => 'Pertanian/Sayur', 'keywords' => ['pertanian', 'sayur', 'buah', 'beras']],
                ['type' => 'Peternakan/Perikanan', 'keywords' => ['ternak', 'ikan', 'perikanan']]
            ];
            
            $ketahananPanganCategoryStats = [];
            foreach ($ketahananPanganCategories as $category) {
                $categoryQuery = Bumdes::query();
                foreach ($category['keywords'] as $keyword) {
                    $categoryQuery->orWhere('JenisUsaha', 'like', "%{$keyword}%")
                        ->orWhere('JenisUsahaUtama', 'like', "%{$keyword}%")
                        ->orWhere('JenisUsahaLainnya', 'like', "%{$keyword}%");
                }
                $count = $categoryQuery->count();
                if ($count > 0) {
                    $ketahananPanganCategoryStats[] = [
                        'type' => $category['type'],
                        'count' => $count
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'target_total' => $targetTotal,
                    'aktif' => $aktif,
                    'tidak_aktif' => $tidakAktif,
                    'terbit_sertifikat' => $terbitSertifikat,
                    'nama_terverifikasi' => $namaTermerifikasi,
                    'perbaikan_dokumen' => $perbaikanDokumen,
                    'belum_proses' => $belumProses,
                    'percentage_aktif' => $percentageAktif,
                    'percentage_sertifikat' => $percentageSertifikat,
                    'progress_to_target' => [
                        'current' => $total,
                        'target' => $targetTotal,
                        'remaining' => $targetTotal - $total,
                        'percentage' => round(($total / $targetTotal) * 100, 1)
                    ],
                    'usaha_utama_stats' => $usahaUtamaStats,
                    'ketahanan_pangan_stats' => [
                        'total' => $ketahananPanganTotal,
                        'categories' => $ketahananPanganCategoryStats
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting BUMDes statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mencari data BUMDes berdasarkan nama atau desa.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $bumdes = Bumdes::where('namabumdesa', 'like', "%{$query}%")
                            ->orWhere('desa', 'like', "%{$query}%")
                            ->get();
        // PERBAIKAN: Menambahkan format 'data' agar konsisten
        return response()->json(['data' => $bumdes]); 
    }

    /**
     * Check if a desa already has BUMDes by kode_desa
     */
    public function checkByKodeDesa($kode_desa)
    {
        $bumdes = Bumdes::where('kode_desa', $kode_desa)->first();
        
        return response()->json([
            'exists' => $bumdes ? true : false,
            'data' => $bumdes ? [
                'id' => $bumdes->id,
                'namabumdesa' => $bumdes->namabumdesa,
                'desa' => $bumdes->desa,
                'kecamatan' => $bumdes->kecamatan,
                'status' => $bumdes->status
            ] : null
        ]);
    }
    
    /**
     * Mengotentikasi pengguna berdasarkan nama desa.
     * Disesuaikan dengan rute POST ke '/api/login/desa'.
     */
    public function loginByDesa(Request $request)
    {
        $validated = $request->validate(['desa' => 'required|string']);
        $bumdes = Bumdes::where('desa', $validated['desa'])->first();

        if ($bumdes) {
            return response()->json($bumdes);
        } else {
            return response()->json(['message' => 'Nama desa tidak ditemukan.'], 404);
        }
    }

    /**
     * Get dokumen badan hukum files from storage
     */
    public function getDokumenBadanHukum()
    {
        try {
            $documents = [];
            
            // Get all BUMDes with their document fields
            $documentColumns = [
                'LaporanKeuangan2021' => 'Laporan Keuangan 2021',
                'LaporanKeuangan2022' => 'Laporan Keuangan 2022', 
                'LaporanKeuangan2023' => 'Laporan Keuangan 2023',
                'LaporanKeuangan2024' => 'Laporan Keuangan 2024',
                'Perdes' => 'Peraturan Desa',
                'ProfilBUMDesa' => 'Profil BUMDes',
                'BeritaAcara' => 'Berita Acara',
                'AnggaranDasar' => 'Anggaran Dasar',
                'AnggaranRumahTangga' => 'Anggaran Rumah Tangga',
                'ProgramKerja' => 'Program Kerja',
                'SK_BUM_Desa' => 'SK BUMDes'
            ];
            
            $bumdesList = Bumdes::all();
            
            foreach ($bumdesList as $bumdes) {
                foreach ($documentColumns as $column => $columnLabel) {
                    if (!empty($bumdes->$column)) {
                        $filePath = $bumdes->$column;
                        $filename = basename($filePath);
                        
                        // Check if file exists in storage
                        $fileExists = Storage::disk('public')->exists($filePath);
                        $fileSize = 0;
                        $lastModified = time();
                        
                        if ($fileExists) {
                            try {
                                $fileSize = Storage::disk('public')->size($filePath);
                                $lastModified = Storage::disk('public')->lastModified($filePath);
                            } catch (\Exception $e) {
                                // File exists but may have permission issues
                                $fileExists = false;
                            }
                        }
                        
                        $document = [
                            'filename' => $filename,
                            'original_path' => $filePath,
                            'document_type' => $column,
                            'document_label' => $columnLabel,
                            'size' => $fileSize,
                            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                            'last_modified' => $lastModified,
                            'url' => '/storage/' . $filePath,
                            'file_exists' => $fileExists,
                            'status' => $fileExists ? 'available' : 'missing',
                            'bumdes_info' => [
                                'id' => $bumdes->id,
                                'namabumdesa' => $bumdes->namabumdesa,
                                'desa' => $bumdes->desa,
                                'kecamatan' => $bumdes->kecamatan
                            ],
                            'matched_bumdes' => [
                                [
                                    'id' => $bumdes->id,
                                    'namabumdesa' => $bumdes->namabumdesa,
                                    'desa' => $bumdes->desa,
                                    'kecamatan' => $bumdes->kecamatan
                                ]
                            ]
                        ];
                        
                        $documents[] = $document;
                    }
                }
            }
            
            // Also scan dokumen_badanhukum folder for additional backup files
            $documentsPath = 'dokumen_badanhukum';
            if (Storage::disk('public')->exists($documentsPath)) {
                $files = Storage::disk('public')->files($documentsPath);
                
                foreach ($files as $file) {
                    $fileName = basename($file);
                    
                    // Skip .gitignore and other system files
                    if (in_array($fileName, ['.gitignore', '.DS_Store', 'Thumbs.db'])) {
                        continue;
                    }
                    
                    // Check if this file is already in database
                    $alreadyInDb = false;
                    foreach ($documents as $doc) {
                        if (str_contains($doc['original_path'], $fileName) || $doc['filename'] === $fileName) {
                            $alreadyInDb = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyInDb) {
                        $fileInfo = [
                            'filename' => $fileName,
                            'original_path' => $file,
                            'document_type' => 'backup_file',
                            'document_label' => 'File Backup',
                            'size' => Storage::disk('public')->size($file),
                            'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                            'last_modified' => Storage::disk('public')->lastModified($file),
                            'url' => '/storage/' . $file,
                            'file_exists' => true,
                            'status' => 'backup',
                            'bumdes_info' => null,
                            'matched_bumdes' => $this->findMatchingBumdes($fileName)
                        ];
                        
                        $documents[] = $fileInfo;
                    }
                }
            }
            
            // Sort by BUMDes name then document type
            usort($documents, function($a, $b) {
                if ($a['bumdes_info'] && $b['bumdes_info']) {
                    $cmp = strcmp($a['bumdes_info']['namabumdesa'], $b['bumdes_info']['namabumdesa']);
                    if ($cmp === 0) {
                        return strcmp($a['document_type'], $b['document_type']);
                    }
                    return $cmp;
                } elseif ($a['bumdes_info']) {
                    return -1;
                } elseif ($b['bumdes_info']) {
                    return 1;
                } else {
                    return strcmp($a['filename'], $b['filename']);
                }
            });
            
            $summary = [
                'total_documents' => count($documents),
                'database_documents' => count(array_filter($documents, function($doc) { 
                    return $doc['document_type'] !== 'backup_file'; 
                })),
                'backup_files' => count(array_filter($documents, function($doc) { 
                    return $doc['document_type'] === 'backup_file'; 
                })),
                'accessible_files' => count(array_filter($documents, function($doc) { 
                    return $doc['file_exists']; 
                })),
                'missing_files' => count(array_filter($documents, function($doc) { 
                    return !$doc['file_exists']; 
                }))
            ];
            
            return response()->json([
                'success' => true,
                'data' => $documents,
                'summary' => $summary
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting dokumen badan hukum: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find matching BUMDes for a document filename
     */
    private function findMatchingBumdes($filename)
    {
        $bumdesList = Bumdes::select('id', 'namabumdesa', 'desa', 'kecamatan')->get();
        $matches = [];
        
        foreach ($bumdesList as $bumdes) {
            // Check if filename contains BUMDes name or desa name
            $searchTerms = [
                strtolower($bumdes->namabumdesa),
                strtolower($bumdes->desa),
                strtolower(str_replace(' ', '', $bumdes->desa)),
                strtolower(str_replace(['BUMDES', 'BUM DESA', 'BUMDESA'], '', $bumdes->namabumdesa))
            ];
            
            $filenameLower = strtolower($filename);
            
            foreach ($searchTerms as $term) {
                if (!empty($term) && strlen($term) > 3 && strpos($filenameLower, $term) !== false) {
                    $matches[] = [
                        'id' => $bumdes->id,
                        'namabumdesa' => $bumdes->namabumdesa,
                        'desa' => $bumdes->desa,
                        'kecamatan' => $bumdes->kecamatan,
                        'match_term' => $term
                    ];
                    break; // Only add once per BUMDes
                }
            }
        }
        
        return $matches;
    }

    /**
     * Link document to specific BUMDes
     */
    public function linkDocument(Request $request)
    {
        $validated = $request->validate([
            'filename' => 'required|string',
            'bumdes_id' => 'required|integer|exists:bumdes,id',
            'document_type' => 'required|string|in:perdes,anggaran_dasar,anggaran_rumah_tangga,berita_acara,sk_pengurus,program_kerja,profil,other'
        ]);

        try {
            $bumdes = Bumdes::findOrFail($validated['bumdes_id']);
            
            // Create or update document linkage (this could be stored in a separate table)
            // For now, we'll just return success and log the linkage
            Log::info("Document linked: {$validated['filename']} -> BUMDes ID: {$validated['bumdes_id']} ({$bumdes->namabumdesa})");
            
            return response()->json([
                'success' => true,
                'message' => "Dokumen berhasil dikaitkan dengan {$bumdes->namabumdesa}",
                'data' => [
                    'filename' => $validated['filename'],
                    'bumdes' => $bumdes,
                    'document_type' => $validated['document_type']
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error linking document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengaitkan dokumen'
            ], 500);
        }
    }
}