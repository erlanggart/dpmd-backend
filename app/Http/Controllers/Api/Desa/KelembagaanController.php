<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;

use App\Models\Rt;
use App\Models\Rw;
use App\Models\Posyandu;
use App\Models\KarangTaruna;
use App\Models\Lpm;
use App\Models\Satlinmas;
use App\Models\Pkk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KelembagaanController extends Controller
{
    /**
     * Get summary counts of all kelembagaan types for a specific desa
     * Lightweight endpoint for dashboard/overview purposes
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * Response format:
     * {
     *   "success": true,
     *   "data": {
     *     "rt": 5,
     *     "rw": 3,
     *     "posyandu": 2,
     *     "karang_taruna": 1,
     *     "lpm": 1,
     *     "satlinmas": 1,
     *     "pkk": 1,
     *     "karang_taruna_formed": true,
     *     "lpm_formed": true,
     *     "satlinmas_formed": true,
     *     "pkk_formed": true,
     *     "total": 14
     *   }
     * }
     */
    public function getSummary(Request $request)
    {
        try {
            // Get desa_id from authenticated user or request
            $user = Auth::user();
            $desaId = $user ? $user->desa_id : $request->query('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa ID diperlukan'
                ], 400);
            }

            // Count all kelembagaan types for the specific desa
            $rtCount = Rt::where('desa_id', $desaId)->count();
            $rwCount = Rw::where('desa_id', $desaId)->count();
            $posyanduCount = Posyandu::where('desa_id', $desaId)->count();
            $karangTarunaCount = KarangTaruna::where('desa_id', $desaId)->count();
            $lpmCount = Lpm::where('desa_id', $desaId)->count();
            $satlinmasCount = Satlinmas::where('desa_id', $desaId)->count();
            $pkkCount = Pkk::where('desa_id', $desaId)->count();

            $summary = [
                'rt' => $rtCount,
                'rw' => $rwCount,
                'posyandu' => $posyanduCount,
                'karang_taruna' => $karangTarunaCount,
                'lpm' => $lpmCount,
                'satlinmas' => $satlinmasCount,
                'pkk' => $pkkCount,
                // Add formation status for singleton kelembagaan
                'karang_taruna_formed' => $karangTarunaCount > 0,
                'lpm_formed' => $lpmCount > 0,
                'satlinmas_formed' => $satlinmasCount > 0,
                'pkk_formed' => $pkkCount > 0,
            ];

            // Add total count
            $summary['total'] = $rtCount + $rwCount + $posyanduCount + $karangTarunaCount + $lpmCount + $satlinmasCount + $pkkCount;

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data kelembagaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary counts with status breakdown
     * Includes active/inactive counts for more detailed overview
     */
    public function getDetailedSummary(Request $request)
    {
        try {
            // Get desa_id from authenticated user or request
            $user = Auth::user();
            $desaId = $user ? $user->desa_id : $request->query('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa ID diperlukan'
                ], 400);
            }

            $summary = [];
            $kelembagaanTypes = [
                'rt' => Rt::class,
                'rw' => Rw::class,
                'posyandu' => Posyandu::class,
                'karang_taruna' => KarangTaruna::class,
                'lpm' => Lpm::class,
                'satlinmas' => Satlinmas::class,
                'pkk' => Pkk::class,
            ];

            foreach ($kelembagaanTypes as $type => $model) {
                $baseQuery = $model::where('desa_id', $desaId);

                $summary[$type] = [
                    'total' => $baseQuery->count(),
                    'aktif' => (clone $baseQuery)->where('status_kelembagaan', 'aktif')->count(),
                    'nonaktif' => (clone $baseQuery)->where('status_kelembagaan', 'nonaktif')->count(),
                ];
            }

            // Calculate overall totals
            $overallTotal = 0;
            $overallAktif = 0;
            $overallNonaktif = 0;

            foreach ($summary as $counts) {
                $overallTotal += $counts['total'];
                $overallAktif += $counts['aktif'];
                $overallNonaktif += $counts['nonaktif'];
            }

            $summary['overall'] = [
                'total' => $overallTotal,
                'aktif' => $overallAktif,
                'nonaktif' => $overallNonaktif,
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data kelembagaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RW list for logged-in desa
     */
    public function getRW()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $rwList = Rw::where('desa_id', $user->desa_id)
                ->with(['pengurus', 'rts' => function ($query) {
                    $query->with('pengurus');
                }])
                ->get()
                ->map(function ($rw) {
                    $ketua = $rw->pengurus()->where('jabatan', 'Ketua')->first();
                    return [
                        'id' => $rw->id,
                        'nama' => 'RW ' . $rw->nomor,
                        'nomor' => $rw->nomor,
                        'alamat' => $rw->alamat,
                        'ketua' => $ketua ? $ketua->nama : '-',
                        'pengurus_count' => $rw->pengurus->count(),
                        'rt_count' => $rw->rts->count(),
                        'total_pengurus_rt' => $rw->rts->sum(function ($rt) {
                            return $rt->pengurus->count();
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data RW berhasil diambil',
                'data' => $rwList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RW',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Posyandu list for logged-in desa
     */
    public function getPosyandu()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $posyanduList = Posyandu::where('desa_id', $user->desa_id)
                ->with('pengurus')
                ->get()
                ->map(function ($posyandu) {
                    $ketua = $posyandu->pengurus()->where('jabatan', 'Ketua')->first();
                    return [
                        'id' => $posyandu->id,
                        'nama' => $posyandu->nama,
                        'alamat' => $posyandu->alamat,
                        'ketua' => $ketua ? $ketua->nama : '-',
                        'pengurus_count' => $posyandu->pengurus->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data Posyandu berhasil diambil',
                'data' => $posyanduList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Posyandu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Karang Taruna for logged-in desa
     */
    public function getKarangTaruna()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $karangTaruna = KarangTaruna::where('desa_id', $user->desa_id)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($karangTaruna) {
                $ketua = $karangTaruna->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $karangTaruna->id,
                    'nama' => $karangTaruna->nama,
                    'alamat' => $karangTaruna->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $karangTaruna->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Karang Taruna berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Karang Taruna',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get LPM for logged-in desa
     */
    public function getLPM()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $lpm = Lpm::where('desa_id', $user->desa_id)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($lpm) {
                $ketua = $lpm->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $lpm->id,
                    'nama' => $lpm->nama,
                    'alamat' => $lpm->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $lpm->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data LPM berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data LPM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Satlinmas for logged-in desa
     */
    public function getSatlinmas()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $satlinmas = Satlinmas::where('desa_id', $user->desa_id)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($satlinmas) {
                $ketua = $satlinmas->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $satlinmas->id,
                    'nama' => $satlinmas->nama,
                    'alamat' => $satlinmas->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $satlinmas->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Satlinmas berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PKK for logged-in desa
     */
    public function getPKK()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->desa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak memiliki akses desa'
                ], 403);
            }

            $pkk = Pkk::where('desa_id', $user->desa_id)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($pkk) {
                $ketua = $pkk->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $pkk->id,
                    'nama' => $pkk->nama,
                    'alamat' => $pkk->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $pkk->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data PKK berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data PKK',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}