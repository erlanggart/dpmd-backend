<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HeroGalleryController extends Controller
{
    // Method untuk landing page (publik)
    public function publicIndex()
    {
        return HeroGallery::where('is_active', true)
            ->orderBy('order')
            ->limit(6) // Batasi maksimal 6 foto
            ->get();
    }

    // Method untuk halaman admin (terlindungi)
    public function index()
    {
        return HeroGallery::orderBy('order')->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'title' => 'nullable|string',
            ]);

            $file = $request->file('image');

            // HENTIKAN SEMUA PROSES DAN TAMPILKAN INFO FILE DI SINI
            // dd($file);

            // Kode di bawah ini tidak akan dijalankan untuk sementara
            $path = $request->file('image')->store('hero-gallery', 'public_uploads');

            $gallery = HeroGallery::create([
                // Simpan hanya path relatif di dalam disk
                'image_path' => $path,
                'title' => $request->title,
            ]);

            Log::info('Upload berhasil, file disimpan di: ' . $path);

            return response()->json($gallery, 201);
        } catch (\Exception $e) {
            Log::error('GAGAL UPLOAD FILE: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat upload, periksa log.'], 500);
        }
    }

    public function update(Request $request, HeroGallery $heroGallery)
    {
        $validated = $request->validate([
            'title' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'order' => 'sometimes|integer',
        ]);

        $heroGallery->update($validated);

        return response()->json($heroGallery);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HeroGallery $heroGallery)
    {
        // Gunakan disk 'public_uploads' untuk menghapus file
        Storage::disk('public_uploads')->delete($heroGallery->image_path);

        $heroGallery->delete();
        return response()->noContent();
    }
}
