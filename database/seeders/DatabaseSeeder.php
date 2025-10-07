<?php

namespace Database\Seeders;

use App\Models\Perjadin\Kegiatan;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class, // <-- Panggil ini dulu
            UserSeeder::class,
            WilayahUserSeeder::class,
            BidangUserSeeder::class,
            DisposisiUserSeeder::class,
            BidangPerjadinSeeder::class,
            BumdesTableSeeder::class,
            KegiatanSeeder::class,
            MusdesusTargetDesaSeeder::class,
            PersonilSeeder::class
            // <-- Seeder untuk akun disposisi
        ]);
    }
}
