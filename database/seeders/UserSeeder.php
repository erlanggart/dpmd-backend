<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@dpmd.com',
            'password' => Hash::make('password')
        ]);
        $superadmin->assignRole('superadmin');

        // 2. Admin Bidang
        $adminBidang = User::create([
            'name' => 'Admin Bidang Pemberdayaan',
            'email' => 'bidang@dpmd.com',
            'password' => Hash::make('password')
        ]);
        $adminBidang->assignRole('admin bidang');


        // 5. Admin Dinas Terkait
        $adminDinas = User::create([
            'name' => 'Admin Dinas Kominfo',
            'email' => 'dinas@dpmd.com',
            'password' => Hash::make('password')
        ]);
        $adminDinas->assignRole('admin dinas');
    }
}
