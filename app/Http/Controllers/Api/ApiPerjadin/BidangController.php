<?php

// dpmd-backend/app/Http/Controllers/Api/ApiPerjadin/BidangController.php
namespace App\Http\Controllers\Api\ApiPerjadin;
use App\Http\Controllers\Controller;
use App\Models\ModelsPerjadin\Bidang;

class BidangController extends Controller
{
    public function index()
    {
        $bidangs = Bidang::select('id_bidang', 'nama_bidang')->orderBy('nama_bidang')->get();
        return response()->json($bidangs);
    }
}