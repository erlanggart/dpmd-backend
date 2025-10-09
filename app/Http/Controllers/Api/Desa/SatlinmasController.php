<?php

namespace App\Http\Controllers\Api\Desa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SatlinmasController extends Controller
{
    /**
     * Get all satlinmas for authenticated desa user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Return dummy data for now
            // TODO: Implement actual database query when Satlinmas model is created
            $satlinmas = [
                [
                    'id' => 1,
                    'nama_ketua' => 'Budi Santoso',
                    'jabatan' => 'Ketua Satlinmas',
                    'alamat' => 'RT 01/RW 01',
                    'no_hp' => '081234567890',
                    'status_kelembagaan' => 'aktif',
                    'tanggal_dibentuk' => '2024-01-15',
                    'created_at' => '2024-01-15T10:30:00.000000Z',
                    'updated_at' => '2024-01-15T10:30:00.000000Z'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $satlinmas
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting satlinmas list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new satlinmas
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'nama_ketua' => 'required|string|max:255',
                'jabatan' => 'nullable|string|max:255',
                'alamat' => 'nullable|string',
                'no_hp' => 'nullable|string|max:20',
                'tanggal_dibentuk' => 'nullable|date',
                'status_kelembagaan' => 'nullable|string|in:aktif,tidak_aktif'
            ]);

            // TODO: Implement actual database storage when Satlinmas model is created
            $satlinmas = array_merge($validatedData, [
                'id' => rand(1, 1000),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data satlinmas berhasil disimpan',
                'data' => $satlinmas
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating satlinmas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update satlinmas
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'nama_ketua' => 'required|string|max:255',
                'jabatan' => 'nullable|string|max:255',
                'alamat' => 'nullable|string',
                'no_hp' => 'nullable|string|max:20',
                'tanggal_dibentuk' => 'nullable|date',
                'status_kelembagaan' => 'nullable|string|in:aktif,tidak_aktif'
            ]);

            // TODO: Implement actual database update when Satlinmas model is created
            $satlinmas = array_merge($validatedData, [
                'id' => $id,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data satlinmas berhasil diperbarui',
                'data' => $satlinmas
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating satlinmas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete satlinmas
     */
    public function destroy($id)
    {
        try {
            // TODO: Implement actual database deletion when Satlinmas model is created
            
            return response()->json([
                'success' => true,
                'message' => 'Data satlinmas berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting satlinmas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
