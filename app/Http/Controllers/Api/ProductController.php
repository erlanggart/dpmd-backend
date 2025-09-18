<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product; // <-- Import model Product
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Method untuk menampilkan semua produk
    public function index()
    {
        // Untuk contoh, kita buat beberapa data jika tabel kosong
        if (Product::count() == 0) {
            Product::create(['name' => 'Laptop Keren', 'description' => 'Laptop dengan spek dewa']);
            Product::create(['name' => 'Mouse Gaming', 'description' => 'Mouse dengan RGB menyala']);
        }

        return response()->json(Product::all());
    }
}
