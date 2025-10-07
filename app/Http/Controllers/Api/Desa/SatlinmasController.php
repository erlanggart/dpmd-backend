<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\Satlinmas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SatlinmasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Satlinmas::where('desa_id', $user->desa_id)->orderBy('nama')->get();
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
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);
        $data = array_merge($v->validated(), ['desa_id' => $user->desa_id]);
        if (empty($data['nama'])) $data['nama'] = 'Satlinmas';
        $item = Satlinmas::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $item = Satlinmas::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $v = Validator::make($request->all(), [
            'nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);
        $item->update($v->validated());
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $item = Satlinmas::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $item->delete();
        return response()->json(['success' => true]);
    }
}
