<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfilDesaController extends Controller
{
    // Mengambil data profil desa milik user yang login
    public function show()
    {
        $user = auth()->user();
        
        // Jika user adalah desa, ambil profil desa mereka
        if ($user->role === 'desa' && $user->desa) {
            $desa = $user->desa;
            $profil = $desa->profil()->firstOrNew([]);
            return response()->json($profil);
        }
        
        // Jika user adalah admin, return data kosong atau template
        if (in_array($user->role, ['superadmin', 'sekretariat', 'sarana_prasarana', 'kekayaan_keuangan', 'pemberdayaan_masyarakat', 'pemerintahan_desa'])) {
            return response()->json([
                'klasifikasi_desa' => null,
                'status_desa' => null,
                'tipologi_desa' => null,
                'jumlah_penduduk' => null,
                'sejarah_desa' => null,
                'demografi' => null,
                'potensi_desa' => null,
                'no_telp' => null,
                'email' => null,
                'instagram_url' => null,
                'youtube_url' => null,
                'luas_wilayah' => null,
                'alamat_kantor' => null,
                'radius_ke_kecamatan' => null,
                'latitude' => null,
                'longitude' => null,
                'foto_kantor_desa_path' => null,
                'message' => 'Admin view - select desa to edit profil'
            ]);
        }
        
        return response()->json(['message' => 'No desa associated with this user'], 404);
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

        $user = auth()->user();
        
        // Untuk admin yang tidak punya relasi desa, ambil desa_id dari request
        if ($user->role === 'superadmin' || $user->role === 'admin' || str_contains($user->role, 'bidang')) {
            $validated['desa_id'] = $request->desa_id; // pastikan desa_id dikirim dari frontend
            $desa = \App\Models\Desa::findOrFail($request->desa_id);
        } else {
            $desa = $user->desa;
        }

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
