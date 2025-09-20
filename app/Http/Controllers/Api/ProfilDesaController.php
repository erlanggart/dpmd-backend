<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfilDesaController extends Controller
{
    // Mengambil data profil desa milik user yang login
    public function show()
    {
        $desa = auth()->user()->desa;
        // findOrNew mengembalikan profil yang ada atau objek baru jika belum ada
        $profil = $desa->profil()->firstOrNew([]);
        return response()->json($profil);
    }

    // Menyimpan atau memperbarui data profil desa
    public function store(Request $request)
    {
        // TAMBAHKAN SEMUA ATURAN VALIDASI DI SINI
        $validated = $request->validate([
            'klasifikasi_desa' => 'nullable|string|max:255',
            'status_desa' => 'nullable|string|max:255',
            'tipologi_desa' => 'nullable|string|max:255',
            'jumlah_penduduk' => 'nullable|integer',
            'sejarah_desa' => 'nullable|string',
            'demografi' => 'nullable|string',
            'potensi_desa' => 'nullable|string',
            'no_telp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'luas_wilayah' => 'nullable|string|max:255',
            'alamat_kantor' => 'nullable|string',
            'radius_ke_kecamatan' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $desa = auth()->user()->desa;

        if ($request->hasFile('foto_kantor_desa')) {
            // Validasi file secara terpisah jika ada
            $request->validate(['foto_kantor_desa' => 'image|mimes:jpeg,png,jpg,webp|max:2048']);
            $path = $request->file('foto_kantor_desa')->store('profil-desa', 'public_uploads');
            $validated['foto_kantor_desa_path'] = $path;
        }

        $desa->profil()->updateOrCreate(['desa_id' => $desa->id], $validated);

        return response()->json(['message' => 'Profil desa berhasil diperbarui.']);
    }
}
