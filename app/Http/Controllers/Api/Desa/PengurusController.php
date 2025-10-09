<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\Pengurus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PengurusController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Handle superadmin access - can specify desa_id in query parameter
        $desaId = $user->role === 'superadmin' && $request->has('desa_id')
            ? $request->get('desa_id')
            : $user->desa_id;

        if (!$desaId) {
            return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
        }

        $items = Pengurus::where('desa_id', $desaId)->latest()->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Get lightweight pengurus list by kelembagaan
     * Only returns essential data: id, nama_lengkap, jabatan, status_jabatan
     */
    public function byKelembagaan(Request $request)
    {
        $user = $request->user();
        $kelembagaanType = $request->query('kelembagaan_type');
        $kelembagaanId = $request->query('kelembagaan_id');

        if (!$kelembagaanType || !$kelembagaanId) {
            return response()->json([
                'success' => false,
                'message' => 'kelembagaan_type dan kelembagaan_id diperlukan'
            ], 400);
        }

        // Handle superadmin access - can specify desa_id in query parameter
        $desaId = $user->role === 'superadmin' && $request->has('desa_id')
            ? $request->get('desa_id')
            : $user->desa_id;

        if (!$desaId) {
            return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
        }

        // Get only essential pengurus data for list view
        $pengurus = Pengurus::where('desa_id', $desaId)
            ->where('pengurusable_type', $kelembagaanType)
            ->where('pengurusable_id', $kelembagaanId)
            ->where('status_jabatan', 'aktif')
            ->select('id', 'nama_lengkap', 'jabatan', 'status_jabatan', 'avatar')
            ->orderBy('jabatan')
            ->get();

        return response()->json(['success' => true, 'data' => $pengurus]);
    }

    /**
     * Get pengurus history (inactive pengurus)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $kelembagaanType = $request->query('kelembagaan_type');
        $kelembagaanId = $request->query('kelembagaan_id');

        if (!$kelembagaanType || !$kelembagaanId) {
            return response()->json([
                'success' => false,
                'message' => 'kelembagaan_type dan kelembagaan_id diperlukan'
            ], 400);
        }

        // Handle superadmin access - can specify desa_id in query parameter
        $desaId = $user->role === 'superadmin' && $request->has('desa_id')
            ? $request->get('desa_id')
            : $user->desa_id;

        if (!$desaId) {
            return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
        }

        // Get inactive pengurus for history
        $pengurus = Pengurus::where('desa_id', $desaId)
            ->where('pengurusable_type', $kelembagaanType)
            ->where('pengurusable_id', $kelembagaanId)
            ->where('status_jabatan', 'selesai')
            ->select('id', 'nama_lengkap', 'jabatan', 'status_jabatan', 'tanggal_mulai_jabatan', 'tanggal_akhir_jabatan', 'avatar')
            ->orderBy('tanggal_akhir_jabatan', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $pengurus]);
    }

    /**
     * Get detailed pengurus data by ID
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            // For superadmin, find pengurus by ID without desa_id restriction
            $pengurus = Pengurus::where('id', $id)->first();

            // If desa_id is specified in query, verify it matches
            if ($request->has('desa_id') && $pengurus) {
                $requestedDesaId = $request->get('desa_id');
                if ($pengurus->desa_id !== $requestedDesaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pengurus tidak ditemukan di desa yang diminta'
                    ], 404);
                }
            }
        } else {
            // For regular users, use their desa_id
            if (!$user->desa_id) {
                return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
            }

            $pengurus = Pengurus::where('desa_id', $user->desa_id)
                ->where('id', $id)
                ->first();
        }

        if (!$pengurus) {
            return response()->json([
                'success' => false,
                'message' => 'Pengurus tidak ditemukan'
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $pengurus]);
    }
    /**
     * Update pengurus status (aktif/selesai)
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            // For superadmin, find pengurus by ID without desa_id restriction
            $pengurus = Pengurus::where('id', $id)->first();

            // If desa_id is specified in query, verify it matches
            if ($request->has('desa_id') && $pengurus) {
                $requestedDesaId = $request->get('desa_id');
                if ($pengurus->desa_id !== $requestedDesaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pengurus tidak ditemukan di desa yang diminta'
                    ], 404);
                }
            }
        } else {
            // For regular users, use their desa_id
            if (!$user->desa_id) {
                return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
            }

            $pengurus = Pengurus::where('desa_id', $user->desa_id)
                ->where('id', $id)
                ->first();
        }

        if (!$pengurus) {
            return response()->json([
                'success' => false,
                'message' => 'Pengurus tidak ditemukan'
            ], 404);
        }

        $v = Validator::make($request->all(), [
            'status_jabatan' => 'required|in:aktif,selesai',
            'tanggal_akhir_jabatan' => 'nullable|date'
        ]);

        if ($v->fails()) {
            return response()->json($v->errors(), 422);
        }

        $pengurus->update([
            'status_jabatan' => $request->status_jabatan,
            'tanggal_akhir_jabatan' => $request->status_jabatan === 'selesai' ?
                ($request->tanggal_akhir_jabatan ?? now()) : null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pengurus berhasil diupdate',
            'data' => $pengurus->fresh()
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Handle superadmin access - can specify desa_id in query parameter
        $desaId = $user->role === 'superadmin' && $request->has('desa_id')
            ? $request->get('desa_id')
            : $user->desa_id;

        if (!$desaId) {
            return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
        }

        $v = Validator::make($request->all(), [
            'pengurusable_type' => 'required|string',
            'pengurusable_id' => 'required|uuid',
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'required|string|max:100',
            'tanggal_mulai_jabatan' => 'required|date',
            'tanggal_akhir_jabatan' => 'nullable|date',
            'status_jabatan' => 'nullable|in:aktif,selesai',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
            'nik' => 'nullable|string|max:32',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'status_perkawinan' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:32',
            'pendidikan' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        // Optional: verify that pengurusable belongs to the same desa
        $class = $request->pengurusable_type;
        if (!class_exists($class)) return response()->json(['message' => 'Tipe pengurus tidak valid'], 422);
        $target = $class::where('id', $request->pengurusable_id)->first();
        if (!$target || (isset($target->desa_id) && $target->desa_id != $desaId)) {
            return response()->json(['message' => 'Target pengurus tidak ditemukan di desa ini'], 404);
        }

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public_uploads');
        }

        $item = Pengurus::create([
            'desa_id' => $desaId,
            'pengurusable_type' => $class,
            'pengurusable_id' => $request->pengurusable_id,
            'nama_lengkap' => $request->nama_lengkap,
            'jabatan' => $request->jabatan,
            'tanggal_mulai_jabatan' => $request->tanggal_mulai_jabatan,
            'tanggal_akhir_jabatan' => $request->tanggal_akhir_jabatan,
            'status_jabatan' => $request->status_jabatan ?? 'aktif',
            'status_verifikasi' => $request->status_verifikasi ?? 'unverified',
            'produk_hukum_id' => $request->produk_hukum_id,
            'nik' => $request->nik,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin,
            'status_perkawinan' => $request->status_perkawinan,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'pendidikan' => $request->pendidikan,
            'avatar' => $avatarPath,
        ]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            // For superadmin, find pengurus by ID without desa_id restriction
            $item = Pengurus::where('id', $id)->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengurus tidak ditemukan'
                ], 404);
            }

            // If desa_id is specified in query, verify it matches
            if ($request->has('desa_id')) {
                $requestedDesaId = $request->get('desa_id');
                if ($item->desa_id !== $requestedDesaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pengurus tidak ditemukan di desa yang diminta'
                    ], 404);
                }
            }
        } else {
            // For regular users, use their desa_id
            if (!$user->desa_id) {
                return response()->json(['success' => false, 'message' => 'Desa ID required'], 400);
            }

            $item = Pengurus::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        }
        $v = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'jabatan' => 'required|string|max:100',
            'tanggal_mulai_jabatan' => 'required|date',
            'tanggal_akhir_jabatan' => 'nullable|date',
            'status_jabatan' => 'nullable|in:aktif,selesai',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
            'nik' => 'nullable|string|max:32',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'status_perkawinan' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:32',
            'pendidikan' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        // Handle avatar upload
        $updateData = $v->validated();
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($item->avatar && Storage::disk('public_uploads')->exists($item->avatar)) {
                Storage::disk('public_uploads')->delete($item->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public_uploads');
        }

        $item->update($updateData);
        return response()->json(['success' => true, 'data' => $item]);
    }
}
