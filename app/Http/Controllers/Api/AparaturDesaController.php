<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AparaturDesa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AparaturDesaController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Admin desa hanya bisa melihat aparatur di desanya sendiri
        if ($user->hasRole('admin desa')) {
            return $user->desa->aparatur()->latest()->get();
        }

        // TODO: Tambahkan logika untuk admin kecamatan & superadmin jika diperlukan
        // Contoh: Superadmin bisa melihat semua
        if ($user->hasRole('superadmin')) {
            return AparaturDesa::latest()->get();
        }

        return []; // Default, tidak mengembalikan apa-apa
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'kontak' => 'nullable|string',
        ]);

        // Ambil desa milik user yang sedang login
        $desa = auth()->user()->desa;

        // Buat aparatur baru yang terhubung langsung dengan desa tersebut
        $aparatur = $desa->aparatur()->create($validated);

        return response()->json($aparatur, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(AparaturDesa $aparaturDesa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AparaturDesa $aparaturDesa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AparaturDesa $aparaturDesa)
    {
        //
    }
}
