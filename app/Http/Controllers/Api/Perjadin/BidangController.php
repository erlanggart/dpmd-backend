<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/BidangController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;

class BidangController extends Controller
{
    public function index()
    {
        try {
            $bidangs = Bidang::orderBy('nama')->get();
            
            // Transform data to match frontend expectations
            $transformedData = $bidangs->map(function($bidang) {
                return [
                    'id_bidang' => $bidang->id,
                    'nama_bidang' => $bidang->nama,
                    'status' => 'aktif' // Default active status for filtering
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching bidang data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}