<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DisposisiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data user untuk sistem disposisi persuratan
        $disposisiUsers = [
            // Staff Sekretariat (menggunakan admin sekretariat yang sudah ada)
            [
                'name' => 'Staff Sekretariat',
                'email' => 'staff.sekretariat@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'description' => 'Staff untuk input surat masuk'
            ],

            // Kepala Dinas
            [
                'name' => 'Kepala Dinas DPMD',
                'email' => 'kepala.dinas@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'kepala_dinas',
                'description' => 'Kepala Dinas untuk review dan disposisi surat'
            ],

            // Sekretaris Dinas
            [
                'name' => 'Sekretaris Dinas DPMD',
                'email' => 'sekretaris.dinas@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'sekretaris_dinas',
                'description' => 'Sekretaris Dinas untuk meneruskan disposisi'
            ],

            // Bidang Pemerintahan Desa
            [
                'name' => 'User Pemerintahan Desa',
                'email' => 'pemerintahan.desa@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'pemerintahan_desa',
                'description' => 'User Bidang Pemerintahan Desa'
            ],

            // Bidang Sarana Prasarana
            [
                'name' => 'User Sarana Prasarana',
                'email' => 'sarana.prasarana@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'sarana_prasarana',
                'description' => 'User Bidang Sarana Prasarana'
            ],

            // Bidang Kekayaan Keuangan
            [
                'name' => 'User Kekayaan Keuangan',
                'email' => 'kekayaan.keuangan@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'kekayaan_keuangan',
                'description' => 'User Bidang Kekayaan Keuangan'
            ],

            // Bidang Pemberdayaan Masyarakat
            [
                'name' => 'User Pemberdayaan Masyarakat',
                'email' => 'pemberdayaan.masyarakat@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'pemberdayaan_masyarakat',
                'description' => 'User Bidang Pemberdayaan Masyarakat'
            ],

            // Departemen Sekretariat
            [
                'name' => 'User Sekretariat',
                'email' => 'sekretariat@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'sekretariat',
                'description' => 'User Departemen Sekretariat'
            ],

            // Departemen Program dan Pelaporan
            [
                'name' => 'User Program dan Pelaporan',
                'email' => 'prolap@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'prolap',
                'description' => 'User Departemen Program dan Pelaporan'
            ],

            // Departemen Keuangan
            [
                'name' => 'User Keuangan',
                'email' => 'keuangan@dpmd.bogorkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'keuangan',
                'description' => 'User Departemen Keuangan'
            ],

        ];

        $this->command->info('Membuat akun untuk sistem disposisi persuratan...');

        foreach ($disposisiUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();

            if ($existingUser) {
                $this->command->warn("User {$userData['email']} sudah ada. Memperbarui data...");

                // Update existing user
                $existingUser->update([
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'password' => $userData['password']
                ]);

                $this->command->info("User {$userData['name']} berhasil diperbarui dengan role {$userData['role']}");
            } else {
                // Create new user
                User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'role' => $userData['role']
                ]);

                $this->command->info("User {$userData['name']} berhasil dibuat dengan role {$userData['role']}");
            }
        }

        $this->command->info('');
        $this->command->info('=== AKUN SISTEM DISPOSISI PERSURATAN ===');
        $this->command->info('=== TINGKAT DINAS ===');
        $this->command->info('Email: staff.sekretariat@dpmd.bogorkab.go.id | Password: password | Role: staff');
        $this->command->info('Email: kepala.dinas@dpmd.bogorkab.go.id | Password: password | Role: kepala_dinas');
        $this->command->info('Email: sekretaris.dinas@dpmd.bogorkab.go.id | Password: password | Role: sekretaris_dinas');
        $this->command->info('');
        $this->command->info('=== BIDANG-BIDANG DPMD ===');
        $this->command->info('Email: pemerintahan.desa@dpmd.bogorkab.go.id | Password: password | Role: pemerintahan_desa');
        $this->command->info('Email: sarana.prasarana@dpmd.bogorkab.go.id | Password: password | Role: sarana_prasarana');
        $this->command->info('Email: kekayaan.keuangan@dpmd.bogorkab.go.id | Password: password | Role: kekayaan_keuangan');
        $this->command->info('Email: pemberdayaan.masyarakat@dpmd.bogorkab.go.id | Password: password | Role: pemberdayaan_masyarakat');
        $this->command->info('');
        $this->command->info('=== DEPARTEMEN DPMD ===');
        $this->command->info('Email: sekretariat@dpmd.bogorkab.go.id | Password: password | Role: sekretariat');
        $this->command->info('Email: prolap@dpmd.bogorkab.go.id | Password: password | Role: prolap');
        $this->command->info('Email: keuangan@dpmd.bogorkab.go.id | Password: password | Role: keuangan');
        $this->command->info('');
        $this->command->info('Semua akun sistem disposisi persuratan berhasil dibuat!');
    }
}
