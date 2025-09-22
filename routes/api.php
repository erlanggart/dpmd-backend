<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BidangController;
use App\Http\Controllers\Api\DinasController;
use App\Http\Controllers\Api\AparaturDesaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HeroGalleryController;
use App\Http\Controllers\Api\ProfilDesaController;
use App\Http\Controllers\Api\Desa\ProdukHukumController;
use App\Http\Controllers\Api\BumdesController;
use App\Models\Kecamatan;
use App\Models\Desa;
use Illuminate\Support\Facades\File;

Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']); // <-- Route baru

    Route::apiResource('/admin/hero-gallery', HeroGalleryController::class)->except(['show']);
    // Route update dan delete akan ditambahkan di sini

    // Routes untuk CRUD Bidang & Dinas
    Route::apiResource('/bidangs', BidangController::class)->only(['index', 'store']);
    Route::apiResource('/dinas', DinasController::class)->only(['index', 'store']);
    Route::apiResource('/aparatur-desa', AparaturDesaController::class)
        ->middleware('role:admin desa|admin kecamatan|superadmin');
});

Route::middleware(['auth:sanctum', 'role:admin desa'])->group(function () {
    Route::get('/dashboard/desa', [DashboardController::class, 'desaDashboardData']);
    Route::get('/profil-desa', [ProfilDesaController::class, 'show']);
    Route::post('/profil-desa', [ProfilDesaController::class, 'store']);
    Route::apiResource('/produk-hukum', ProdukHukumController::class)->except(['show']);
    Route::put('/produk-hukum/{id}/status', [ProdukHukumController::class, 'updateStatus']);
}); // Rute Publik untuk Produk Hukum
Route::get('/produk-hukum/{produkHukum}', [ProdukHukumController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/bidang', [AuthController::class, 'loginBidang']);
Route::get('/public/hero-gallery', [HeroGalleryController::class, 'publicIndex']);

// Routes untuk Bumdes
Route::apiResource('/bumdes', BumdesController::class);
Route::get('/bumdes/search', [BumdesController::class, 'search']);
Route::post('/login/desa', [BumdesController::class, 'loginByDesa']);
Route::get('/identitas-bumdes', [BumdesController::class, 'index']); // Untuk mendapatkan data identitas

// Routes untuk data referensi Kecamatan dan Desa
Route::get('/kecamatans', function () {
    return response()->json(['data' => Kecamatan::all(['id', 'kode', 'nama'])]);
});
Route::get('/desas', function () {
    return response()->json(['data' => Desa::with('kecamatan:id,nama')->get(['id', 'kecamatan_id', 'kode', 'nama'])]);
});
Route::get('/desas/by-kecamatan/{kecamatan_id}', function ($kecamatan_id) {
    return response()->json(['data' => Desa::where('kecamatan_id', $kecamatan_id)->get(['id', 'kode', 'nama'])]);
});


Route::get('/test-storage', function () {
    $path = storage_path('app/public/test-folder');

    echo "Mencoba membuat direktori di: " . $path . "<br>";

    try {
        // Coba buat direktori
        if (!File::exists($path)) {
            File::makeDirectory($path, 0775, true, true);
            echo "STATUS: Berhasil membuat folder.<br>";
        } else {
            echo "STATUS: Folder sudah ada.<br>";
        }

        // Coba tulis file
        $file_path = $path . '/test.txt';
        File::put($file_path, 'Tes tulis file berhasil pada ' . now());
        echo "STATUS: Berhasil menulis file di: " . $file_path . "<br>";

        return "KESIMPULAN: Izin akses tulis (write permission) BERFUNGSI.";
    } catch (\Exception $e) {
        // Jika gagal, tampilkan pesan error yang sebenarnya
        return "KESIMPULAN: GAGAL. Pesan Error: " . $e->getMessage();
    }
});
