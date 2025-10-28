<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\Pkk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PkkController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Pkk::where('desa_id', $user->desa_id)->orderBy('nama')->get();
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
        if (empty($data['nama'])) $data['nama'] = 'PKK';
        $item = Pkk::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $item = Pkk::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
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

        // Find PKK based on user role
        if ($user->role === 'superadmin') {
            $item = Pkk::findOrFail($id);
        } else {
            $item = Pkk::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
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

        // Find PKK based on user role
        if ($user->role === 'superadmin') {
            $item = Pkk::findOrFail($id);
        } else {
            $item = Pkk::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
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
        $item = Pkk::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $item->delete();
        return response()->json(['success' => true]);
    }
}
