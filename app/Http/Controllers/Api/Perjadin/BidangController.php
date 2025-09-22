<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/BidangController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;

class BidangController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::select('id', 'nama')->orderBy('nama')->get();
        return response()->json($bidangs);
    }
}