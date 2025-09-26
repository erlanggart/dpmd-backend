<?php

namespace Database\Seeders;

use App\Models\Bumdes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BumdesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Tentukan path ke file JSON dari root proyek
        $jsonPath = base_path('desk_bumdes2025.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File desk_bumdes2025.json tidak ditemukan! Pastikan file ini berada di root proyek Anda.");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->command->error("Data JSON tidak valid atau kosong.");
            return;
        }

        // Tentukan kolom-kolom yang harus diubah menjadi integer
        $integerColumns = [
            'Omset2023', 'Laba2023', 'Omset2024', 'Laba2024',
            'PenyertaanModal2019', 'PenyertaanModal2020', 'PenyertaanModal2021',
            'PenyertaanModal2022', 'PenyertaanModal2023', 'PenyertaanModal2024',
            'SumberLain', 'NilaiAset', 'TotalTenagaKerja',
            'KontribusiTerhadapPADes2021', 'KontribusiTerhadapPADes2022', 'KontribusiTerhadapPADes2023', 'KontribusiTerhadapPADes2024',
        ];

        foreach ($data as $bumdesData) {
            // Extract kode_desa from desa field (format: "3201112001-CIDOKOM")
            $kode_desa = null;
            if (isset($bumdesData['desa']) && 
                $bumdesData['desa'] !== '-' && 
                $bumdesData['desa'] !== '' && 
                strpos($bumdesData['desa'], '-') !== false) {
                $parts = explode('-', $bumdesData['desa']);
                if (!empty($parts[0]) && is_numeric($parts[0])) {
                    $kode_desa = $parts[0];
                }
            }

            // Skip entries without valid kode_desa
            if (empty($kode_desa)) {
                $this->command->warn("Melewatkan entri dengan desa '{$bumdesData['desa']}' karena tidak memiliki kode desa yang valid.");
                continue;
            }

            // Mapping kunci JSON (PascalCase/UPPERCASE) ke nama kolom database yang sesuai.
            $mappedData = [
                'kode_desa' => $kode_desa,
                'kecamatan' => $bumdesData['kecamatan'] ?? null,
                'desa' => $bumdesData['desa'] ?? null,
                'namabumdesa' => $bumdesData['namabumdesa'] ?? null,
                'status' => $bumdesData['status'] ?? null,
                'keterangan_tidak_aktif' => $bumdesData['keterangan_tidak_aktif'] ?? null,
                'NIB' => $bumdesData['NIB'] ?? null,
                'LKPP' => $bumdesData['LKPP'] ?? null,
                'NPWP' => $bumdesData['NPWP'] ?? null,
                'badanhukum' => $bumdesData['badanhukum'] ?? null,
                'NamaPenasihat' => $bumdesData['NamaPenasihat'] ?? null,
                'JenisKelaminPenasihat' => $bumdesData['JenisKelaminPenasihat'] ?? null,
                'HPPenasihat' => $bumdesData['HPPenasihat'] ?? null,
                'NamaPengawas' => $bumdesData['NamaPengawas'] ?? null,
                'JenisKelaminPengawas' => $bumdesData['JenisKelaminPengawas'] ?? null,
                'HPPengawas' => $bumdesData['HPPengawas'] ?? null,
                'NamaDirektur' => $bumdesData['NamaDirektur'] ?? null,
                'JenisKelaminDirektur' => $bumdesData['JenisKelaminDirektur'] ?? null,
                'HPDirektur' => $bumdesData['HPDirektur'] ?? null,
                'NamaSekretaris' => $bumdesData['NamaSekretaris'] ?? null,
                'JenisKelaminSekretaris' => $bumdesData['JenisKelaminSekretaris'] ?? null,
                'HPSekretaris' => $bumdesData['HPSekretaris'] ?? null,
                'NamaBendahara' => $bumdesData['NamaBendahara'] ?? null,
                'JenisKelaminBendahara' => $bumdesData['JenisKelaminBendahara'] ?? null,
                'HPBendahara' => $bumdesData['HPBendahara'] ?? null,
                'TahunPendirian' => $bumdesData['TahunPendirian'] ?? null,
                'AlamatBumdesa' => $bumdesData['AlamatBumdesa'] ?? null,
                'Alamatemail' => $bumdesData['Alamatemail'] ?? null,
                'TotalTenagaKerja' => $bumdesData['TotalTenagaKerja'] ?? null,
                'TelfonBumdes' => $bumdesData['TelfonBumdes'] ?? null,
                'JenisUsaha' => $bumdesData['JenisUsaha'] ?? null,
                'JenisUsahaUtama' => $bumdesData['JenisUsahaUtama'] ?? null,
                'JenisUsahaLainnya' => $bumdesData['JenisUsahaLainnya'] ?? null,
                'Omset2023' => $bumdesData['Omset2023'] ?? null,
                'Laba2023' => $bumdesData['Laba2023'] ?? null,
                'Omset2024' => $bumdesData['Omset2024'] ?? null,
                'Laba2024' => $bumdesData['Laba2024'] ?? null,
                'PenyertaanModal2019' => $bumdesData['PenyertaanModal2019'] ?? null,
                'PenyertaanModal2020' => $bumdesData['PenyertaanModal2020'] ?? null,
                'PenyertaanModal2021' => $bumdesData['PenyertaanModal2021'] ?? null,
                'PenyertaanModal2022' => $bumdesData['PenyertaanModal2022'] ?? null,
                'PenyertaanModal2023' => $bumdesData['PenyertaanModal2023'] ?? null,
                'PenyertaanModal2024' => $bumdesData['PenyertaanModal2024'] ?? null,
                'SumberLain' => $bumdesData['SumberLain'] ?? null,
                'JenisAset' => $bumdesData['JenisAset'] ?? null,
                'NilaiAset' => $bumdesData['NilaiAset'] ?? null,
                'KerjasamaPihakKetiga' => $bumdesData['KerjasamaPihakKetiga'] ?? null,
                'TahunMulai-TahunBerakhir' => $bumdesData['TahunMulai-TahunBerakhir'] ?? null,
                'KontribusiTerhadapPADes2021' => $bumdesData['KontribusiTerhadapPADes2021'] ?? null,
                'KontribusiTerhadapPADes2022' => $bumdesData['KontribusiTerhadapPADes2022'] ?? null,
                'KontribusiTerhadapPADes2023' => $bumdesData['KontribusiTerhadapPADes2023'] ?? null,
                'KontribusiTerhadapPADes2024' => $bumdesData['KontribusiTerhadapPADes2024'] ?? null,
                'Ketapang2024' => $bumdesData['Ketapang2024'] ?? null,
                'Ketapang2025' => $bumdesData['Ketapang2025'] ?? null,
                // Kolom baru
                'DesaWisata' => $bumdesData['DesaWisata'] ?? null,
                'BantuanKementrian' => $bumdesData['BantuanKementrian'] ?? null,
                'BantuanLaptopShopee' => $bumdesData['BantuanLaptopShopee'] ?? null,
                'NomorPerdes' => $bumdesData['NomorPerdes'] ?? null,
                // Mengambil data dari objek bersarang
                'LaporanKeuangan2021' => $bumdesData['laporan_keuangan']['LaporanKeuangan2021'] ?? null,
                'Perdes' => $bumdesData['dokumen_badanhukum']['Perdes'] ?? null,
                'ProfilBUMDesa' => $bumdesData['dokumen_badanhukum']['ProfilBUMDesa'] ?? null,
                'BeritaAcara' => $bumdesData['dokumen_badanhukum']['BeritaAcara'] ?? null,
                'AnggaranDasar' => $bumdesData['dokumen_badanhukum']['AnggaranDasar'] ?? null,
                'AnggaranRumahTangga' => $bumdesData['dokumen_badanhukum']['AnggaranRumahTangga'] ?? null,
                'ProgramKerja' => $bumdesData['dokumen_badanhukum']['ProgramKerja'] ?? null,
                'SK_BUM_Desa' => $bumdesData['dokumen_badanhukum']['SK_BUM_Desa'] ?? null,
            ];

            // Bersihkan data dari titik dan karakter non-digit untuk kolom integer
            foreach ($integerColumns as $column) {
                if (isset($mappedData[$column]) && is_string($mappedData[$column])) {
                    $cleanedValue = preg_replace('/\D/', '', $mappedData[$column]);
                    $mappedData[$column] = (int) $cleanedValue;
                }
            }

            // Ekstraksi tahun dari string 'TahunPendirian'
            if (isset($mappedData['TahunPendirian']) && is_string($mappedData['TahunPendirian'])) {
                if (preg_match('/(\d{4})/', $mappedData['TahunPendirian'], $matches)) {
                    $mappedData['TahunPendirian'] = (int) $matches[1];
                } else {
                    $mappedData['TahunPendirian'] = null; // Tetapkan null jika tidak ditemukan
                }
            }
            
            // Periksa apakah data dengan 'kode_desa' yang sama sudah ada sebelum membuat
            if (Bumdes::where('kode_desa', $kode_desa)->exists()) {
                $this->command->warn("Data untuk kode desa '{$kode_desa}' sudah ada. Melewatkan pembuatan entri.");
                continue; // Lanjut ke iterasi berikutnya
            }

            // Filter data untuk memastikan tidak ada kunci kosong
            $cleanedData = array_filter($mappedData, function ($key) {
                return $key !== '';
            }, ARRAY_FILTER_USE_KEY);

            Bumdes::create($cleanedData);
        }
        
        $this->command->info('Data BUMDes berhasil dimasukkan dari file JSON!');
    }
}
