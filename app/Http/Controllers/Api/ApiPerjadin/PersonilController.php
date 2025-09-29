<?php

// dpmd-backend/app/Http/Controllers/PersonilController.php
namespace App\Http\Controllers;
use App\Models\Personil;
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