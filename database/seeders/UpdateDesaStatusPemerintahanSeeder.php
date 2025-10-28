<?php

namespace Database\Seeders;

use App\Models\Desa;
use Illuminate\Database\Seeder;

class UpdateDesaStatusPemerintahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update semua desa yang sudah ada dengan status 'desa' sebagai default
        // List kelurahan berdasarkan kecamatan dan nama untuk akurasi yang lebih baik

        $kelurahanList = [
            // Kecamatan Cibinong
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'PONDOK RAJEG'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'KARADENAN'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'HARAPAN JAYA'], // Fixed: Harapan Jaya (bukan Harapanjaya)
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'NANGGEWER'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'Nanggewer Mekar'], // Fixed: Nanggewer Mekar (bukan NANGGEWER MEKAR)
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'CIBINONG'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'PAKANSARI'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'TENGAH'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'SUKAHATI'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'CIRIUNG'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'CIRIMEKAR'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'PABUARAN'],
            ['kecamatan' => 'CIBINONG', 'kelurahan' => 'Pabuaran Mekar'], // Fixed: Pabuaran Mekar (bukan PABUARAN MEKAR)

            // Kecamatan Citeureup
            ['kecamatan' => 'CITEUREUP', 'kelurahan' => 'PUSPANEGARA'],
            ['kecamatan' => 'CITEUREUP', 'kelurahan' => 'KARANG ASEM BARAT'],

            // Kecamatan Kemang
            ['kecamatan' => 'KEMANG', 'kelurahan' => 'ATANG SENJAYA'],

            // Kecamatan Bojong Gede
            ['kecamatan' => 'BOJONG GEDE', 'kelurahan' => 'PABUARAN'],

            // Kecamatan Cisarua
            ['kecamatan' => 'CISARUA', 'kelurahan' => 'CISARUA'],

            // Kecamatan Ciomas
            ['kecamatan' => 'CIOMAS', 'kelurahan' => 'PADASUKA'],
        ];

        // Set semua desa dengan status 'desa' terlebih dahulu
        Desa::whereNull('status_pemerintahan')
            ->orWhere('status_pemerintahan', '')
            ->update(['status_pemerintahan' => 'desa']);

        // Update desa yang merupakan kelurahan berdasarkan kecamatan dan nama
        foreach ($kelurahanList as $kelurahan) {
            $updated = Desa::whereHas('kecamatan', function ($query) use ($kelurahan) {
                $query->where('nama', 'LIKE', "%{$kelurahan['kecamatan']}%");
            })
                ->where('nama', 'LIKE', "%{$kelurahan['kelurahan']}%")
                ->update(['status_pemerintahan' => 'kelurahan']);

            if ($updated > 0) {
                $this->command->info("Updated {$kelurahan['kelurahan']} di Kecamatan {$kelurahan['kecamatan']} menjadi kelurahan");
            } else {
                $this->command->warn("Tidak ditemukan {$kelurahan['kelurahan']} di Kecamatan {$kelurahan['kecamatan']}");
            }
        }

        $totalKelurahan = Desa::where('status_pemerintahan', 'kelurahan')->count();
        $totalDesa = Desa::where('status_pemerintahan', 'desa')->count();

        $this->command->info("Status pemerintahan berhasil diupdate!");
        $this->command->info("Total Kelurahan: {$totalKelurahan}");
        $this->command->info("Total Desa: {$totalDesa}");
    }
}
