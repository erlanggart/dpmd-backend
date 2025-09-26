<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BidangController;
use App\Http\Controllers\Api\DinasController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HeroGalleryController;
use App\Http\Controllers\Api\ProfilDesaController;
use App\Http\Controllers\Api\Desa\ProdukHukumController;
use App\Http\Controllers\Api\Desa\AparaturDesaController;
use Illuminate\Support\Facades\File;

Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']); // <-- Route baru

    Route::apiResource('/admin/hero-gallery', HeroGalleryController::class)->except(['show']);
    // Route update dan delete akan ditambahkan di sini

    // Routes untuk CRUD Bidang & Dinas
    Route::apiResource('/bidangs', BidangController::class)->only(['index', 'store']);
    Route::apiResource('/dinas', DinasController::class)->only(['index', 'store']);
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
Route::get('/public/hero-gallery', [HeroGalleryController::class, 'publicIndex']);


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

Route::middleware(['auth:sanctum', 'role:desa'])->prefix('desa')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/profil-desa', ProfilDesaController::class)->only(['index', 'store']);
    Route::apiResource('/produk-hukum', ProdukHukumController::class);
    Route::post('/produk-hukum/{id}', [ProdukHukumController::class, 'update']);
    Route::put('/produk-hukum/status/{id}', [ProdukHukumController::class, 'updateStatus']);
    Route::apiResource('/aparatur-desa', AparaturDesaController::class);
    Route::post('/aparatur-desa/{id}', [AparaturDesaController::class, 'update']);
});

Route::get('/', function () {
    return response()->json(['message' => 'API is running']);
});
