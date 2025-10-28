<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Desa;
use App\Models\Kecamatan;

class MusdesusTargetDesaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar 37 desa yang wajib upload musdesus
        $targetDesas = [
            ['desa' => 'Cipayung Girang', 'kecamatan' => 'Megamendung'],
            ['desa' => 'Cariu', 'kecamatan' => 'Cariu'],
            ['desa' => 'Bantarkuning', 'kecamatan' => 'Cariu'],
            ['desa' => 'Cihideung Udik', 'kecamatan' => 'Ciampea'],
            ['desa' => 'Cimanggu I', 'kecamatan' => 'Cibungbulang'],
            ['desa' => 'Galuga', 'kecamatan' => 'Cibungbulang'],
            ['desa' => 'Tugujaya', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Ciburuy', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Ciburayut', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Ciadeg', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Cigombong', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Watesjaya', 'kecamatan' => 'Cigombong'],
            ['desa' => 'Tajurhalang', 'kecamatan' => 'Cijeruk'],
            ['desa' => 'Cijeruk', 'kecamatan' => 'Cijeruk'],
            ['desa' => 'Pasirangin', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Limusnunggal', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Gandoang', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Cipeucang', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Jatisari', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Situsari', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Mekarsari', 'kecamatan' => 'Cileungsi'],
            ['desa' => 'Kota Batu', 'kecamatan' => 'Ciomas'],
            ['desa' => 'Kopo', 'kecamatan' => 'Cisarua'],
            ['desa' => 'Citeko', 'kecamatan' => 'Cisarua'],
            ['desa' => 'Cilember', 'kecamatan' => 'Cisarua'],
            ['desa' => 'Jogjogan', 'kecamatan' => 'Cisarua'],
            ['desa' => 'Kalongsawah', 'kecamatan' => 'Jasinga'],
            ['desa' => 'Sipak', 'kecamatan' => 'Jasinga'],
            ['desa' => 'Setu', 'kecamatan' => 'Jasinga'],
            ['desa' => 'Jasinga', 'kecamatan' => 'Jasinga'],
            ['desa' => 'Lulut', 'kecamatan' => 'Klapanunggal'],
            ['desa' => 'Karacak', 'kecamatan' => 'Leuwiliang'],
            ['desa' => 'Leuwiliang', 'kecamatan' => 'Leuwiliang'],
            ['desa' => 'Ciasmara', 'kecamatan' => 'Pamijahan'],
            ['desa' => 'Lumpang', 'kecamatan' => 'Parung Panjang'],
            ['desa' => 'Parung Panjang', 'kecamatan' => 'Parung Panjang'],
            ['desa' => 'Bantarjaya', 'kecamatan' => 'Rancabungur']
        ];

        echo "Menandai desa-desa target musdesus...\n";

        // Reset semua desa menjadi bukan target
        Desa::query()->update(['is_musdesus_target' => false]);

        $found = 0;
        $notFound = [];

        foreach ($targetDesas as $target) {
            // Cari kecamatan
            $kecamatan = Kecamatan::where('nama', 'LIKE', "%{$target['kecamatan']}%")->first();
            
            if (!$kecamatan) {
                $notFound[] = "Kecamatan '{$target['kecamatan']}' tidak ditemukan";
                continue;
            }

            // Cari desa dalam kecamatan tersebut
            $desa = Desa::where('kecamatan_id', $kecamatan->id)
                        ->where('nama', 'LIKE', "%{$target['desa']}%")
                        ->first();

            if ($desa) {
                $desa->update(['is_musdesus_target' => true]);
                echo "✓ {$target['desa']}, {$target['kecamatan']}\n";
                $found++;
            } else {
                $notFound[] = "Desa '{$target['desa']}' di kecamatan '{$target['kecamatan']}' tidak ditemukan";
            }
        }

        echo "\n=== HASIL SEEDER ===\n";
        echo "Desa target yang berhasil ditandai: {$found} dari " . count($targetDesas) . "\n";
        
        if (!empty($notFound)) {
            echo "\nDesa yang tidak ditemukan:\n";
            foreach ($notFound as $item) {
                echo "- {$item}\n";
            }
        }

        echo "\nSeeder selesai!\n";
    }
}
