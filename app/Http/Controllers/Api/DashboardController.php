<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function desaDashboardData()
    {
        $user = auth()->user();

        // Pastikan user memiliki desa yang terhubung
        if (!$user->desa) {
            return response()->json(['message' => 'User tidak terhubung dengan desa manapun.'], 404);
        }

        // Ambil data dari desa milik user yang login
        $desa = $user->desa;
        $jumlahAparatur = $desa->aparatur()->count();
        // Anda bisa menambahkan data lain di sini nanti (misal: jumlah dana, dll)

        return response()->json([
            'nama_desa' => $desa->nama,
            'nama_kecamatan' => $desa->kecamatan->nama,
            'jumlah_aparatur' => $jumlahAparatur,
            'anggaran_dana_desa_contoh' => 1200000000, // Contoh data
        ]);
    }
}
