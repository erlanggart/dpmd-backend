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
        // Tentukan path file JSON
        $jsonFilePath = base_path('desk_bumdes2025.json');

        // Baca dan decode data JSON
        if (!File::exists($jsonFilePath)) {
            $this->command->error("File JSON tidak ditemukan: {$jsonFilePath}");
            return;
        }

        $jsonData = File::get($jsonFilePath);
        $bumdesArray = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Error parsing JSON: " . json_last_error_msg());
            return;
        }

        if (!is_array($bumdesArray)) {
            $this->command->error("Data JSON tidak valid atau kosong.");
            return;
        }

        $totalData = count($bumdesArray);
        $this->command->info("Memproses {$totalData} data BUMDes dari file JSON...");

        // Tentukan kolom-kolom yang harus diubah menjadi integer
        $integerColumns = [
            'Omset2023', 'Laba2023', 'Omset2024', 'Laba2024',
            'PenyertaanModal2019', 'PenyertaanModal2020', 'PenyertaanModal2021',
            'PenyertaanModal2022', 'PenyertaanModal2023', 'PenyertaanModal2024',
            'SumberLain', 'NilaiAset', 'TotalTenagaKerja',
            'KontribusiTerhadapPADes2021', 'KontribusiTerhadapPADes2022', 'KontribusiTerhadapPADes2023', 'KontribusiTerhadapPADes2024',
        ];

        foreach ($bumdesArray as $index => $bumdesData) {
            // Generate kode_desa dari kombinasi kecamatan dan desa, atau gunakan index sebagai fallback
            $kode_desa = null;
            
            // Coba extract kode_desa dari desa field jika ada format "kode-nama"
            if (isset($bumdesData['desa']) && 
                $bumdesData['desa'] !== '-' && 
                $bumdesData['desa'] !== '' && 
                strpos($bumdesData['desa'], '-') !== false) {
                $parts = explode('-', $bumdesData['desa']);
                if (!empty($parts[0]) && is_numeric($parts[0])) {
                    $kode_desa = $parts[0];
                }
            }
            
            // Jika tidak ada kode_desa, generate berdasarkan kecamatan-desa atau index
            if (empty($kode_desa)) {
                if (!empty($bumdesData['kecamatan']) && !empty($bumdesData['desa'])) {
                    // Generate kode unik berdasarkan kombinasi kecamatan-desa
                    $kode_desa = substr(md5($bumdesData['kecamatan'] . '-' . $bumdesData['desa']), 0, 10);
                } else {
                    // Fallback menggunakan index + 1
                    $kode_desa = 'GEN' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
                }
            }

            // Skip jika tidak ada nama BUMDes (data tidak valid)
            if (empty($bumdesData['namabumdesa']) || trim($bumdesData['namabumdesa']) === '') {
                $this->command->warn("Melewatkan entri kosong pada index {$index} karena tidak memiliki nama BUMDes.");
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
            
            // Periksa duplikasi berdasarkan kombinasi kecamatan, desa, dan nama BUMDes
            $existingBumdes = Bumdes::where('kecamatan', $mappedData['kecamatan'])
                                   ->where('desa', $mappedData['desa'])
                                   ->where('namabumdesa', $mappedData['namabumdesa'])
                                   ->first();
            
            if ($existingBumdes) {
                $this->command->warn("Data BUMDes '{$mappedData['namabumdesa']}' di desa '{$mappedData['desa']}' sudah ada. Melewatkan pembuatan entri.");
                continue; // Lanjut ke iterasi berikutnya
            }

            // Filter data untuk memastikan tidak ada kunci kosong
            $cleanedData = array_filter($mappedData, function ($key) {
                return $key !== '';
            }, ARRAY_FILTER_USE_KEY);

            try {
                Bumdes::create($cleanedData);
                $this->command->info("✓ Berhasil menambah: {$mappedData['namabumdesa']} - {$mappedData['desa']}");
            } catch (\Exception $e) {
                $this->command->error("✗ Gagal menambah {$mappedData['namabumdesa']}: " . $e->getMessage());
            }
        }
        
        $finalCount = Bumdes::count();
        $this->command->info("Seeding selesai! Total data BUMDes di database: {$finalCount}");
    }
}
