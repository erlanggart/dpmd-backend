<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BidangUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data bidang dan users
        $bidangUsers = [
            [
                'name' => 'Admin Sekretariat',
                'email' => 'sekretariat@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'sekretariat',
            ],
            [
                'name' => 'Admin Sarana Prasarana',
                'email' => 'sarana@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'sarana_prasarana',
            ],
            [
                'name' => 'Admin Kekayaan Keuangan',
                'email' => 'keuangan@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'kekayaan_keuangan',
            ],
            [
                'name' => 'Admin Pemberdayaan Masyarakat',
                'email' => 'pemberdayaan@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'pemberdayaan_masyarakat',
            ],
            [
                'name' => 'Admin Pemerintahan Desa',
                'email' => 'pemerintahan@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'pemerintahan_desa',
            ],
        ];

        foreach ($bidangUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();

            if ($existingUser) {
                $this->command->warn("User {$userData['email']} sudah ada. Melewatkan pembuatan.");
                continue;
            }

            User::create($userData);
            $this->command->info("User {$userData['name']} berhasil dibuat dengan role {$userData['role']}");
        }

        $this->command->info('Semua user bidang berhasil dibuat!');
    }
}