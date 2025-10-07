<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HeroGalleryController extends Controller
{
    // Method untuk landing page (publik)
    public function publicIndex()
    {
        try {
            $galleries = HeroGallery::where('is_active', true)
                ->orderBy('order')
                ->limit(6) // Batasi maksimal 6 foto
                ->get();
            
            return response()->json($galleries);
        } catch (\Exception $e) {
            // Jika ada error, return array kosong agar frontend tidak error
            return response()->json([]);
        }
    }

    // Method untuk halaman admin (terlindungi)
    public function index()
    {
        return HeroGallery::orderBy('order')->get();
    }

    public function store(Request $request)
    {

        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            Log::error('UPLOAD GAGAL: Tidak ada file gambar atau file tidak valid.');
            return response()->json(['message' => 'Tidak ada file gambar yang valid dalam request.'], 400);
        }

        try {
            // Validasi request
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'title' => 'nullable|string',
            ]);

            $file = $request->file('image');

            // Menggunakan disk 'public_uploads' yang menyimpan ke public/uploads
            $path = $file->store('hero-gallery', 'public_uploads');


            if (!$path) {
                Log::error('UPLOAD GAGAL: Fungsi store() mengembalikan path yang tidak valid.');
                return response()->json(['message' => 'Gagal menyimpan file ke disk.'], 500);
            }

            $gallery = HeroGallery::create([
                'image_path' => $path, // Simpan path relatif: 'hero-gallery/namafile.jpg'
                'title' => $request->title,
            ]);

            Log::info('UPLOAD BERHASIL: File disimpan di disk public_uploads dengan path: ' . $path);

            return response()->json($gallery, 201);
        } catch (ValidationException $e) {

            Log::error('UPLOAD GAGAL - ERROR VALIDASI: ', $e->errors());
            // Mengembalikan error 422 dengan detail validasi
            return response()->json(['message' => 'Data yang diberikan tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {

            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            Log::error('UPLOAD GAGAL - EXCEPTION UMUM: ' . $errorMessage . ' (Code: ' . $errorCode . ')');

            // Berikan pesan error yang lebih spesifik saat mode debug/local
            $responseMessage = 'Terjadi kesalahan saat mengupload file.';
            if (app()->environment('local')) {
                $responseMessage = $errorMessage;
            }

            return response()->json(['message' => $responseMessage], 500);
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
