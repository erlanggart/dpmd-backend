<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bumdes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BumdesController extends Controller
{
    /**
     * Clear cache untuk file listing
     */
    private function clearFileListingCache()
    {
        Cache::forget('bumdes_dokumen_badan_hukum_with_unlinked');
        Cache::forget('bumdes_dokumen_badan_hukum_without_unlinked');
        Cache::forget('bumdes_laporan_keuangan_with_unlinked'); 
        Cache::forget('bumdes_laporan_keuangan_without_unlinked');
        Cache::forget('bumdes_dokumen_badan_hukum_fast');
        Cache::forget('bumdes_laporan_keuangan_fast');
    }

    /**
     * Helper function to get file statistics efficiently
     *
     * @param string $filePath
     * @return array
     */
    private function getFileStats(string $filePath): array
    {
        try {
            if (file_exists($filePath)) {
                $stat = stat($filePath);
                return [
                    'exists' => true,
                    'size' => $stat['size'] ?? 0,
                    'last_modified' => date('Y-m-d H:i:s', $stat['mtime'] ?? time())
                ];
            }
        } catch (\Exception $e) {
            // File access error
        }
        
        return [
            'exists' => false,
            'size' => 0,
            'last_modified' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Categorize document by filename for dokumen badan hukum
     */
    private function categorizeDocumentByName($filename)
    {
        $filename = strtolower($filename);
        
        // Define categories based on keywords in filename
        if (strpos($filename, 'perdes') !== false || strpos($filename, 'peraturan') !== false) {
            return ['type' => 'Perdes', 'label' => 'Peraturan Desa'];
        } elseif (strpos($filename, 'profil') !== false) {
            return ['type' => 'ProfilBUMDesa', 'label' => 'Profil BUMDes'];
        } elseif (strpos($filename, 'berita acara') !== false || strpos($filename, 'ba ') !== false || strpos($filename, 'ba.') !== false) {
            return ['type' => 'BeritaAcara', 'label' => 'Berita Acara'];
        } elseif (strpos($filename, 'anggaran dasar') !== false || strpos($filename, 'ad ') !== false || strpos($filename, 'ad.') !== false) {
            return ['type' => 'AnggaranDasar', 'label' => 'Anggaran Dasar'];
        } elseif (strpos($filename, 'anggaran rumah tangga') !== false || strpos($filename, 'art ') !== false || strpos($filename, 'art.') !== false) {
            return ['type' => 'AnggaranRumahTangga', 'label' => 'Anggaran RT'];
        } elseif (strpos($filename, 'program kerja') !== false || strpos($filename, 'proker') !== false || strpos($filename, 'rencana kerja') !== false) {
            return ['type' => 'ProgramKerja', 'label' => 'Program Kerja'];
        } elseif (strpos($filename, 'sk ') !== false || strpos($filename, 'sk.') !== false || strpos($filename, 'surat keputusan') !== false) {
            return ['type' => 'SK_BUM_Desa', 'label' => 'SK BUMDes'];
        } else {
            return ['type' => 'unlinked', 'label' => 'Tidak Terkategorikan'];
        }
    }

    /**
     * Categorize laporan keuangan by filename  
     */
    private function categorizeLaporanKeuanganByName($filename)
    {
        $filename = strtolower($filename);
        
        if (strpos($filename, '2021') !== false) {
            return ['type' => 'LaporanKeuangan2021', 'label' => '2021'];
        } elseif (strpos($filename, '2022') !== false) {
            return ['type' => 'LaporanKeuangan2022', 'label' => '2022'];
        } elseif (strpos($filename, '2023') !== false) {
            return ['type' => 'LaporanKeuangan2023', 'label' => '2023'];
        } elseif (strpos($filename, '2024') !== false) {
            return ['type' => 'LaporanKeuangan2024', 'label' => '2024'];
        } elseif (strpos($filename, '2025') !== false) {
            return ['type' => 'LaporanKeuangan2025', 'label' => '2025'];
        } else {
            return ['type' => 'unlinked', 'label' => 'Tidak Terkategorikan'];
        }
    }

    /**
     * Helper function to generate proper storage URL based on environment
     *
     * @param string $filePath
     * @return string
     */
    private function getStorageUrl(string $filePath): string
    {
        // Determine folder based on file path
        $folder = 'bumdes_dokumen_badanhukum'; // default folder
        
        if (strpos($filePath, 'bumdes_laporan_keuangan/') !== false) {
            $folder = 'bumdes_laporan_keuangan';
        } elseif (strpos($filePath, 'bumdes_dokumen_badanhukum/') !== false) {
            $folder = 'bumdes_dokumen_badanhukum';
        }
        
        $filename = basename($filePath);
        
        // Just encode spaces and special characters that might break URLs
        $encodedFilename = str_replace(' ', '%20', $filename);
        
        if (config('app.env') === 'production') {
            // Production: Use URL with folder path since direct access might not work
            return 'https://dpmdbogorkab.id/api/uploads/' . $folder . '/' . $encodedFilename;
        } else {
            // Development: Use local storage path with folder
            return config('app.url') . '/storage/app/uploads/' . $folder . '/' . $encodedFilename;
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
            if ($currentFilePath && Storage::exists('uploads/' . $currentFilePath)) {
                Storage::delete('uploads/' . $currentFilePath);
            }
            
            // Determine folder based on file type
            $folder = 'bumdes'; // default
            $laporanKeuanganFields = ['LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'];
            $dokumenBadanHukumFields = ['Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa'];
            
            if (in_array($fileKey, $laporanKeuanganFields)) {
                $folder = 'bumdes_laporan_keuangan';
            } elseif (in_array($fileKey, $dokumenBadanHukumFields)) {
                $folder = 'bumdes_dokumen_badanhukum';
            }
            
            // Save to storage/app/uploads/{folder}
            $file = $request->file($fileKey);
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Store file using Laravel Storage (storage/app/uploads/{folder})
            $storagePath = "uploads/{$folder}";
            $path = $file->storeAs($storagePath, $filename);
            
            if ($path) {
                // Return relative path from uploads folder
                $relativePath = "{$folder}/{$filename}";
                return $relativePath;
            } else {
                return null;
            }
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

            // Pisahkan data file dan non-file
            $dataToSave = [];
            $fileFields = [
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
                'ProgramKerja', 'SK_BUM_Desa'
            ];
            
            // Ambil hanya data non-file untuk create
            foreach ($validatedData as $key => $value) {
                if (!in_array($key, $fileFields)) {
                    $dataToSave[$key] = $value;
                }
            }

            $bumdes = Bumdes::create($dataToSave);

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $path = $this->uploadFile($request, $field);
                    if ($path) {
                        $bumdes->$field = $path;
                    }
                }
            }
            
            $bumdes->save();
            
            // Clear cache setelah data berubah
            $this->clearFileListingCache();

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
            
            // Clear cache setelah data berubah
            $this->clearFileListingCache();

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
            
            // Clear cache setelah data dihapus
            $this->clearFileListingCache();

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
        
        // Temporarily disable cache to avoid issues
        return $this->_getDokumenBadanHukum(true);
    }
    
    /**
     * Internal method untuk mengambil dokumen badan hukum
     */
    private function _getDokumenBadanHukum($includeUnlinked = true)
    {
        try {
            $documents = [];
            
            // Scan bumdes_dokumen_badanhukum folder untuk mengambil semua file
            // Prioritas public path untuk production, fallback ke storage path
            $documentsPath = public_path('uploads/bumdes_dokumen_badanhukum');
            $fallbackPath = storage_path('app/uploads/bumdes_dokumen_badanhukum');
            
            $scanPath = is_dir($documentsPath) ? $documentsPath : $fallbackPath;
            Log::info("BUMDes Dokumen Scan Path", [
                'primary_path' => $documentsPath,
                'fallback_path' => $fallbackPath,
                'using_path' => $scanPath,
                'primary_exists' => is_dir($documentsPath),
                'fallback_exists' => is_dir($fallbackPath)
            ]);
            
            if (is_dir($scanPath)) {
                $files = array_diff(scandir($scanPath), array('.', '..'));
                
                foreach ($files as $fileName) {
                    $filePath = $scanPath . '/' . $fileName;
                    
                    // Skip directories and system files
                    if (is_dir($filePath) || in_array($fileName, ['.gitignore', '.DS_Store', 'Thumbs.db'])) {
                        continue;
                    }
                    
                    $fileStats = $this->getFileStats($filePath);
                    $documentType = $this->categorizeDocumentByName($fileName);
                    $matchedBumdes = $this->findMatchingBumdes($fileName);
                    
                    $document = [
                        'filename' => $fileName,
                        'original_path' => 'uploads/bumdes_dokumen_badanhukum/' . $fileName,
                        'document_type' => $documentType['type'],
                        'document_label' => $documentType['label'],
                        'size' => $fileStats['size'],
                        'file_size_formatted' => $this->formatBytes($fileStats['size']),
                        'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                        'last_modified' => $fileStats['last_modified'],
                        'url' => '/api/uploads/bumdes_dokumen_badanhukum/' . $fileName,
                        'download_url' => $fileStats['exists'] ? $this->getStorageUrl('bumdes_dokumen_badanhukum/' . $fileName) : null,
                        'file_exists' => $fileStats['exists'],
                        'status' => $fileStats['exists'] ? 'available' : 'missing',
                        'matched_bumdes' => $matchedBumdes,
                        'bumdes_name' => !empty($matchedBumdes) ? $matchedBumdes[0]['namabumdesa'] : 'Tidak Diketahui',
                        'desa' => !empty($matchedBumdes) ? $matchedBumdes[0]['desa'] : '',
                        'kecamatan' => !empty($matchedBumdes) ? $matchedBumdes[0]['kecamatan'] : '',
                        'id' => !empty($matchedBumdes) ? $matchedBumdes[0]['id'] : null,
                        // Add bumdes_info field for frontend compatibility
                        'bumdes_info' => !empty($matchedBumdes) ? $matchedBumdes[0] : null
                    ];
                    
                    $documents[] = $document;
                }
            }
            
            // Sort by BUMDes name then document type
            usort($documents, function($a, $b) {
                $cmp = strcmp($a['bumdes_name'], $b['bumdes_name']);
                if ($cmp === 0) {
                    return strcmp($a['document_type'], $b['document_type']);
                }
                return $cmp;
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
     * ULTRA SECURE - 100% exact matching only, no fuzzy matching
     */
    private function findMatchingBumdes($filename)
    {
        // STRICT SECURITY: Only exact filename matches allowed
        $bumdesList = Bumdes::all();
        $matches = [];
        
        // Log untuk debugging keamanan
        Log::info("SECURITY MATCHING: Looking for exact match for filename: {$filename}");
        
        foreach ($bumdesList as $bumdes) {
            // All document fields that can contain filenames
            $documentFields = [
                'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 
                'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa',
                'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'
            ];
            
            foreach ($documentFields as $field) {
                $dbValue = $bumdes->$field;
                
                // Skip empty values
                if (empty($dbValue) || trim($dbValue) === '') {
                    continue;
                }
                
                // Extract filename from database value
                $extractedFilename = null;
                
                // Method 1: If contains folder path (support both old and new naming convention)
                if (strpos($dbValue, 'bumdes_dokumen_badanhukum/') === 0) {
                    $extractedFilename = substr($dbValue, strlen('bumdes_dokumen_badanhukum/'));
                } elseif (strpos($dbValue, 'bumdes_laporan_keuangan/') === 0) {
                    $extractedFilename = substr($dbValue, strlen('bumdes_laporan_keuangan/'));
                } elseif (strpos($dbValue, 'dokumen_badanhukum/') === 0) {
                    $extractedFilename = substr($dbValue, strlen('dokumen_badanhukum/'));
                } elseif (strpos($dbValue, 'laporan_keuangan/') === 0) {
                    $extractedFilename = substr($dbValue, strlen('laporan_keuangan/'));
                } elseif (strpos($dbValue, '/') !== false) {
                    // Generic path handling with basename()
                    $extractedFilename = basename($dbValue);
                } else {
                    // Assume it's already just the filename
                    $extractedFilename = $dbValue;
                }
                
                // CRITICAL SECURITY CHECK: Must be exact match
                if ($extractedFilename === $filename) {
                    Log::info("SECURITY MATCH FOUND: {$filename} matches BUMDes ID {$bumdes->id} ({$bumdes->namabumdesa}) in field {$field}");
                    
                    // Additional security validation
                    $match = [
                        'id' => $bumdes->id,
                        'namabumdesa' => $bumdes->namabumdesa,
                        'desa' => $bumdes->desa,
                        'kecamatan' => $bumdes->kecamatan,
                        'match_field' => $field,
                        'match_type' => 'exact_secure',
                        'db_value' => $dbValue,
                        'extracted_filename' => $extractedFilename,
                        'security_verified' => true,
                        'match_timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    // Prevent duplicate matches for the same BUMDes
                    $existingMatch = false;
                    foreach ($matches as $existingMatch) {
                        if ($existingMatch['id'] === $bumdes->id) {
                            $existingMatch = true;
                            break;
                        }
                    }
                    
                    if (!$existingMatch) {
                        $matches[] = $match;
                    }
                    
                    // Stop checking other fields for this BUMDes once we find a match
                    break;
                }
            }
        }
        
        // SECURITY LOG: Record match results
        if (empty($matches)) {
            Log::warning("SECURITY: NO MATCH FOUND for filename: {$filename} - File is orphaned");
        } else {
            Log::info("SECURITY: Found " . count($matches) . " secure matches for filename: {$filename}");
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
        
        // Temporarily disable cache to avoid issues
        return $this->_getLaporanKeuangan(true);
    }
    
    /**
     * Internal method untuk mengambil laporan keuangan
     */
    private function _getLaporanKeuangan($includeUnlinked = true)
    {
        try {
            $documents = [];
            
            // Scan bumdes_laporan_keuangan folder untuk mengambil semua file
            // Prioritas public path untuk production, fallback ke storage path
            $laporanPath = public_path('uploads/bumdes_laporan_keuangan');
            $fallbackPath = storage_path('app/uploads/bumdes_laporan_keuangan');
            
            $scanPath = is_dir($laporanPath) ? $laporanPath : $fallbackPath;
            Log::info("BUMDes Laporan Scan Path", [
                'primary_path' => $laporanPath,
                'fallback_path' => $fallbackPath,
                'using_path' => $scanPath,
                'primary_exists' => is_dir($laporanPath),
                'fallback_exists' => is_dir($fallbackPath)
            ]);
            
            if (is_dir($scanPath)) {
                $files = array_diff(scandir($scanPath), array('.', '..'));
                
                foreach ($files as $fileName) {
                    $filePath = $scanPath . '/' . $fileName;
                    
                    // Skip directories and system files
                    if (is_dir($filePath) || in_array($fileName, ['.gitignore', '.DS_Store', 'Thumbs.db'])) {
                        continue;
                    }
                    
                    $fileStats = $this->getFileStats($filePath);
                    $documentType = $this->categorizeLaporanKeuanganByName($fileName);
                    $matchedBumdes = $this->findMatchingBumdes($fileName);
                    
                    $document = [
                        'filename' => $fileName,
                        'original_path' => 'uploads/bumdes_laporan_keuangan/' . $fileName,
                        'document_type' => $documentType['type'],
                        'document_label' => $documentType['label'],
                        'size' => $fileStats['size'],
                        'file_size_formatted' => $this->formatBytes($fileStats['size']),
                        'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                        'last_modified' => $fileStats['last_modified'],
                        'url' => '/api/uploads/bumdes_laporan_keuangan/' . $fileName,
                        'download_url' => $fileStats['exists'] ? $this->getStorageUrl('bumdes_laporan_keuangan/' . $fileName) : null,
                        'file_exists' => $fileStats['exists'],
                        'status' => $fileStats['exists'] ? 'available' : 'missing',
                        'matched_bumdes' => $matchedBumdes,
                        'bumdes_name' => !empty($matchedBumdes) ? $matchedBumdes[0]['namabumdesa'] : 'Tidak Diketahui',
                        'desa' => !empty($matchedBumdes) ? $matchedBumdes[0]['desa'] : '',
                        'kecamatan' => !empty($matchedBumdes) ? $matchedBumdes[0]['kecamatan'] : '',
                        'id' => !empty($matchedBumdes) ? $matchedBumdes[0]['id'] : null,
                        // Add bumdes_info field for frontend compatibility
                        'bumdes_info' => !empty($matchedBumdes) ? $matchedBumdes[0] : null
                    ];
                    
                    $documents[] = $document;
                }
            }
            
            // Sort by BUMDes name, then by document type
            usort($documents, function($a, $b) {
                $cmp = strcmp($a['bumdes_name'], $b['bumdes_name']);
                if ($cmp === 0) {
                    return strcmp($a['document_type'], $b['document_type']);
                }
                return $cmp;
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
     * Fast version - Hanya mengambil dokumen yang terhubung dengan BUMDes (tanpa scan folder)
     */
    public function getDokumenBadanHukumFast()
    {
        \Illuminate\Support\Facades\Log::info('getDokumenBadanHukumFast called from: ' . request()->header('Origin'));
        
        // Cache untuk 10 menit karena tidak scan folder
        return Cache::remember('bumdes_dokumen_badan_hukum_fast', 600, function () {
            return $this->_getDokumenBadanHukum(false); // false = skip scan folder
        });
    }

    /**
     * Fast version - Hanya mengambil laporan keuangan yang terhubung dengan BUMDes (tanpa scan folder)
     */
    public function getLaporanKeuanganFast()
    {
        \Illuminate\Support\Facades\Log::info('getLaporanKeuanganFast called from: ' . request()->header('Origin'));
        
        // Cache untuk 10 menit karena tidak scan folder
        return Cache::remember('bumdes_laporan_keuangan_fast', 600, function () {
            return $this->_getLaporanKeuangan(false); // false = skip scan folder
        });
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

    /**
     * Delete a specific file and unlink it from database
     */
    public function deleteFile(Request $request)
    {
        try {
            $request->validate([
                'filename' => 'required|string',
                'folder' => 'required|in:dokumen_badanhukum,laporan_keuangan',
                'bumdes_id' => 'nullable|integer'
            ]);

            $filename = $request->filename;
            $folder = $request->folder;
            $bumdesId = $request->bumdes_id;

            // Check if file exists in storage
            $filePath = storage_path("app/uploads/{$folder}/{$filename}");
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            // Find BUMDes that references this file
            $affectedBumdes = [];
            
            if ($folder === 'dokumen_badanhukum') {
                $documentFields = ['Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa'];
                
                foreach ($documentFields as $field) {
                    $bumdesList = Bumdes::where($field, $filename)->get();
                    foreach ($bumdesList as $bumdes) {
                        $affectedBumdes[] = $bumdes;
                        // Clear the field in database
                        $bumdes->update([$field => null]);
                    }
                }
            } else { // laporan_keuangan
                $laporanFields = ['LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'];
                
                foreach ($laporanFields as $field) {
                    $bumdesList = Bumdes::where($field, $filename)->get();
                    foreach ($bumdesList as $bumdes) {
                        $affectedBumdes[] = $bumdes;
                        // Clear the field in database
                        $bumdes->update([$field => null]);
                    }
                }
            }

            // Delete the physical file
            if (unlink($filePath)) {
                $message = "File {$filename} berhasil dihapus";
                if (count($affectedBumdes) > 0) {
                    $bumdesNames = array_map(function($bumdes) {
                        return $bumdes->namabumdesa;
                    }, $affectedBumdes);
                    $message .= " dan unlink dari BUMDes: " . implode(', ', $bumdesNames);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'affected_bumdes' => count($affectedBumdes)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menghapus file'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error deleting file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get BUMDES data for specific desa (called from desa dashboard)
     */
    public function getByDesa(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only allow desa users or superadmin
            if (!in_array($user->role, ['desa', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // For desa users, get their desa_id, for superadmin allow desa_id parameter
            $desaId = $user->role === 'superadmin' && $request->has('desa_id') 
                ? $request->get('desa_id') 
                : $user->desa_id;

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa ID required'
                ], 400);
            }

            // Find BUMDES for this desa
            $bumdes = Bumdes::where('desa_id', $desaId)->first();

            if ($bumdes) {
                return response()->json([
                    'success' => true,
                    'data' => $bumdes,
                    'message' => 'Data BUMDES berhasil diambil'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Data BUMDES belum tersedia'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error getting BUMDES by desa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data BUMDES'
            ], 500);
        }
    }

    /**
     * Store new BUMDES data from desa dashboard
     */
    public function storeByDesa(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only allow desa users or superadmin
            if (!in_array($user->role, ['desa', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $desaId = $user->role === 'superadmin' && $request->has('desa_id') 
                ? $request->get('desa_id') 
                : $user->desa_id;

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa ID required'
                ], 400);
            }

            // Check if BUMDES already exists for this desa
            $existingBumdes = Bumdes::where('desa_id', $desaId)->first();
            if ($existingBumdes) {
                return response()->json([
                    'success' => false,
                    'message' => 'BUMDES untuk desa ini sudah ada. Gunakan update untuk mengubah data.'
                ], 409);
            }

            // Validate required fields
            $validated = $request->validate([
                'namabumdesa' => 'required|string|max:255',
                'desa' => 'required|string|max:255',
                'kecamatan' => 'required|string|max:255',
                'kode_desa' => 'nullable|string|max:50',
                'TahunPendirian' => 'nullable|integer|min:1900|max:' . date('Y'),
                'AlamatBumdes' => 'nullable|string',
                'NoHpBumdes' => 'nullable|string|max:20',
                'EmailBumdes' => 'nullable|email|max:255',
                // Add other fields as needed
            ]);

            // Add desa_id and upload_status
            $validated['desa_id'] = $desaId;
            $validated['upload_status'] = 'uploaded';

            // Create new BUMDES
            $bumdes = Bumdes::create($validated);

            return response()->json([
                'success' => true,
                'data' => $bumdes,
                'message' => 'Data BUMDES berhasil disimpan'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing BUMDES by desa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data BUMDES'
            ], 500);
        }
    }

    /**
     * Update BUMDES data from desa dashboard
     */
    public function updateByDesa(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only allow desa users or superadmin
            if (!in_array($user->role, ['desa', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $bumdes = Bumdes::findOrFail($id);

            // Check if user has access to this BUMDES
            if ($user->role === 'desa' && $bumdes->desa_id !== $user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this BUMDES data'
                ], 403);
            }

            // Validate fields
            $validated = $request->validate([
                'namabumdesa' => 'required|string|max:255',
                'desa' => 'required|string|max:255',
                'kecamatan' => 'required|string|max:255',
                'kode_desa' => 'nullable|string|max:50',
                'TahunPendirian' => 'nullable|integer|min:1900|max:' . date('Y'),
                'AlamatBumdes' => 'nullable|string',
                'NoHpBumdes' => 'nullable|string|max:20',
                'EmailBumdes' => 'nullable|email|max:255',
                // Add other fields as needed
            ]);

            // Update upload_status if it was not uploaded before
            if ($bumdes->upload_status === 'not_uploaded') {
                $validated['upload_status'] = 'uploaded';
            }

            // Update BUMDES
            $bumdes->update($validated);

            return response()->json([
                'success' => true,
                'data' => $bumdes->fresh(),
                'message' => 'Data BUMDES berhasil diperbarui'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data BUMDES tidak ditemukan'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating BUMDES by desa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data BUMDES'
            ], 500);
        }
    }

    /**
     * Delete BUMDES data from desa dashboard
     */
    public function destroyByDesa(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only allow desa users or superadmin
            if (!in_array($user->role, ['desa', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $bumdes = Bumdes::findOrFail($id);

            // Check if user has access to this BUMDES
            if ($user->role === 'desa' && $bumdes->desa_id !== $user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this BUMDES data'
                ], 403);
            }

            // Delete the BUMDES
            $bumdes->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data BUMDES berhasil dihapus'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data BUMDES tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting BUMDES by desa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data BUMDES'
            ], 500);
        }
    }

    /**
     * Get BUMDES statistics for desa dashboard
     */
    public function getDesaStatistics(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only allow desa users or superadmin
            if (!in_array($user->role, ['desa', 'superadmin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $desaId = $user->role === 'superadmin' && $request->has('desa_id') 
                ? $request->get('desa_id') 
                : $user->desa_id;

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa ID required'
                ], 400);
            }

            $bumdes = Bumdes::where('desa_id', $desaId)->first();

            if (!$bumdes) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_bumdes' => false,
                        'upload_status' => 'not_uploaded',
                        'last_updated' => null,
                        'completion_percentage' => 0
                    ]
                ]);
            }

            // Calculate completion percentage based on filled fields
            $requiredFields = [
                'namabumdesa', 'desa', 'kecamatan', 'TahunPendirian',
                'JenisUsaha', 'StatusUsaha', 'ModalAwal', 'TotalTenagaKerja'
            ];

            $filledFields = 0;
            foreach ($requiredFields as $field) {
                if (!empty($bumdes->$field)) {
                    $filledFields++;
                }
            }

            $completionPercentage = round(($filledFields / count($requiredFields)) * 100);

            return response()->json([
                'success' => true,
                'data' => [
                    'has_bumdes' => true,
                    'upload_status' => $bumdes->upload_status,
                    'last_updated' => $bumdes->updated_at,
                    'completion_percentage' => $completionPercentage,
                    'nama_bumdes' => $bumdes->namabumdesa,
                    'jenis_usaha' => $bumdes->JenisUsaha,
                    'status_usaha' => $bumdes->StatusUsaha,
                    'modal_awal' => $bumdes->ModalAwal,
                    'total_tenaga_kerja' => $bumdes->TotalTenagaKerja
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting BUMDES statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik BUMDES'
            ], 500);
        }
    }

    /**
     * Get BUMDES data for specific desa (from desa dashboard)
     */
    public function getDesaBumdes(Request $request)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            $bumdes = Bumdes::with(['produkHukumPerdes', 'produkHukumSkBumdes'])
                            ->where('desa_id', $user->desa_id)
                            ->first();

            return response()->json([
                'success' => true,
                'data' => $bumdes
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting desa BUMDES data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data BUMDES'
            ], 500);
        }
    }

    /**
     * Store new BUMDES data from desa
     */
    public function storeDesaBumdes(Request $request)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            // Validasi data
            $validated = $request->validate([
                'namabumdesa' => 'required|string|max:255',
                'desa' => 'nullable|string|max:255',
                'kecamatan' => 'nullable|string|max:255',
                'kode_desa' => 'nullable|string|max:50',
                'TahunPendirian' => 'nullable|integer|min:1900|max:' . date('Y'),
                'AlamatBumdes' => 'nullable|string',
                'NoHpBumdes' => 'nullable|string|max:20',
                'EmailBumdes' => 'nullable|email|max:255',
                'NoPerdes' => 'nullable|string|max:100',
                'TanggalPerdes' => 'nullable|date',
                'NoSKKemenkumham' => 'nullable|string|max:100',
                'TanggalSKKemenkumham' => 'nullable|date',
                'NamaPenasihat' => 'nullable|string|max:255',
                'NamaPengawas' => 'nullable|string|max:255',
                'NamaDirektur' => 'nullable|string|max:255',
                'NamaSekretaris' => 'nullable|string|max:255',
                'NamaBendahara' => 'nullable|string|max:255',
                'TotalTenagaKerja' => 'nullable|integer|min:0',
                'TenagaKerjaLaki' => 'nullable|integer|min:0',
                'TenagaKerjaPerempuan' => 'nullable|integer|min:0',
                'JenisUsaha' => 'nullable|string|max:255',
                'KelasUsaha' => 'nullable|string|max:100',
                'StatusUsaha' => 'nullable|string|max:100',
                'ModalAwal' => 'nullable|numeric|min:0',
                'ModalSekarang' => 'nullable|numeric|min:0',
                'Aset' => 'nullable|numeric|min:0',
                'KekayaanBersih' => 'nullable|numeric|min:0',
                'Omzet2022' => 'nullable|numeric|min:0',
                'Omzet2023' => 'nullable|numeric|min:0',
                'Omzet2024' => 'nullable|numeric|min:0',
                'SHU2022' => 'nullable|numeric|min:0',
                'SHU2023' => 'nullable|numeric|min:0',
                'SHU2024' => 'nullable|numeric|min:0',
                'Laba2022' => 'nullable|numeric|min:0',
                'Laba2023' => 'nullable|numeric|min:0',
                'Laba2024' => 'nullable|numeric|min:0',
                'PotensiWisata' => 'nullable|string',
                'OVOP' => 'nullable|string|max:255',
                'Ketapang2025' => 'nullable|string|max:255',
                'DesaWisata' => 'nullable|string|max:255',
                'KontribusiPADesRP' => 'nullable|numeric|min:0',
                'KontribusiPADesPersen' => 'nullable|numeric|min:0|max:100',
                'PeranOVOP' => 'nullable|string',
                'PeranKetapang2025' => 'nullable|string',
                'PeranDesaWisata' => 'nullable|string',
                'BantuanKementrian' => 'nullable|string|max:255',
                'BantuanLaptopShopee' => 'nullable|string|max:255',
                'LaporanKeuangan' => 'nullable|string',
                // Produk hukum integration fields
                'produk_hukum_perdes_id' => 'nullable|uuid|exists:produk_hukums,id',
                'produk_hukum_sk_bumdes_id' => 'nullable|uuid|exists:produk_hukums,id',
            ]);

            // Cek apakah sudah ada data BUMDES untuk desa ini
            $existingBumdes = Bumdes::where('desa_id', $user->desa_id)->first();
            if ($existingBumdes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data BUMDES untuk desa ini sudah ada'
                ], 400);
            }

            // Tambahkan data desa dan status
            $validated['desa_id'] = $user->desa_id;
            $validated['upload_status'] = 'uploaded';

            $bumdes = Bumdes::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data BUMDES berhasil disimpan',
                'data' => $bumdes
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing desa BUMDES data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data BUMDES'
            ], 500);
        }
    }

    /**
     * Update BUMDES data from desa
     */
    public function updateDesaBumdes(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            $bumdes = Bumdes::where('id', $id)
                           ->where('desa_id', $user->desa_id)
                           ->first();

            if (!$bumdes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data BUMDES tidak ditemukan'
                ], 404);
            }

            // Validasi data (sama seperti store)
            $validated = $request->validate([
                'namabumdesa' => 'required|string|max:255',
                'desa' => 'nullable|string|max:255',
                'kecamatan' => 'nullable|string|max:255',
                'kode_desa' => 'nullable|string|max:50',
                'TahunPendirian' => 'nullable|integer|min:1900|max:' . date('Y'),
                'AlamatBumdes' => 'nullable|string',
                'NoHpBumdes' => 'nullable|string|max:20',
                'EmailBumdes' => 'nullable|email|max:255',
                'NoPerdes' => 'nullable|string|max:100',
                'TanggalPerdes' => 'nullable|date',
                'NoSKKemenkumham' => 'nullable|string|max:100',
                'TanggalSKKemenkumham' => 'nullable|date',
                'NamaPenasihat' => 'nullable|string|max:255',
                'NamaPengawas' => 'nullable|string|max:255',
                'NamaDirektur' => 'nullable|string|max:255',
                'NamaSekretaris' => 'nullable|string|max:255',
                'NamaBendahara' => 'nullable|string|max:255',
                'TotalTenagaKerja' => 'nullable|integer|min:0',
                'TenagaKerjaLaki' => 'nullable|integer|min:0',
                'TenagaKerjaPerempuan' => 'nullable|integer|min:0',
                'JenisUsaha' => 'nullable|string|max:255',
                'KelasUsaha' => 'nullable|string|max:100',
                'StatusUsaha' => 'nullable|string|max:100',
                'ModalAwal' => 'nullable|numeric|min:0',
                'ModalSekarang' => 'nullable|numeric|min:0',
                'Aset' => 'nullable|numeric|min:0',
                'KekayaanBersih' => 'nullable|numeric|min:0',
                'Omzet2022' => 'nullable|numeric|min:0',
                'Omzet2023' => 'nullable|numeric|min:0',
                'Omzet2024' => 'nullable|numeric|min:0',
                'SHU2022' => 'nullable|numeric|min:0',
                'SHU2023' => 'nullable|numeric|min:0',
                'SHU2024' => 'nullable|numeric|min:0',
                'Laba2022' => 'nullable|numeric|min:0',
                'Laba2023' => 'nullable|numeric|min:0',
                'Laba2024' => 'nullable|numeric|min:0',
                'PotensiWisata' => 'nullable|string',
                'OVOP' => 'nullable|string|max:255',
                'Ketapang2025' => 'nullable|string|max:255',
                'DesaWisata' => 'nullable|string|max:255',
                'KontribusiPADesRP' => 'nullable|numeric|min:0',
                'KontribusiPADesPersen' => 'nullable|numeric|min:0|max:100',
                'PeranOVOP' => 'nullable|string',
                'PeranKetapang2025' => 'nullable|string',
                'PeranDesaWisata' => 'nullable|string',
                'BantuanKementrian' => 'nullable|string|max:255',
                'BantuanLaptopShopee' => 'nullable|string|max:255',
                'LaporanKeuangan' => 'nullable|string',
                // Produk hukum integration fields
                'produk_hukum_perdes_id' => 'nullable|uuid|exists:produk_hukums,id',
                'produk_hukum_sk_bumdes_id' => 'nullable|uuid|exists:produk_hukums,id',
            ]);

            // Update data dan status
            $validated['upload_status'] = 'uploaded';
            $bumdes->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Data BUMDES berhasil diperbarui',
                'data' => $bumdes->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating desa BUMDES data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data BUMDES'
            ], 500);
        }
    }

    /**
     * Delete BUMDES data from desa
     */
    public function deleteDesaBumdes(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            $bumdes = Bumdes::where('id', $id)
                           ->where('desa_id', $user->desa_id)
                           ->first();

            if (!$bumdes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data BUMDES tidak ditemukan'
                ], 404);
            }

            $bumdes->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data BUMDES berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting desa BUMDES data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data BUMDES'
            ], 500);
        }
    }

    /**
     * Get BUMDES statistics for desa
     */
    public function getDesaBumdesStatistics(Request $request)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            $bumdes = Bumdes::where('desa_id', $user->desa_id)->first();

            if (!$bumdes) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'has_bumdes' => false,
                        'upload_status' => 'not_uploaded',
                        'completion_percentage' => 0
                    ]
                ]);
            }

            // Hitung persentase kelengkapan data
            $requiredFields = ['namabumdesa', 'TahunPendirian', 'JenisUsaha'];
            $filledFields = 0;
            $totalFields = count($requiredFields);

            foreach ($requiredFields as $field) {
                if (!empty($bumdes->$field)) {
                    $filledFields++;
                }
            }

            $completionPercentage = ($filledFields / $totalFields) * 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'has_bumdes' => true,
                    'upload_status' => $bumdes->upload_status ?? 'uploaded',
                    'completion_percentage' => round($completionPercentage, 2),
                    'nama_bumdes' => $bumdes->namabumdesa,
                    'jenis_usaha' => $bumdes->JenisUsaha,
                    'status_usaha' => $bumdes->StatusUsaha,
                    'modal_awal' => $bumdes->ModalAwal,
                    'total_tenaga_kerja' => $bumdes->TotalTenagaKerja,
                    'last_updated' => $bumdes->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting desa BUMDES statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil statistik BUMDES'
            ], 500);
        }
    }

    /**
     * Get produk hukum yang relevan untuk BUMDES (PERDES dan SK)
     */
    public function getProdukHukumForBumdes(Request $request)
    {
        try {
            $user = $request->user();
            
            // Pastikan user adalah desa
            if ($user->role !== 'desa' || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses tidak diizinkan'
                ], 403);
            }

            // Ambil produk hukum desa yang relevan untuk BUMDES
            $produkHukum = \App\Models\ProdukHukum::where('desa_id', $user->desa_id)
                ->where(function($query) {
                    // PERDES (Peraturan Desa)
                    $query->where('jenis', 'Peraturan Desa')
                          ->where(function($subQuery) {
                              $subQuery->where('judul', 'like', '%bumdes%')
                                       ->orWhere('judul', 'like', '%badan usaha milik desa%')
                                       ->orWhere('subjek', 'like', '%bumdes%')
                                       ->orWhere('subjek', 'like', '%badan usaha milik desa%');
                          });
                })
                ->orWhere(function($query) use ($user) {
                    // SK (Surat Keputusan)
                    $query->where('desa_id', $user->desa_id)
                          ->where('jenis', 'Keputusan Kepala Desa')
                          ->where(function($subQuery) {
                              $subQuery->where('judul', 'like', '%bumdes%')
                                       ->orWhere('judul', 'like', '%badan usaha milik desa%')
                                       ->orWhere('judul', 'like', '%pembentukan%')
                                       ->orWhere('subjek', 'like', '%bumdes%')
                                       ->orWhere('subjek', 'like', '%badan usaha milik desa%');
                          });
                })
                ->select('id', 'judul', 'nomor', 'tahun', 'jenis', 'singkatan_jenis', 'tanggal_penetapan', 'file')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'perdes' => $produkHukum->where('jenis', 'Peraturan Desa')->values(),
                    'sk_bumdes' => $produkHukum->where('jenis', 'Keputusan Kepala Desa')->values(),
                    'all' => $produkHukum
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting produk hukum for BUMDES: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data produk hukum'
            ], 500);
        }
    }
}