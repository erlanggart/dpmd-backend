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
            'email' => 'superadmin@dpmd.bogorkab.go.id',
            'password' => Hash::make('password'),
            'role' => 'superadmin'
        ]);
    }
}
