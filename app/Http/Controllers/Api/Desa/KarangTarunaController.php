<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\KarangTaruna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KarangTarunaController extends Controller
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

        $items = KarangTaruna::where('desa_id', $desaId)->orderBy('nama')->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $v = Validator::make($request->all(), [
            'nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);
        $data = array_merge($v->validated(), ['desa_id' => $user->desa_id]);
        if (empty($data['nama'])) $data['nama'] = 'Karang Taruna';
        $item = KarangTaruna::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $item = KarangTaruna::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $v = Validator::make($request->all(), [
            'nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);
        $item->update($v->validated());
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $item = KarangTaruna::findOrFail($id);
        } else {
            $item = KarangTaruna::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        }

        $v = Validator::make($request->all(), [
            'status_kelembagaan' => 'required|in:aktif,nonaktif',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $item->update(['status_kelembagaan' => $request->status_kelembagaan]);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function toggleVerification(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role === 'superadmin') {
            $item = KarangTaruna::findOrFail($id);
        } else {
            $item = KarangTaruna::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        }

        $v = Validator::make($request->all(), [
            'status_verifikasi' => 'required|in:verified,unverified,pending',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $item->update(['status_verifikasi' => $request->status_verifikasi]);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $item = KarangTaruna::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $item->delete();
        return response()->json(['success' => true]);
    }
}
