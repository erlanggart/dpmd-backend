<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;

class BidangPerjadinSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bidangs = [
            ['nama' => 'Sekretariat'],
            ['nama' => 'Sarana Prasarana Kewilayahan dan Ekonomi Desa'],
            ['nama' => 'Kekayaan dan Keuangan Desa'],
            ['nama' => 'Pemberdayaan Masyarakat Desa'],
            ['nama' => 'Pemerintahan Desa'],
            ['nama' => 'Tenaga Alih Daya'],
            ['nama' => 'Tenaga Keamanan'],
            ['nama' => 'Tenaga Kebersihan'],
        ];

        foreach ($bidangs as $bidang) {
            Bidang::firstOrCreate($bidang);
        }
    }
}
