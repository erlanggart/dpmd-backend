<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use App\Models\Rt;
use App\Models\Rw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RtController extends Controller
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

        $items = Rt::where('desa_id', $desaId)
            ->with(['pengurus' => function ($query) {
                $query->where(function ($q) {
                    $q->where('jabatan', 'Ketua RT')
                        ->orWhere('jabatan', 'LIKE', '%Ketua%');
                })
                    ->where('status_jabatan', 'aktif')
                    ->select('pengurusable_id', 'nama_lengkap');
            }])
            ->orderBy('nomor')
            ->get()
            ->map(function ($item) {
                $ketua = $item->pengurus->first();
                $item->ketua_nama = $ketua ? $ketua->nama_lengkap : null;
                unset($item->pengurus);
                return $item;
            });

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $v = Validator::make($request->all(), [
            'rw_id' => 'required|uuid',
            'nomor' => 'required|string|max:10',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $rw = Rw::where('desa_id', $user->desa_id)->where('id', $request->rw_id)->first();
        if (!$rw) return response()->json(['message' => 'RW tidak ditemukan di desa ini'], 404);

        $data = $v->validated();
        $data['desa_id'] = $user->desa_id;
        $data['rw_id'] = $rw->id;
        $item = Rt::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        // For superadmin, find RT by ID without desa_id restriction
        if ($user->role === 'superadmin') {
            $item = Rt::where('id', $id)
                ->with(['rw' => function ($query) {
                    $query->with(['pengurus' => function ($q) {
                        $q->where(function ($subq) {
                            $subq->where('jabatan', 'Ketua RW')
                                ->orWhere('jabatan', 'LIKE', '%Ketua%');
                        })
                            ->where('status_jabatan', 'aktif')
                            ->select('pengurusable_id', 'nama_lengkap');
                    }]);
                }])
                ->with(['desa'])
                ->firstOrFail();
        } else {
            // For desa users, restrict to their desa
            $item = Rt::where('desa_id', $user->desa_id)
                ->where('id', $id)
                ->with(['rw' => function ($query) {
                    $query->with(['pengurus' => function ($q) {
                        $q->where(function ($subq) {
                            $subq->where('jabatan', 'Ketua RW')
                                ->orWhere('jabatan', 'LIKE', '%Ketua%');
                        })
                            ->where('status_jabatan', 'aktif')
                            ->select('pengurusable_id', 'nama_lengkap');
                    }]);
                }])
                ->firstOrFail();
        }

        // Tambahkan ketua_nama untuk RW
        if ($item->rw && $item->rw->pengurus) {
            $ketua = $item->rw->pengurus->first();
            $item->rw->ketua_nama = $ketua ? $ketua->nama_lengkap : null;
            unset($item->rw->pengurus);
        }

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        // For superadmin, find RT by ID without desa_id restriction
        if ($user->role === 'superadmin') {
            $item = Rt::findOrFail($id);
            $rw = Rw::where('id', $request->rw_id)->first();
        } else {
            // For desa users, restrict to their desa
            $item = Rt::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
            $rw = Rw::where('desa_id', $user->desa_id)->where('id', $request->rw_id)->first();
        }

        $v = Validator::make($request->all(), [
            'rw_id' => 'required|uuid',
            'nomor' => 'required|string|max:10',
            'alamat' => 'nullable|string|max:255',
            'status_kelembagaan' => 'nullable|in:aktif,nonaktif',
            'status_verifikasi' => 'nullable|in:verified,unverified',
            'produk_hukum_id' => 'nullable|uuid|exists:produk_hukums,id',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        if (!$rw) return response()->json(['message' => 'RW tidak ditemukan'], 404);

        $data = $v->validated();
        $data['rw_id'] = $rw->id;
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $user = $request->user();

        // Find RT based on user role
        if ($user->role === 'superadmin') {
            $item = Rt::findOrFail($id);
        } else {
            $item = Rt::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
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

        // Find RT based on user role
        if ($user->role === 'superadmin') {
            $item = Rt::findOrFail($id);
        } else {
            $item = Rt::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
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

        // For superadmin, find RT by ID without desa_id restriction
        if ($user->role === 'superadmin') {
            $item = Rt::findOrFail($id);
        } else {
            // For desa users, restrict to their desa
            $item = Rt::where('desa_id', $user->desa_id)->where('id', $id)->firstOrFail();
        }

        $item->delete();
        return response()->json(['success' => true]);
    }
}
