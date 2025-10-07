<?php

namespace Database\Seeders;

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
            RoleSeeder::class,
            MasterDataSeeder::class, // <-- Panggil ini dulu
            UserSeeder::class,
            WilayahUserSeeder::class,
            BidangUserSeeder::class,
            DisposisiUserSeeder::class, // <-- Seeder untuk akun disposisi
            BidangPerjadinSeeder::class,
            BumdesTableSeeder::class,
            PersonilSeeder::class,

        ]);
    }
}
