<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\AparaturDesa;
use App\Models\ProdukHukum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AparaturDesaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AparaturDesa::with('desa', 'produkHukum')->where('desa_id', $user->desa_id);

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('nama_lengkap', 'like', '%' . $request->search . '%')
                    ->orWhere('jabatan', 'like', '%' . $request->search . '%');
            });
        }

        $aparatur = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar Aparatur Desa',
            'data' => $aparatur
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Normalize empty strings to null for nullable fields
        $input = $request->all();
        $nullableFields = [
            'nipd',
            'pangkat_golongan',
            'tanggal_pemberhentian',
            'nomor_sk_pemberhentian',
            'keterangan',
            'produk_hukum_id',
            'bpjs_kesehatan_nomor',
            'bpjs_ketenagakerjaan_nomor',
        ];
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'nipd' => 'nullable|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'pendidikan_terakhir' => 'required|string|max:255',
            'agama' => 'required|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:255',
            'tanggal_pengangkatan' => 'required|date',
            'nomor_sk_pengangkatan' => 'required|string|max:255',
            'tanggal_pemberhentian' => 'nullable|date',
            'nomor_sk_pemberhentian' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
            'produk_hukum_id' => 'nullable|exists:produk_hukums,id',
            'bpjs_kesehatan_nomor' => 'nullable|string|max:255',
            'bpjs_ketenagakerjaan_nomor' => 'nullable|string|max:255',
            'file_bpjs_kesehatan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_bpjs_ketenagakerjaan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_pas_foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'file_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_akta_kelahiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_ijazah_terakhir' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except(['file_bpjs_kesehatan', 'file_bpjs_ketenagakerjaan', 'file_pas_foto', 'file_ktp', 'file_kk', 'file_akta_kelahiran', 'file_ijazah_terakhir', 'id', 'produk_hukum_uuid']);
        $data['desa_id'] = $user->desa_id;

        // Optional: map produk_hukum_uuid to produk_hukum_id
        if ($request->filled('produk_hukum_uuid')) {
            $ph = ProdukHukum::where('id', $request->produk_hukum_uuid)
                ->orWhere('uuid', $request->produk_hukum_uuid)
                ->first();
            $data['produk_hukum_id'] = $ph?->id;
        }

        // Handle file uploads
        $fileFields = ['file_bpjs_kesehatan', 'file_bpjs_ketenagakerjaan', 'file_pas_foto', 'file_ktp', 'file_kk', 'file_akta_kelahiran', 'file_ijazah_terakhir'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('aparatur_desa_files', 'public_uploads');
                $data[$field] = basename($path);
            }
        }

        $aparatur = AparaturDesa::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Aparatur Desa berhasil ditambahkan',
            'data' => $aparatur
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $aparatur = AparaturDesa::with('desa', 'produkHukum')->where('id', $id)->first();

        if (!$aparatur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $aparatur
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $aparatur = AparaturDesa::where('id', $id)->first();
        if (!$aparatur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Normalize empty strings to null for nullable fields and validate (mirip dengan store, tapi file tidak required)
        $input = $request->all();
        $nullableFields = [
            'nipd',
            'pangkat_golongan',
            'tanggal_pemberhentian',
            'nomor_sk_pemberhentian',
            'keterangan',
            'produk_hukum_id',
            'bpjs_kesehatan_nomor',
            'bpjs_ketenagakerjaan_nomor',
        ];
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $input) && $input[$field] === '') {
                $input[$field] = null;
            }
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'required|string|max:255',
            'nipd' => 'nullable|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'pendidikan_terakhir' => 'required|string|max:255',
            'agama' => 'required|string|max:255',
            'pangkat_golongan' => 'nullable|string|max:255',
            'tanggal_pengangkatan' => 'required|date',
            'nomor_sk_pengangkatan' => 'required|string|max:255',
            'tanggal_pemberhentian' => 'nullable|date',
            'nomor_sk_pemberhentian' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:Aktif,Tidak Aktif',
            'produk_hukum_id' => 'nullable|exists:produk_hukums,id',
            'bpjs_kesehatan_nomor' => 'nullable|string|max:255',
            'bpjs_ketenagakerjaan_nomor' => 'nullable|string|max:255',
            'file_bpjs_kesehatan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_bpjs_ketenagakerjaan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_pas_foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'file_ktp' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_akta_kelahiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_ijazah_terakhir' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->except(['_method', 'file_bpjs_kesehatan', 'file_bpjs_ketenagakerjaan', 'file_pas_foto', 'file_ktp', 'file_kk', 'file_akta_kelahiran', 'file_ijazah_terakhir', 'produk_hukum_uuid']);

        // Optional: map produk_hukum_uuid to produk_hukum_id
        if ($request->filled('produk_hukum_uuid')) {
            $ph = ProdukHukum::where('id', $request->produk_hukum_uuid)
                ->orWhere('uuid', $request->produk_hukum_uuid)
                ->first();
            $data['produk_hukum_id'] = $ph?->id;
        }

        // Handle file updates
        $fileFields = ['file_bpjs_kesehatan', 'file_bpjs_ketenagakerjaan', 'file_pas_foto', 'file_ktp', 'file_kk', 'file_akta_kelahiran', 'file_ijazah_terakhir'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada
                if ($aparatur->$field) {
                    Storage::disk('public_uploads')->delete('aparatur_desa_files/' . $aparatur->$field);
                }
                // Simpan file baru
                $path = $request->file($field)->store('aparatur_desa_files', 'public_uploads');
                $data[$field] = basename($path);
            }
        }

        $aparatur->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Aparatur Desa berhasil diupdate',
            'data' => $aparatur
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $aparatur = AparaturDesa::where('id', $id)->first();

        if (!$aparatur) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Hapus semua file terkait
        $fileFields = ['file_bpjs_kesehatan', 'file_bpjs_ketenagakerjaan', 'file_pas_foto', 'file_ktp', 'file_kk', 'file_akta_kelahiran', 'file_ijazah_terakhir'];
        foreach ($fileFields as $field) {
            if ($aparatur->$field) {
                Storage::disk('public_uploads')->delete('aparatur_desa_files/' . $aparatur->$field);
            }
        }

        $aparatur->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aparatur Desa berhasil dihapus'
        ]);
    }
}
