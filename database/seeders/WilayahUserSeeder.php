<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WilayahUserSeeder extends Seeder
{
    public function run(): void
    {
        // --- BUAT AKUN ADMIN DESA ---
        // Kita butuh relasi 'kecamatan' untuk mendapatkan namanya
        $desas = Desa::with('kecamatan')->get();

        foreach ($desas as $desa) {
            // Membuat slug yang aman untuk URL/email
            $desaSlug = Str::slug($desa->nama);
            $kecamatanSlug = Str::slug($desa->kecamatan->nama); // Ambil slug dari nama kecamatan

            // Gabungkan untuk membuat email yang unik
            $email = strtolower("desa.{$desaSlug}.{$kecamatanSlug}@dpmd.com");

            $userDesa = User::create([
                'name' => 'Admin Desa ' . $desa->nama,
                'email' => $email, // <-- Gunakan email baru yang unik
                'password' => Hash::make('password'),
                'desa_id' => $desa->id,
            ]);
            $userDesa->assignRole('admin desa');
        }

        // --- BUAT AKUN ADMIN KECAMATAN ---
        $kecamatans = Kecamatan::all();
        foreach ($kecamatans as $kecamatan) {
            $userKecamatan = User::create([
                'name' => 'Admin Kecamatan ' . $kecamatan->nama,
                'email' => strtolower('kecamatan.' . Str::slug($kecamatan->nama) . '@dpmd.com'),
                'password' => Hash::make('password'),
                'kecamatan_id' => $kecamatan->id, // Hubungkan ke ID kecamatan
            ]);
            $userKecamatan->assignRole('admin kecamatan');
        }
    }
}
