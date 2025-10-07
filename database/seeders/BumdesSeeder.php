<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bumdes;
use Illuminate\Support\Facades\Log;

class BumdesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai import data BUMDes...');
        
        // Baca file JSON
        $jsonPath = base_path('desk_bumdes2025.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("File desk_bumdes2025.json tidak ditemukan!");
            return;
        }
        
        $jsonData = file_get_contents($jsonPath);
        $bumdesData = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error("Error parsing JSON: " . json_last_error_msg());
            return;
        }
        
        $this->command->info("Total data BUMDes: " . count($bumdesData));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($bumdesData as $index => $item) {
            try {
                // Siapkan data untuk insert
                $bumdesInsertData = [
                'kecamatan' => $item['kecamatan'] ?? null,
                'desa' => $item['desa'] ?? null,
                'kode_desa' => $item['kode_desa'] ?? null,
                'namabumdesa' => $item['namabumdesa'] ?? null,
                    'status' => $item['status'] ?? null,
                    'keterangan_tidak_aktif' => $item['keterangan_tidak_aktif'] ?? null,
                    'NIB' => $item['NIB'] ?? null,
                    'LKPP' => $item['LKPP'] ?? null,
                    'NPWP' => $item['NPWP'] ?? null,
                    'badanhukum' => $item['badanhukum'] ?? null,
                    'NamaPenasihat' => $item['NamaPenasihat'] ?? null,
                    'JenisKelaminPenasihat' => $item['JenisKelaminPenasihat'] ?? null,
                    'HPPenasihat' => $item['HPPenasihat'] ?? null,
                    'NamaPengawas' => $item['NamaPengawas'] ?? null,
                    'JenisKelaminPengawas' => $item['JenisKelaminPengawas'] ?? null,
                    'HPPengawas' => $item['HPPengawas'] ?? null,
                    'NamaDirektur' => $item['NamaDirektur'] ?? null,
                    'JenisKelaminDirektur' => $item['JenisKelaminDirektur'] ?? null,
                    'HPDirektur' => $item['HPDirektur'] ?? null,
                    'NamaSekretaris' => $item['NamaSekretaris'] ?? null,
                    'JenisKelaminSekretaris' => $item['JenisKelaminSekretaris'] ?? null,
                    'HPSekretaris' => $item['HPSekretaris'] ?? null,
                    'NamaBendahara' => $item['NamaBendahara'] ?? null,
                    'JenisKelaminBendahara' => $item['JenisKelaminBendahara'] ?? null,
                    'HPBendahara' => $item['HPBendahara'] ?? null,
                    'TahunPendirian' => $item['TahunPendirian'] ?? null,
                    'AlamatBumdesa' => $item['AlamatBumdesa'] ?? null,
                    'Alamatemail' => $item['Alamatemail'] ?? null,
                    'TotalTenagaKerja' => $item['TotalTenagaKerja'] ?? null,
                    'TelfonBumdes' => $item['TelfonBumdes'] ?? null,
                    'JenisUsaha' => $item['JenisUsaha'] ?? null,
                    'JenisUsahaUtama' => $item['JenisUsahaUtama'] ?? null,
                    'JenisUsahaLainnya' => $item['JenisUsahaLainnya'] ?? null,
                    'Omset2021' => $this->parseNumericValue($item['Omset2021'] ?? null),
                    'Laba2021' => $this->parseNumericValue($item['Laba2021'] ?? null),
                    'Omset2022' => $this->parseNumericValue($item['Omset2022'] ?? null),
                    'Laba2022' => $this->parseNumericValue($item['Laba2022'] ?? null),
                    'Omset2023' => $this->parseNumericValue($item['Omset2023'] ?? null),
                    'Laba2023' => $this->parseNumericValue($item['Laba2023'] ?? null),
                    'Omset2024' => $this->parseNumericValue($item['Omset2024'] ?? null),
                    'Laba2024' => $this->parseNumericValue($item['Laba2024'] ?? null),
                    'PenyertaanModal2019' => $this->parseNumericValue($item['PenyertaanModal2019'] ?? null),
                    'PenyertaanModal2020' => $this->parseNumericValue($item['PenyertaanModal2020'] ?? null),
                    'PenyertaanModal2021' => $this->parseNumericValue($item['PenyertaanModal2021'] ?? null),
                    'PenyertaanModal2022' => $this->parseNumericValue($item['PenyertaanModal2022'] ?? null),
                    'PenyertaanModal2023' => $this->parseNumericValue($item['PenyertaanModal2023'] ?? null),
                    'PenyertaanModal2024' => $this->parseNumericValue($item['PenyertaanModal2024'] ?? null),
                    'SumberLain' => $this->parseNumericValue($item['SumberLain'] ?? null),
                    'JenisAset' => $item['JenisAset'] ?? null,
                    'NilaiAset' => $this->parseNumericValue($item['NilaiAset'] ?? null),
                    'KerjasamaPihakKetiga' => $item['KerjasamaPihakKetiga'] ?? null,
                    'KontribusiTerhadapPADes2021' => $this->parseNumericValue($item['KontribusiTerhadapPADes2021'] ?? null),
                    'KontribusiTerhadapPADes2022' => $this->parseNumericValue($item['KontribusiTerhadapPADes2022'] ?? null),
                    'KontribusiTerhadapPADes2023' => $this->parseNumericValue($item['KontribusiTerhadapPADes2023'] ?? null),
                    'KontribusiTerhadapPADes2024' => $this->parseNumericValue($item['KontribusiTerhadapPADes2024'] ?? null),
                    'Ketapang2024' => $item['Ketapang2024'] ?? null,
                    'Ketapang2025' => $item['Ketapang2025'] ?? null,
                    'DesaWisata' => $item['DesaWisata'] ?? null,
                    'BantuanKementrian' => $item['BantuanKementrian'] ?? null,
                    'BantuanLaptopShopee' => $item['BantuanLaptopShopee'] ?? null,
                    'NomorPerdes' => $item['NomorPerdes'] ?? null,
                    'TanggalPerdes' => $item['TanggalPerdes'] ?? null,
                    'NomorSK' => $item['NomorSK'] ?? null,
                    'TanggalSK' => $item['TanggalSK'] ?? null,
                ];
                
                // Handle laporan keuangan - ambil hanya nama file tanpa path
                if (isset($item['laporan_keuangan'])) {
                    $laporanKeuangan = $item['laporan_keuangan'];
                    $bumdesInsertData['LaporanKeuangan2021'] = $laporanKeuangan['LaporanKeuangan2021'] ?? null;
                    $bumdesInsertData['LaporanKeuangan2022'] = $laporanKeuangan['LaporanKeuangan2022'] ?? null;
                    $bumdesInsertData['LaporanKeuangan2023'] = $laporanKeuangan['LaporanKeuangan2023'] ?? null;
                    $bumdesInsertData['LaporanKeuangan2024'] = $laporanKeuangan['LaporanKeuangan2024'] ?? null;
                }
                
                // Create BUMDes record
                Bumdes::create($bumdesInsertData);
                
                $successCount++;
                
                if ($successCount % 10 == 0) {
                    $this->command->info("Berhasil import $successCount data BUMDes...");
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("Error importing BUMDes #{$index}: " . $e->getMessage());
                Log::error("BumdesSeeder Error", [
                    'index' => $index,
                    'data' => $item,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->command->info("=== SELESAI ===");
        $this->command->info("✅ Berhasil: $successCount");
        $this->command->info("❌ Error: $errorCount");
        $this->command->info("📊 Total: " . ($successCount + $errorCount));
    }
    
    private function parseNumericValue($value)
    {
        if (empty($value) || $value === '' || $value === '0') {
            return 0;
        }
        
        // Remove dots, commas, and other non-numeric characters except decimal point
        $cleaned = preg_replace('/[^\d.]/', '', str_replace(',', '.', $value));
        
        return is_numeric($cleaned) ? (float)$cleaned : 0;
    }
}
