<?php

// dpmd-backend/app/Http/Controllers/BidangController.php
namespace App\Http\Controllers;
use App\Models\Bidang;

class BidangController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::select('id_bidang', 'nama_bidang')->orderBy('nama_bidang')->get();
        return response()->json($bidangs);
    }
}