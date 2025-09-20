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
});

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
