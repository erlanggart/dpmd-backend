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
     * Helper function to generate proper storage URL based on environment
     *
     * @param string $filePath
     * @return string
     */
    private function getStorageUrl(string $filePath): string
    {
        // Determine folder based on file path
        $folder = 'bumdes'; // default folder
        
        if (strpos($filePath, 'laporan_keuangan/') === 0) {
            $folder = 'laporan_keuangan';
            $filename = basename($filePath);
        } elseif (strpos($filePath, 'dokumen_badanhukum/') === 0) {
            $folder = 'dokumen_badanhukum';
            $filename = basename($filePath);
        } else {
            // For files without folder prefix, assume they're in appropriate folders
            $filename = basename($filePath);
            // Determine folder based on context or default to bumdes
            if (strpos($filename, 'laporan') !== false || strpos($filename, 'keuangan') !== false) {
                $folder = 'laporan_keuangan';
            } elseif (strpos($filename, 'perdes') !== false || strpos($filename, 'profil') !== false || 
                      strpos($filename, 'berita') !== false || strpos($filename, 'anggaran') !== false || 
                      strpos($filename, 'sk') !== false || strpos($filename, 'program') !== false) {
                $folder = 'dokumen_badanhukum';
            }
        }
        
        if (config('app.env') === 'production') {
            // Production: Use new API uploads route
            return 'https://dpmdbogorkab.id/api/uploads/' . $folder . '/' . $filename;
        } else {
            // Development: Use new API uploads route
            return config('app.url') . '/api/uploads/' . $folder . '/' . $filename;
        }
    }

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
            
            // Determine folder based on file type
            $folder = 'bumdes'; // default
            $laporanKeuanganFields = ['LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'];
            $dokumenBadanHukumFields = ['Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa'];
            
            if (in_array($fileKey, $laporanKeuanganFields)) {
                $folder = 'laporan_keuangan';
            } elseif (in_array($fileKey, $dokumenBadanHukumFields)) {
                $folder = 'dokumen_badanhukum';
            }
            
            // Save to public/uploads/{folder} instead of storage/app/public
            $file = $request->file($fileKey);
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Create directory if it doesn't exist
            $uploadPath = public_path('uploads/' . $folder);
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Move file to public/uploads/{folder}
            $file->move($uploadPath, $filename);
            
            // Return just the filename (not the full path)
            return $filename;
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
        \Illuminate\Support\Facades\Log::info('getDokumenBadanHukum called from: ' . request()->header('Origin'));
        
        try {
            $documents = [];
            
            // Get all BUMDes with their dokumen badan hukum fields (tanpa laporan keuangan)
            $documentColumns = [
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
                        
                        // Check if file exists in public/uploads/dokumen_badanhukum
                        $publicPath = public_path('uploads/dokumen_badanhukum/' . $filename);
                        $fileExists = file_exists($publicPath);
                        $fileSize = 0;
                        $lastModified = time();
                        
                        if ($fileExists) {
                            try {
                                $fileSize = filesize($publicPath);
                                $lastModified = filemtime($publicPath);
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
                            'file_size_formatted' => $this->formatBytes($fileSize),
                            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                            'last_modified' => date('Y-m-d H:i:s', $lastModified),
                            'url' => '/api/uploads/dokumen_badanhukum/' . $filename,
                            'download_url' => $fileExists ? $this->getStorageUrl('dokumen_badanhukum/' . $filename) : null,
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
            $documentsPath = public_path('uploads/dokumen_badanhukum');
            
            if (is_dir($documentsPath)) {
                $files = array_diff(scandir($documentsPath), array('.', '..'));
                
                foreach ($files as $fileName) {
                    $filePath = $documentsPath . '/' . $fileName;
                    
                    // Skip directories and system files
                    if (is_dir($filePath) || in_array($fileName, ['.gitignore', '.DS_Store', 'Thumbs.db'])) {
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
                        $matchedBumdes = $this->findMatchingBumdes($fileName);
                        
                        // Generate correct URL for public/uploads files
                        $downloadUrl = $this->getStorageUrl('dokumen_badanhukum/' . $fileName);
                        
                        $fileInfo = [
                            'filename' => $fileName,
                            'original_path' => 'uploads/dokumen_badanhukum/' . $fileName,
                            'document_type' => 'unlinked',
                            'document_label' => 'Tidak Terhubung',
                            'size' => filesize($filePath),
                            'file_size_formatted' => $this->formatBytes(filesize($filePath)),
                            'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                            'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'url' => '/api/uploads/dokumen_badanhukum/' . $fileName,
                            'download_url' => $downloadUrl,
                            'file_exists' => true,
                            'status' => 'unlinked',
                            'bumdes_name' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['namabumdesa'] : 'File Tidak Terhubung',
                            'desa' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['desa'] : null,
                            'kecamatan' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['kecamatan'] : null,
                            'bumdes_info' => null,
                            'matched_bumdes' => $matchedBumdes
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
                'status' => 'success',
                'message' => 'Dokumen badan hukum berhasil diambil',  
                'data' => $documents,
                'total' => count($documents),
                'summary' => $summary
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting dokumen badan hukum: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data dokumen: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Find matching BUMDes for a document filename
     */
    private function findMatchingBumdes($filename)
    {
        // EXACT FILENAME MATCHING ONLY
        $bumdesList = Bumdes::all();
        $matches = [];
        
        foreach ($bumdesList as $bumdes) {
            // Check all document fields for exact filename match
            $documentFields = [
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 
                'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa',
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'
            ];
            
            foreach ($documentFields as $field) {
                if (!empty($bumdes->$field)) {
                    $dbFilename = basename($bumdes->$field);
                    
                    // Exact match only
                    if ($dbFilename === $filename) {
                        $matches[] = [
                            'id' => $bumdes->id,
                            'namabumdesa' => $bumdes->namabumdesa,
                            'desa' => $bumdes->desa,
                            'kecamatan' => $bumdes->kecamatan,
                            'match_field' => $field,
                            'match_type' => 'exact'
                        ];
                        break; // Only add once per BUMDes
                    }
                }
            }
        }
        
        return $matches;
    }



    /**
     * Format file size in human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        if ($size === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($size, 1024));
        
        return round($size / pow(1024, $power), $precision) . ' ' . $units[$power];
    }

    /**
     * Get laporan keuangan files for all BUMDes
     */
    public function getLaporanKeuangan()
    {
        \Illuminate\Support\Facades\Log::info('getLaporanKeuangan called from: ' . request()->header('Origin'));
        
        try {
            $documents = [];
            
            // Get all BUMDes with their laporan keuangan fields
            $laporanColumns = [
                'LaporanKeuangan2021' => 'Laporan Keuangan 2021',
                'LaporanKeuangan2022' => 'Laporan Keuangan 2022', 
                'LaporanKeuangan2023' => 'Laporan Keuangan 2023',
                'LaporanKeuangan2024' => 'Laporan Keuangan 2024',
            ];
            
            $bumdesList = Bumdes::all();
            
            foreach ($bumdesList as $bumdes) {
                foreach ($laporanColumns as $column => $columnLabel) {
                    if (!empty($bumdes->$column)) {
                        $filename = $bumdes->$column; // Langsung nama file tanpa path
                        
                        // Check if file exists in public/uploads/laporan_keuangan
                        $publicPath = public_path('uploads/laporan_keuangan/' . $filename);
                        $fileExists = file_exists($publicPath);
                        $fileSize = 0;
                        $lastModified = time();
                        
                        if ($fileExists) {
                            try {
                                $fileSize = filesize($publicPath);
                                $lastModified = filemtime($publicPath);
                            } catch (\Exception $e) {
                                // If there's an error getting file info, continue with defaults
                            }
                        }
                        
                        $documents[] = [
                            'id' => $bumdes->id,
                            'bumdes_name' => $bumdes->namabumdesa,
                            'kecamatan' => $bumdes->kecamatan,
                            'desa' => $bumdes->desa,
                            'bumdes_info' => [
                                'id' => $bumdes->id,
                                'namabumdesa' => $bumdes->namabumdesa,
                                'desa' => $bumdes->desa,
                                'kecamatan' => $bumdes->kecamatan
                            ],
                            'document_type' => $column,
                            'document_label' => $columnLabel,
                            'filename' => $filename,
                            'file_path' => 'uploads/laporan_keuangan/' . $filename,
                            'file_exists' => $fileExists,
                            'file_size' => $fileSize,
                            'file_size_formatted' => $this->formatBytes($fileSize),
                            'last_modified' => date('Y-m-d H:i:s', $lastModified),
                            'download_url' => $fileExists ? $this->getStorageUrl('laporan_keuangan/' . $filename) : null
                        ];
                    }
                }
            }
            
            // Also scan laporan_keuangan folder for additional backup files
            $laporanPath = public_path('uploads/laporan_keuangan');
            
            if (is_dir($laporanPath)) {
                $files = array_diff(scandir($laporanPath), array('.', '..'));
                
                foreach ($files as $fileName) {
                    $filePath = $laporanPath . '/' . $fileName;
                    
                    // Skip directories and system files
                    if (is_dir($filePath) || in_array($fileName, ['.gitignore', '.DS_Store', 'Thumbs.db'])) {
                        continue;
                    }
                    
                    // Check if this file is already linked to a BUMDes
                    $linkedBumdes = Bumdes::where('LaporanKeuangan2021', $fileName)
                                         ->orWhere('LaporanKeuangan2022', $fileName)
                                         ->orWhere('LaporanKeuangan2023', $fileName)
                                         ->orWhere('LaporanKeuangan2024', $fileName)
                                         ->first();
                    
                    if (!$linkedBumdes) {
                        // Try to find matching BUMDes for unlinked files
                        $matchedBumdes = $this->findMatchingBumdes($fileName);
                        
                        // If we found a match, create bumdes_info structure
                        $bumdesInfo = null;
                        if ($matchedBumdes && count($matchedBumdes) > 0) {
                            $firstMatch = $matchedBumdes[0];
                            $bumdesInfo = [
                                'id' => $firstMatch['id'],
                                'namabumdesa' => $firstMatch['namabumdesa'],
                                'desa' => $firstMatch['desa'],
                                'kecamatan' => $firstMatch['kecamatan']
                            ];
                        }
                        
                        // Generate correct URL for public/uploads files
                        $downloadUrl = $this->getStorageUrl('laporan_keuangan/' . $fileName);
                        
                        $documents[] = [
                            'id' => null,
                            'bumdes_name' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['namabumdesa'] : 'File Tidak Terhubung',
                            'kecamatan' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['kecamatan'] : null,
                            'desa' => $matchedBumdes && count($matchedBumdes) > 0 ? $matchedBumdes[0]['desa'] : null,
                            'bumdes_info' => $bumdesInfo, // Add bumdes_info for matched files
                            'document_type' => $bumdesInfo ? 'matched' : 'unlinked', // Change type if matched
                            'document_label' => 'File Laporan Keuangan',
                            'filename' => $fileName,
                            'file_path' => 'uploads/laporan_keuangan/' . $fileName,
                            'file_exists' => true,
                            'file_size' => filesize($filePath),
                            'file_size_formatted' => $this->formatBytes(filesize($filePath)),
                            'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'download_url' => $downloadUrl,
                            'matched_bumdes' => $matchedBumdes
                        ];
                    }
                }
            }
            
            // Sort by BUMDes name, then by document type
            usort($documents, function($a, $b) {
                if ($a['bumdes_name'] == $b['bumdes_name']) {
                    return strcmp($a['document_type'], $b['document_type']);
                }
                return strcmp($a['bumdes_name'], $b['bumdes_name']);
            });
            
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan keuangan berhasil diambil',
                'data' => $documents,
                'total' => count($documents),
                'summary' => [
                    'total_files' => count($documents),
                    'files_exist' => count(array_filter($documents, fn($doc) => $doc['file_exists'])),
                    'files_missing' => count(array_filter($documents, fn($doc) => !$doc['file_exists'])),
                    'unlinked_files' => count(array_filter($documents, fn($doc) => $doc['document_type'] === 'unlinked'))
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data laporan keuangan: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Link a document to a specific BUMDes
     */
    public function linkDocument(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
                'bumdes_id' => 'required|integer|exists:bumdes,id',
                'document_type' => 'required|in:dokumen_badan_hukum,laporan_keuangan'
            ]);

            $bumdes = Bumdes::findOrFail($request->bumdes_id);
            $filename = $request->filename;
            $documentType = $request->document_type;

            // Check if file exists in public/uploads
            $publicPath = public_path('uploads/' . $documentType . '/' . $filename);
            $fileExists = file_exists($publicPath);
            
            if (!$fileExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan di public/uploads/' . $documentType
                ], 404);
            }

            // Determine which field to update based on document type
            if ($documentType === 'dokumen_badan_hukum') {
                // For dokumen badan hukum, we need to determine the specific field
                // Based on filename pattern, assign to appropriate field
                $fieldToUpdate = $this->determineDocumentField($filename);
                
                if ($fieldToUpdate) {
                    $bumdes->update([$fieldToUpdate => $filename]);
                    $message = "Dokumen {$filename} berhasil dikaitkan dengan BUMDes {$bumdes->namabumdesa} sebagai {$fieldToUpdate}";
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tidak dapat menentukan jenis dokumen badan hukum'
                    ], 400);
                }
            } else {
                // For laporan keuangan, determine year from filename
                $year = $this->extractYearFromFilename($filename);
                $fieldToUpdate = "LaporanKeuangan{$year}";
                
                // Check if the field exists in database schema
                $validFields = ['LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'];
                
                if (in_array($fieldToUpdate, $validFields)) {
                    $bumdes->update([$fieldToUpdate => $filename]);
                    $message = "Laporan keuangan {$filename} berhasil dikaitkan dengan BUMDes {$bumdes->namabumdesa} untuk tahun {$year}";
                } else {
                    // Default to LaporanKeuangan2024 if year cannot be determined
                    $bumdes->update(['LaporanKeuangan2024' => $filename]);
                    $message = "Laporan keuangan {$filename} berhasil dikaitkan dengan BUMDes {$bumdes->namabumdesa} (default tahun 2024)";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Error linking document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengaitkan dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine document field based on filename
     */
    private function determineDocumentField($filename)
    {
        $filename = strtolower($filename);
        
        if (strpos($filename, 'perdes') !== false || strpos($filename, 'peraturan') !== false) {
            return 'Perdes';
        } elseif (strpos($filename, 'profil') !== false || strpos($filename, 'profile') !== false) {
            return 'ProfilBUMDesa';
        } elseif (strpos($filename, 'berita') !== false || strpos($filename, 'ba') !== false || strpos($filename, 'musdes') !== false) {
            return 'BeritaAcara';
        } elseif (strpos($filename, 'anggaran dasar') !== false || strpos($filename, 'ad') !== false) {
            return 'AnggaranDasar';
        } elseif (strpos($filename, 'anggaran rumah tangga') !== false || strpos($filename, 'art') !== false) {
            return 'AnggaranRumahTangga';
        } elseif (strpos($filename, 'program kerja') !== false || strpos($filename, 'proker') !== false || strpos($filename, 'rencana') !== false) {
            return 'ProgramKerja';
        } elseif (strpos($filename, 'sk') !== false || strpos($filename, 'surat keputusan') !== false) {
            return 'SK_BUM_Desa';
        }
        
        // Default to ProfilBUMDesa if cannot determine
        return 'ProfilBUMDesa';
    }

    /**
     * Extract year from filename
     */
    private function extractYearFromFilename($filename)
    {
        // Look for 4-digit year in filename
        if (preg_match('/20(21|22|23|24)/', $filename, $matches)) {
            return '20' . $matches[1];
        }
        
        // Default to current year or 2024
        return '2024';
    }
}