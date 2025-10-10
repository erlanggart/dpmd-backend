<?php

namespace App\Http\Controllers\Api\Desa;

use App\Models\ProdukHukum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProdukHukumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ProdukHukum::with('desa.kecamatan')->where('desa_id', $user->desa_id);

        // Cek jika ada parameter pencarian 'search'
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where('judul', 'like', '%' . $searchTerm . '%');
        }

        // Jika parameter 'all' ada, return semua data tanpa pagination
        if ($request->has('all') && $request->all) {
            $produkHukums = $query->latest()->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar Produk Hukum',
                'data' => $produkHukums
            ]);
        }

        $produkHukums = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Daftar Produk Hukum',
            'data' => $produkHukums
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'nomor' => 'required|string|max:255',
            'tahun' => 'required|digits:4',
            'jenis' => 'required|in:Peraturan Desa,Peraturan Kepala Desa,Keputusan Kepala Desa',
            'singkatan_jenis' => 'required|in:PERDES,PERKADES,SK KADES',
            'tempat_penetapan' => 'required|string|max:255',
            'tanggal_penetapan' => 'required|date',
            'sumber' => 'nullable|string|max:255',
            'subjek' => 'nullable|string|max:255',
            'status_peraturan' => 'required|in:berlaku,dicabut',
            'keterangan_status' => 'nullable|string|max:255',
            'file' => 'required|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('file');
        // Simpan file ke disk 'public_uploads' di dalam folder 'produk_hukum'
        $path = $file->store('produk_hukum', 'public_uploads');

        // Dapatkan hanya nama file dari path yang dihasilkan
        $fileName = basename($path);

        $produkHukum = ProdukHukum::create(array_merge($request->except(['file', 'id']), [
            'desa_id' => $user->desa_id,
            'file' => $fileName, // Simpan hanya nama file
        ]));

        if ($produkHukum) {
            return response()->json([
                'success' => true,
                'message' => 'Produk Hukum berhasil ditambahkan',
                'data' => $produkHukum
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk Hukum gagal ditambahkan',
        ], 409);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $produkHukum = ProdukHukum::with('desa.kecamatan')
            ->where('id', $id)->first();

        if ($produkHukum) {
            return response()->json([
                'success' => true,
                'message' => 'Detail Produk Hukum',
                'data' => $produkHukum
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk Hukum tidak ditemukan',
        ], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $produkHukum = ProdukHukum::where('id', $id)->first();

        if (!$produkHukum) {
            return response()->json([
                'success' => false,
                'message' => 'Produk Hukum tidak ditemukan',
            ], 404);
        }

        // 1. Validasi data teks terlebih dahulu
        $textDataValidator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'nomor' => 'required|string|max:255',
            'tahun' => 'required|digits:4',
            'jenis' => 'required|in:Peraturan Desa,Peraturan Kepala Desa,Keputusan Kepala Desa',
            'singkatan_jenis' => 'required|in:PERDES,PERKADES,SK KADES',
            'tempat_penetapan' => 'required|string|max:255',
            'tanggal_penetapan' => 'required|date',
            'sumber' => 'nullable|string|max:255',
            'subjek' => 'nullable|string|max:255',
            'status_peraturan' => 'required|in:berlaku,dicabut',
            'keterangan_status' => 'nullable|string|max:255',
        ]);

        if ($textDataValidator->fails()) {
            return response()->json($textDataValidator->errors(), 422);
        }

        // Ambil semua data yang tervalidasi kecuali file
        $updateData = $textDataValidator->validated();

        // 2. Cek dan proses file jika ada
        if ($request->hasFile('file')) {
            // Validasi file secara terpisah
            $fileValidator = Validator::make($request->all(), [
                'file' => 'required|mimes:pdf|max:10240',
            ]);

            if ($fileValidator->fails()) {
                return response()->json($fileValidator->errors(), 422);
            }

            // Hapus file lama
            if ($produkHukum->file) {
                Storage::disk('public_uploads')->delete('produk_hukum/' . $produkHukum->file);
            }

            // Simpan file baru
            $file = $request->file('file');
            $path = $file->store('produk_hukum', 'public_uploads');

            // Tambahkan nama file baru ke data update
            $updateData['file'] = basename($path);
        }

        // 3. Update database dengan data yang sudah disiapkan
        $produkHukum->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Produk Hukum berhasil diupdate',
            'data' => $produkHukum
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $produkHukum = ProdukHukum::where('id', $id)->first();

        if (!$produkHukum) {
            return response()->json([
                'success' => false,
                'message' => 'Produk Hukum tidak ditemukan',
            ], 404);
        }

        // Hapus file dari disk 'public_uploads'
        Storage::disk('public_uploads')->delete('produk_hukum/' . $produkHukum->file);
        $produkHukum->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk Hukum berhasil dihapus',
        ], 200);
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function updateStatus(Request $request, $id)
    {
        $produkHukum = ProdukHukum::where('id', $id)->first();

        if (!$produkHukum) {
            return response()->json([
                'success' => false,
                'message' => 'Produk Hukum tidak ditemukan',
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'status_peraturan' => 'required|in:berlaku,dicabut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update status
        $produkHukum->status_peraturan = $request->status_peraturan;
        $produkHukum->save();

        return response()->json([
            'success' => true,
            'message' => 'Status Produk Hukum berhasil diupdate',
            'data' => $produkHukum
        ], 200);
    }
}
