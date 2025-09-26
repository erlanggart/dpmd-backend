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
                'email' => 'staff.sekretariat@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'description' => 'Staff untuk input surat masuk'
            ],
            
            // Kepala Dinas
            [
                'name' => 'Kepala Dinas DPMD',
                'email' => 'kepala.dinas@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'kepala_dinas',
                'description' => 'Kepala Dinas untuk review dan disposisi surat'
            ],
            
            // Sekretaris Dinas
            [
                'name' => 'Sekretaris Dinas DPMD',
                'email' => 'sekretaris.dinas@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'sekretaris_dinas',
                'description' => 'Sekretaris Dinas untuk meneruskan disposisi'
            ],
            
            // Kepala Bidang Pemerintahan
            [
                'name' => 'Kepala Bidang Pemerintahan',
                'email' => 'kepala.pemerintahan@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'kepala_bidang_pemerintahan',
                'description' => 'Kepala Bidang Pemerintahan Desa'
            ],
            
            // Kepala Bidang Kesejahteraan Rakyat
            [
                'name' => 'Kepala Bidang Kesejahteraan Rakyat',
                'email' => 'kepala.kesra@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'kepala_bidang_kesra',
                'description' => 'Kepala Bidang Kesejahteraan Rakyat'
            ],
            
            // Kepala Bidang Ekonomi
            [
                'name' => 'Kepala Bidang Ekonomi',
                'email' => 'kepala.ekonomi@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'kepala_bidang_ekonomi',
                'description' => 'Kepala Bidang Ekonomi'
            ],
            
            // Kepala Bidang Fisik dan Prasarana
            [
                'name' => 'Kepala Bidang Fisik dan Prasarana',
                'email' => 'kepala.fisik@dpmd.com',
                'password' => Hash::make('password'),
                'role' => 'kepala_bidang_fisik',
                'description' => 'Kepala Bidang Fisik dan Prasarana'
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
        $this->command->info('=== AKUN DISPOSISI PERSURATAN ===');
        $this->command->info('Email: staff.sekretariat@dpmd.com | Password: password | Role: staff');
        $this->command->info('Email: kepala.dinas@dpmd.com | Password: password | Role: kepala_dinas');
        $this->command->info('Email: sekretaris.dinas@dpmd.com | Password: password | Role: sekretaris_dinas');
        $this->command->info('Email: kepala.pemerintahan@dpmd.com | Password: password | Role: kepala_bidang_pemerintahan');
        $this->command->info('Email: kepala.kesra@dpmd.com | Password: password | Role: kepala_bidang_kesra');
        $this->command->info('Email: kepala.ekonomi@dpmd.com | Password: password | Role: kepala_bidang_ekonomi');
        $this->command->info('Email: kepala.fisik@dpmd.com | Password: password | Role: kepala_bidang_fisik');
        $this->command->info('');
        $this->command->info('Semua akun disposisi persuratan berhasil dibuat!');
    }
}