<?php

// dpmd-backend/app/Http/Controllers/Api/ApiPerjadin/PersonilController.php
namespace App\Http\Controllers\Api\ApiPerjadin;
use App\Http\Controllers\Controller;
use App\Models\ModelsPerjadin\Personil;
use Illuminate\Http\Request;

class PersonilController extends Controller
{
    public function index()
    {
        $personil = Personil::select('id_personil', 'nama_personil')->orderBy('nama_personil')->get();
        return response()->json($personil);
    }
    public function getPersonilByBidang($id_bidang)
    {
        $personil = Personil::where('id_bidang', $id_bidang)
            ->select('id_personil', 'nama_personil')
            ->orderBy('nama_personil')
            ->get();
        return response()->json($personil);
    }
}