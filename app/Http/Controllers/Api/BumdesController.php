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
            // Validasi data masukan
            $validatedData = $request->validate([
                'kode_desa' => ['required', 'string', 'unique:bumdes,kode_desa'],
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
            // Validasi data masukan
            $validatedData = $request->validate([
                'kode_desa' => ['nullable', 'string', Rule::unique('bumdes', 'kode_desa')->ignore($bumdes->id)],
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
    public function destroy(Bumdes $bumdes)
    {
        $fileFields = [
            'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
            'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
            'ProgramKerja', 'SK_BUM_Desa'
        ];
        
        foreach ($fileFields as $field) {
            if ($bumdes->$field && Storage::disk('public')->exists($bumdes->$field)) {
                Storage::disk('public')->delete($bumdes->$field);
            }
        }
        
        $bumdes->delete();

        return response()->json(['message' => 'Data BUMDes berhasil dihapus.'], 200);
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
}