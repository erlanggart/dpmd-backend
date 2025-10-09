<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KelembagaanController extends Controller
{
    /**
     * Get kelembagaan summary data for authenticated desa user
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Untuk sementara, return dummy data sesuai yang diharapkan frontend
            // TODO: Implement actual database counting when models are created
            $summary = [
                'rt' => 12,
                'rw' => 8,
                'posyandu' => 5,
                'karang_taruna' => 1,
                'lpm' => 1,
                'pkk' => 1,
                'satlinmas' => 1,
                'karang_taruna_formed' => true,
                'lpm_formed' => true,
                'satlinmas_formed' => true,
                'pkk_formed' => true,
                'total' => 29
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting kelembagaan summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan kelembagaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed kelembagaan summary
     */
    public function detailedSummary(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Return more detailed data
            $detailedSummary = [
                'rt_list' => [],
                'rw_list' => [],
                'posyandu_list' => [],
                'karang_taruna' => null,
                'lpm' => null,
                'pkk' => null,
                'satlinmas' => null,
                'counts' => [
                    'rt' => 12,
                    'rw' => 8,
                    'posyandu' => 5,
                    'karang_taruna' => 1,
                    'lpm' => 1,
                    'pkk' => 1,
                    'satlinmas' => 1
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $detailedSummary
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting detailed kelembagaan summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan kelembagaan detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
