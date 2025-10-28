<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bumdes;

class DesaController extends Controller
{
    /**
     * Get all desas for reference
     */
    public function index()
    {
        try {
            // Join dengan kecamatans untuk mendapatkan nama kecamatan
            $desas = DB::table('desas')
                ->join('kecamatans', 'desas.kecamatan_id', '=', 'kecamatans.id')
                ->select(
                    'desas.id',
                    'desas.kode as kode_desa',
                    'desas.nama as nama_desa',
                    'kecamatans.nama as nama_kecamatan'
                )
                ->orderBy('kecamatans.nama')
                ->orderBy('desas.nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $desas,
                'total' => $desas->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch desa data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronize BUMDes village codes with desas table
     */
    public function syncBumdesVillageCodes()
    {
        try {
            // Get all desas for lookup with kecamatan info
            $desas = DB::table('desas')
                ->join('kecamatans', 'desas.kecamatan_id', '=', 'kecamatans.id')
                ->select(
                    'desas.id',
                    'desas.kode as kode_desa',
                    'desas.nama as nama_desa',
                    'kecamatans.nama as nama_kecamatan'
                )
                ->get()
                ->keyBy(function ($item) {
                    // Create composite key: kecamatan-nama_desa
                    return strtoupper(trim($item->nama_kecamatan)) . '-' . strtoupper(trim($item->nama_desa));
                });

            // Get all BUMDes that need village code sync
            $bumdesList = Bumdes::all();

            $syncResults = [
                'updated' => 0,
                'no_match' => 0,
                'already_synced' => 0,
                'details' => []
            ];

            foreach ($bumdesList as $bumdes) {
                // Skip if already has proper village code (format: xx.xx.xx.xxxx)
                $hasValidCode = !empty($bumdes->kode_desa) &&
                    preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $bumdes->kode_desa);

                if ($hasValidCode) {
                    $syncResults['already_synced']++;
                    continue;
                }

                // Try to find matching desa
                $kecamatan = strtoupper(trim($bumdes->kecamatan ?? ''));
                $namaDesaRaw = strtoupper(trim($bumdes->desa ?? ''));

                // Extract nama desa from combined format if needed
                $namaDesa = $namaDesaRaw;
                if (strpos($namaDesaRaw, '-') !== false) {
                    $parts = explode('-', $namaDesaRaw);
                    $namaDesa = trim($parts[1]); // Take the second part after dash
                }

                $searchKey = $kecamatan . '-' . $namaDesa;

                if (isset($desas[$searchKey])) {
                    $matchedDesa = $desas[$searchKey];

                    // Check if the village code is already used by another BUMDes
                    $existingBumdes = Bumdes::where('kode_desa', $matchedDesa->kode_desa)
                        ->where('id', '!=', $bumdes->id)
                        ->first();

                    if ($existingBumdes) {
                        // Skip if code already used - mark as duplicate
                        $syncResults['no_match']++;
                        $syncResults['details'][] = [
                            'bumdes_id' => $bumdes->id,
                            'bumdes_name' => $bumdes->namabumdesa,
                            'search_key' => $searchKey,
                            'status' => 'duplicate_code',
                            'message' => "Kode desa {$matchedDesa->kode_desa} sudah digunakan oleh BUMDes: {$existingBumdes->namabumdesa}",
                            'original_desa' => $bumdes->desa,
                            'original_kecamatan' => $bumdes->kecamatan
                        ];
                    } else {
                        // Update BUMDes with proper village code and standardized names
                        $bumdes->update([
                            'kode_desa' => $matchedDesa->kode_desa,
                            'desa' => $matchedDesa->nama_desa, // Use standardized name from desas table
                            'kecamatan' => $matchedDesa->nama_kecamatan // Use standardized kecamatan name
                        ]);

                        $syncResults['updated']++;
                        $syncResults['details'][] = [
                            'bumdes_id' => $bumdes->id,
                            'bumdes_name' => $bumdes->namabumdesa,
                            'old_desa' => $bumdes->getOriginal('desa'),
                            'new_desa' => $matchedDesa->nama_desa,
                            'old_kecamatan' => $bumdes->getOriginal('kecamatan'),
                            'new_kecamatan' => $matchedDesa->nama_kecamatan,
                            'village_code' => $matchedDesa->kode_desa,
                            'matched_key' => $searchKey
                        ];
                    }
                } else {
                    $syncResults['no_match']++;
                    $syncResults['details'][] = [
                        'bumdes_id' => $bumdes->id,
                        'bumdes_name' => $bumdes->namabumdesa,
                        'search_key' => $searchKey,
                        'status' => 'no_match',
                        'original_desa' => $bumdes->desa,
                        'original_kecamatan' => $bumdes->kecamatan
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Village code synchronization completed',
                'results' => $syncResults,
                'summary' => [
                    'total_bumdes' => $bumdesList->count(),
                    'updated' => $syncResults['updated'],
                    'no_match' => $syncResults['no_match'],
                    'already_synced' => $syncResults['already_synced']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get village code mapping preview (without actual update)
     */
    public function previewVillageCodeSync()
    {
        try {
            // Get all desas for lookup with kecamatan info
            $desas = DB::table('desas')
                ->join('kecamatans', 'desas.kecamatan_id', '=', 'kecamatans.id')
                ->select(
                    'desas.id',
                    'desas.kode as kode_desa',
                    'desas.nama as nama_desa',
                    'kecamatans.nama as nama_kecamatan'
                )
                ->get()
                ->keyBy(function ($item) {
                    return strtoupper(trim($item->nama_kecamatan)) . '-' . strtoupper(trim($item->nama_desa));
                });

            // Get all BUMDes for preview
            $bumdesList = Bumdes::select('id', 'namabumdesa', 'desa', 'kecamatan', 'kode_desa')->get();

            $previewResults = [
                'will_update' => [],
                'no_match' => [],
                'already_synced' => []
            ];

            foreach ($bumdesList as $bumdes) {
                // Skip if already has proper village code (format: xx.xx.xx.xxxx)
                $hasValidCode = !empty($bumdes->kode_desa) &&
                    preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $bumdes->kode_desa);

                if ($hasValidCode) {
                    $previewResults['already_synced'][] = [
                        'bumdes_id' => $bumdes->id,
                        'bumdes_name' => $bumdes->namabumdesa,
                        'current_code' => $bumdes->kode_desa,
                        'desa' => $bumdes->desa,
                        'kecamatan' => $bumdes->kecamatan
                    ];
                    continue;
                }

                // Try to find matching desa
                $kecamatan = strtoupper(trim($bumdes->kecamatan ?? ''));
                $namaDesaRaw = strtoupper(trim($bumdes->desa ?? ''));

                // Extract nama desa from combined format if needed
                $namaDesa = $namaDesaRaw;
                if (strpos($namaDesaRaw, '-') !== false) {
                    $parts = explode('-', $namaDesaRaw);
                    $namaDesa = trim($parts[1]);
                }

                $searchKey = $kecamatan . '-' . $namaDesa;

                if (isset($desas[$searchKey])) {
                    $matchedDesa = $desas[$searchKey];

                    $previewResults['will_update'][] = [
                        'bumdes_id' => $bumdes->id,
                        'bumdes_name' => $bumdes->namabumdesa,
                        'current_desa' => $bumdes->desa,
                        'new_desa' => $matchedDesa->nama_desa,
                        'current_kecamatan' => $bumdes->kecamatan,
                        'new_kecamatan' => $matchedDesa->nama_kecamatan,
                        'new_village_code' => $matchedDesa->kode_desa,
                        'search_key' => $searchKey
                    ];
                } else {
                    $previewResults['no_match'][] = [
                        'bumdes_id' => $bumdes->id,
                        'bumdes_name' => $bumdes->namabumdesa,
                        'current_desa' => $bumdes->desa,
                        'current_kecamatan' => $bumdes->kecamatan,
                        'search_key' => $searchKey
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Preview of village code synchronization',
                'preview' => $previewResults,
                'summary' => [
                    'total_bumdes' => $bumdesList->count(),
                    'will_update' => count($previewResults['will_update']),
                    'no_match' => count($previewResults['no_match']),
                    'already_synced' => count($previewResults['already_synced'])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific desa by ID
     */
    public function show($id)
    {
        try {
            $desa = DB::table('desas')
                ->join('kecamatans', 'desas.kecamatan_id', '=', 'kecamatans.id')
                ->select(
                    'desas.id',
                    'desas.kode as kode_desa',
                    'desas.nama as nama_desa',
                    'desas.status_pemerintahan',
                    'kecamatans.id as kecamatan_id',
                    'kecamatans.nama as nama_kecamatan'
                )
                ->where('desas.id', $id)
                ->first();

            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $desa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch desa data: ' . $e->getMessage()
            ], 500);
        }
    }
}
