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
            $email = strtolower("desa.{$desaSlug}.{$kecamatanSlug}@dpmd.bogorkab.go.id");

            $userDesa = User::updateOrCreate(
                ['email' => $email], // Cari berdasarkan email
                [
                    'name' => 'Admin Desa ' . $desa->nama,
                    'password' => Hash::make('password'),
                    'role' => 'desa', // Role langsung tanpa spatie permission
                    'desa_id' => $desa->id,
                ]
            );
        }

        // --- BUAT AKUN ADMIN KECAMATAN ---
        $kecamatans = Kecamatan::all();
        foreach ($kecamatans as $kecamatan) {
            $userKecamatan = User::updateOrCreate(
                ['email' => strtolower('kecamatan.' . Str::slug($kecamatan->nama) . '@dpmd.bogorkab.go.id')],
                [
                    'name' => 'Admin Kecamatan ' . $kecamatan->nama,
                    'password' => Hash::make('password'),
                    'role' => 'kecamatan', // Role langsung tanpa spatie permission
                    'kecamatan_id' => $kecamatan->id, // Hubungkan ke ID kecamatan
                ]
            );
        }
    }
}
