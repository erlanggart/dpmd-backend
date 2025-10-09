<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
}
