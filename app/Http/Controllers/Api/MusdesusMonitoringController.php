<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Musdesus;
use Illuminate\Support\Facades\DB;

class MusdesusMonitoringController extends Controller
{
    /**
     * Get monitoring dashboard data untuk 37 desa target
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            // Query untuk mendapatkan status upload dari 37 desa target
            $monitoringData = DB::table('petugas_monitoring as pm')
                ->leftJoin('desas as d', 'pm.nama_desa', '=', 'd.nama')
                ->leftJoin('kecamatans as k', 'pm.nama_kecamatan', '=', 'k.nama')
                ->leftJoin('musdesus as m', 'd.id', '=', 'm.desa_id')
                ->select([
                    'pm.id as petugas_id',
                    'pm.nama_desa',
                    'pm.nama_kecamatan', 
                    'pm.nama_petugas',
                    'd.id as desa_id',
                    'd.nama as desa_nama_actual',
                    'k.id as kecamatan_id',
                    'k.nama as kecamatan_nama_actual',
                    DB::raw('COUNT(m.id) as total_uploads'),
                    DB::raw('MAX(m.created_at) as latest_upload'),
                    DB::raw('CASE WHEN COUNT(m.id) > 0 THEN "SUDAH UPLOAD" ELSE "BELUM UPLOAD" END as status_upload')
                ])
                ->where('pm.is_active', true)
                ->groupBy('pm.id')
                ->orderBy('pm.nama_kecamatan')
                ->orderBy('pm.nama_desa')
                ->get();

            // Statistik ringkasan
            $totalDesa = 37;
            $desaSudahUpload = $monitoringData->where('total_uploads', '>', 0)->count();
            $desaBelumUpload = $totalDesa - $desaSudahUpload;
            $persentaseUpload = round(($desaSudahUpload / $totalDesa) * 100, 2);

            // Group by kecamatan untuk statistik per kecamatan
            $statistikKecamatan = $monitoringData->groupBy('nama_kecamatan')->map(function ($desas, $kecamatan) {
                $totalDesa = $desas->count();
                $sudahUpload = $desas->where('total_uploads', '>', 0)->count();
                $belumUpload = $totalDesa - $sudahUpload;
                
                return [
                    'nama_kecamatan' => $kecamatan,
                    'total_desa' => $totalDesa,
                    'sudah_upload' => $sudahUpload,
                    'belum_upload' => $belumUpload,
                    'persentase' => $totalDesa > 0 ? round(($sudahUpload / $totalDesa) * 100, 2) : 0,
                    'desa_list' => $desas->values()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'ringkasan' => [
                        'total_desa_target' => $totalDesa,
                        'desa_sudah_upload' => $desaSudahUpload,
                        'desa_belum_upload' => $desaBelumUpload,
                        'persentase_upload' => $persentaseUpload
                    ],
                    'statistik_kecamatan' => $statistikKecamatan,
                    'detail_monitoring' => $monitoringData,
                    'desa_belum_upload_list' => $monitoringData->where('total_uploads', 0)->values(),
                    'desa_sudah_upload_list' => $monitoringData->where('total_uploads', '>', 0)->values()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail monitoring untuk desa tertentu
     */
    public function getDesaDetail($petugasId): JsonResponse
    {
        try {
            $petugas = DB::table('petugas_monitoring as pm')
                ->leftJoin('desas as d', function($join) {
                    $join->on('pm.nama_desa', '=', 'd.nama')
                        ->orOn('pm.desa_id', '=', 'd.id');
                })
                ->leftJoin('kecamatans as k', function($join) {
                    $join->on('pm.nama_kecamatan', '=', 'k.nama')
                        ->orOn('pm.kecamatan_id', '=', 'k.id');
                })
                ->select([
                    'pm.*',
                    'd.id as desa_id',
                    'd.nama as desa_nama_actual',
                    'k.id as kecamatan_id', 
                    'k.nama as kecamatan_nama_actual'
                ])
                ->where('pm.id', $petugasId)
                ->first();

            if (!$petugas) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data petugas monitoring tidak ditemukan'
                ], 404);
            }

            // Get upload history untuk desa ini
            $uploadHistory = [];
            if ($petugas->desa_id) {
                $uploadHistory = Musdesus::where('desa_id', $petugas->desa_id)
                    ->with(['desa', 'kecamatan'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'petugas' => $petugas,
                    'upload_history' => $uploadHistory,
                    'total_uploads' => $uploadHistory->count(),
                    'latest_upload' => $uploadHistory->first()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public monitoring data (read-only) untuk halaman stats publik
     */
    public function getPublicMonitoringData(): JsonResponse
    {
        try {
            // Query untuk mendapatkan status upload dari 37 desa target (tanpa auth)
            // Menggunakan subquery untuk menghindari duplikasi dari JOIN
            $monitoringData = DB::table('petugas_monitoring as pm')
                ->select([
                    'pm.id as petugas_id',
                    'pm.nama_desa',
                    'pm.nama_kecamatan', 
                    'pm.nama_petugas',
                    DB::raw('(SELECT id FROM desas WHERE nama = pm.nama_desa LIMIT 1) as desa_id'),
                    DB::raw('(SELECT nama FROM desas WHERE nama = pm.nama_desa LIMIT 1) as desa_nama_actual'),
                    DB::raw('(SELECT id FROM kecamatans WHERE nama = pm.nama_kecamatan LIMIT 1) as kecamatan_id'),
                    DB::raw('(SELECT nama FROM kecamatans WHERE nama = pm.nama_kecamatan LIMIT 1) as kecamatan_nama_actual'),
                    DB::raw('(SELECT COUNT(*) FROM musdesus WHERE desa_id = (SELECT id FROM desas WHERE nama = pm.nama_desa LIMIT 1)) as total_uploads'),
                    DB::raw('(SELECT MAX(created_at) FROM musdesus WHERE desa_id = (SELECT id FROM desas WHERE nama = pm.nama_desa LIMIT 1)) as latest_upload')
                ])
                ->where('pm.is_active', true)
                ->orderBy('pm.nama_kecamatan')
                ->orderBy('pm.nama_desa')
                ->get();

            // Statistik ringkasan
            $totalDesa = 37;
            $desaSudahUpload = $monitoringData->where('total_uploads', '>', 0)->count();
            $desaBelumUpload = $totalDesa - $desaSudahUpload;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_desa_target' => $totalDesa,
                        'desa_sudah_upload' => $desaSudahUpload,
                        'desa_belum_upload' => $desaBelumUpload,
                        'persentase_upload' => round(($desaSudahUpload / $totalDesa) * 100, 2)
                    ],
                    'detail_monitoring' => $monitoringData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data monitoring publik',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daftar kecamatan dan desa dari petugas monitoring
     */
    public function getKecamatanDesa(): JsonResponse
    {
        try {
            $data = DB::table('petugas_monitoring')
                ->where('is_active', true)
                ->select('nama_kecamatan', 'nama_desa')
                ->distinct()
                ->orderBy('nama_kecamatan')
                ->orderBy('nama_desa')
                ->get()
                ->groupBy('nama_kecamatan')
                ->map(function ($items) {
                    return $items->pluck('nama_desa')->toArray();
                });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan dan desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get petugas monitoring berdasarkan desa dan kecamatan
     */
    public function getPetugasByDesa(Request $request): JsonResponse
    {
        $request->validate([
            'nama_desa' => 'required|string',
            'nama_kecamatan' => 'required|string'
        ]);

        try {
            $namaDesa = trim($request->nama_desa);
            $namaKecamatan = trim($request->nama_kecamatan);

            // Cari petugas berdasarkan nama desa dan kecamatan
            $petugasList = DB::table('petugas_monitoring')
                ->where('nama_desa', $namaDesa)
                ->where('nama_kecamatan', $namaKecamatan)
                ->where('is_active', true)
                ->select([
                    'id',
                    'nama_desa',
                    'nama_kecamatan', 
                    'nama_petugas',
                    'desa_id',
                    'kecamatan_id'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $petugasList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data petugas monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get kecamatan list dari petugas monitoring untuk dropdown upload
     */
    public function getKecamatanFromMonitoring(): JsonResponse
    {
        try {
            $kecamatanList = DB::table('petugas_monitoring as pm')
                ->join('kecamatans as k', 'pm.nama_kecamatan', '=', 'k.nama')
                ->where('pm.is_active', true)
                ->select('k.id', 'k.nama')
                ->distinct()
                ->orderBy('k.nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $kecamatanList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan dari monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get desa list dari petugas monitoring berdasarkan kecamatan untuk dropdown upload
     */
    public function getDesaFromMonitoringByKecamatan($kecamatanId): JsonResponse
    {
        try {
            // Get nama kecamatan berdasarkan ID
            $kecamatan = DB::table('kecamatans')->where('id', $kecamatanId)->first();
            
            if (!$kecamatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak ditemukan'
                ], 404);
            }

            $desaList = DB::table('petugas_monitoring as pm')
                ->join('desas as d', function($join) {
                    $join->on('pm.nama_desa', '=', 'd.nama')
                         ->on('pm.nama_kecamatan', '=', DB::raw('(SELECT nama FROM kecamatans WHERE id = d.kecamatan_id)'));
                })
                ->where('pm.is_active', true)
                ->where('pm.nama_kecamatan', $kecamatan->nama)
                ->select('d.id', 'd.nama')
                ->distinct()
                ->orderBy('d.nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $desaList
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa dari monitoring',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
