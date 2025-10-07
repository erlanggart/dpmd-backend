<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\Rw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RwController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = Rw::where('desa_id', $user->desa_id)
            ->withCount('rts as jumlah_rt')
            ->with(['pengurus' => function ($query) {
                $query->where(function ($q) {
                    $q->where('jabatan', 'Ketua')
                        ->orWhere('jabatan', 'LIKE', '%Ketua%');
                })
                    ->where('status_jabatan', 'aktif')
                    ->select('pengurusable_id', 'nama_lengkap');
            }])
            ->orderBy('nomor')
            ->get()
            ->map(function ($item) {
                // Get ketua name from pengurus relation
                $ketua = $item->pengurus->first();
                $item->ketua_nama = $ketua ? $ketua->nama_lengkap : null;
                unset($item->pengurus); // Remove pengurus relation from response
                return $item;
            });
        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $v = Validator::make($request->all(), [
            'nomor' => 'required|string|max:10',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);
        $data = $v->validated();
        $data['desa_id'] = $user->desa_id;
        $item = Rw::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $item = Rw::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $item = Rw::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $v = Validator::make($request->all(), [
            'nomor' => 'required|string|max:10',
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
        $item = Rw::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        $item->delete();
        return response()->json(['success' => true]);
    }
}
