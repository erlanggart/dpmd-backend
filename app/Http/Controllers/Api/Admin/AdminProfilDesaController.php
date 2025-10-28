<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\ProfilDesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfilDesaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Desa::with(['kecamatan', 'profil']);

            if ($request->filled('kecamatan_id') && $request->kecamatan_id !== '') {
                $query->where('kecamatan_id', $request->kecamatan_id);
            }

            if ($request->filled('is_verified')) {
                if ($request->is_verified === 'verified') {
                    $query->whereHas('profil', function ($q) {
                        $q->where('is_verified', true);
                    });
                } elseif ($request->is_verified === 'unverified') {
                    $query->whereHas('profil', function ($q) {
                        $q->where('is_verified', false);
                    });
                } elseif ($request->is_verified === 'no_profil') {
                    $query->doesntHave('profil');
                }
            }

            if ($request->filled('search') && $request->search !== '') {
                $query->where('nama', 'like', '%' . $request->search . '%');
            }

            $query->orderBy('kecamatan_id', 'asc')->orderBy('nama', 'asc');

            $desas = $query->paginate(20);

            $desas->getCollection()->transform(function ($desa) {
                $desa->has_profil = $desa->profil !== null;
                $desa->is_verified = $desa->profil ? $desa->profil->is_verified : false;
                $desa->verified_at = $desa->profil ? $desa->profil->verified_at : null;
                return $desa;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data desa berhasil diambil',
                'data' => $desas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($desaId)
    {
        try {
            $desa = Desa::with(['kecamatan', 'profil'])->findOrFail($desaId);

            return response()->json([
                'success' => true,
                'message' => 'Data profil desa berhasil diambil',
                'data' => $desa
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data profil desa',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function toggleVerification($desaId)
    {
        try {
            $user = Auth::user();
            
            if (!in_array($user->role, ['superadmin', 'pemerintah_desa'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk verifikasi profil desa'
                ], 403);
            }

            $desa = Desa::with('profil')->findOrFail($desaId);

            if (!$desa->profil) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil desa belum dibuat'
                ], 404);
            }

            $profil = $desa->profil;
            $newStatus = !$profil->is_verified;
            $profil->is_verified = $newStatus;
            
            if ($newStatus) {
                $profil->verified_at = now();
                $profil->verified_by = $user->id;
            } else {
                $profil->verified_at = null;
                $profil->verified_by = null;
            }
            
            $profil->save();

            return response()->json([
                'success' => true,
                'message' => 'Status verifikasi berhasil diubah',
                'data' => [
                    'is_verified' => $profil->is_verified,
                    'verified_at' => $profil->verified_at,
                    'verified_by' => $profil->verified_by
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status verifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
