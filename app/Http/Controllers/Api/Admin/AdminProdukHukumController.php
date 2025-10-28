<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\ProdukHukum;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminProdukHukumController extends Controller
{
    /**
     * Display a listing of all produk hukum from all desa with filters.
     */
    public function index(Request $request)
    {
        $query = ProdukHukum::with(['desa.kecamatan']);

        // Filter berdasarkan kecamatan
        if ($request->has('kecamatan_id') && $request->kecamatan_id != '') {
            $query->whereHas('desa', function ($q) use ($request) {
                $q->where('kecamatan_id', $request->kecamatan_id);
            });
        }

        // Filter berdasarkan desa
        if ($request->has('desa_id') && $request->desa_id != '') {
            $query->where('desa_id', $request->desa_id);
        }

        // Filter berdasarkan jenis
        if ($request->has('jenis') && $request->jenis != '') {
            $query->where('jenis', $request->jenis);
        }

        // Filter berdasarkan status peraturan
        if ($request->has('status_peraturan') && $request->status_peraturan != '') {
            $query->where('status_peraturan', $request->status_peraturan);
        }

        // Pencarian berdasarkan judul, nomor, atau subjek
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('judul', 'like', '%' . $searchTerm . '%')
                    ->orWhere('nomor', 'like', '%' . $searchTerm . '%')
                    ->orWhere('subjek', 'like', '%' . $searchTerm . '%');
            });
        }

        // Pagination
        $produkHukums = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'message' => 'Daftar Produk Hukum dari Semua Desa',
            'data' => $produkHukums
        ]);
    }
}
