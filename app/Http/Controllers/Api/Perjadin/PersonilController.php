<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/PersonilController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonilController extends Controller
{
    public function index()
    {
        $personil = DB::table('personil')
            ->join('bidangs', 'personil.id_bidang', '=', 'bidangs.id')
            ->select('personil.id_personil', 'personil.nama_personil', 'personil.id_bidang', 'bidangs.nama as bidang_nama')
            ->orderBy('personil.nama_personil')
            ->get();
        return response()->json($personil);
    }
    
    public function getByBidang($id_bidang)
    {
        try {
            $personil = DB::table('personil')
                ->where('id_bidang', $id_bidang)
                ->select('id_personil', 'nama_personil')
                ->orderBy('nama_personil')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $personil
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching personil data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}