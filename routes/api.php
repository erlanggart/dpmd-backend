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

use App\Http\Controllers\Api\BumdesController;
use App\Http\Controllers\Api\Perjadin\KegiatanController as PerjadinKegiatanController;
use App\Http\Controllers\Api\Perjadin\DashboardController as PerjadinDashboardController;
use App\Http\Controllers\Api\Perjadin\BidangController as PerjadinBidangController;
use App\Http\Controllers\Api\Perjadin\PersonilController as PerjadinPersonilController;
use App\Http\Controllers\Api\Perjadin\StatistikController as PerjadinStatistikController;
use App\Models\Kecamatan;
use App\Models\Desa;

use Illuminate\Support\Facades\File;

Route::middleware(['auth:sanctum', 'role:superadmin|sekretariat|sarana_prasarana|kekayaan_keuangan|pemberdayaan_masyarakat|pemerintahan_desa'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']); // <-- Route baru

    Route::apiResource('/admin/hero-gallery', HeroGalleryController::class)->except(['show']);
    // Route update dan delete akan ditambahkan di sini

    // Routes untuk CRUD Bidang & Dinas
    Route::apiResource('/bidangs', BidangController::class)->only(['index', 'store']);
    Route::apiResource('/dinas', DinasController::class)->only(['index', 'store']);
}); // <-- Missing closing brace for previous middleware group

Route::middleware(['auth:sanctum', 'role:desa|superadmin|sekretariat|sarana_prasarana|kekayaan_keuangan|pemberdayaan_masyarakat|pemerintahan_desa'])->group(function () {
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
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/public/hero-gallery', [HeroGalleryController::class, 'publicIndex']);

// Route untuk cek user saat ini
Route::middleware(['auth:sanctum'])->get('/me', function (Request $request) {
    return response()->json(['user' => $request->user()]);
});

// Routes dengan autentikasi
Route::middleware(['auth:sanctum'])->group(function () {

    // Routes untuk Bumdes
    Route::apiResource('/bumdes', BumdesController::class);
    Route::get('/bumdes/search', [BumdesController::class, 'search']);
    Route::post('/login/desa', [BumdesController::class, 'loginByDesa']);
    Route::get('/identitas-bumdes', [BumdesController::class, 'index']); // Untuk mendapatkan data identitas

    // Routes untuk Perjalanan Dinas (dengan auth untuk superadmin dan admin bidang)
    Route::prefix('perjadin')->group(function () {
        Route::get('/dashboard', [PerjadinDashboardController::class, 'index']);
        Route::get('/dashboard/weekly-schedule', [PerjadinDashboardController::class, 'weeklySchedule']);
        Route::get('/statistik-perjadin', [PerjadinStatistikController::class, 'getStatistikPerjadin']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/bidang', [PerjadinBidangController::class, 'index']);
            Route::get('/personil/{bidang_id}', [PerjadinPersonilController::class, 'getByBidang']);
            Route::apiResource('/kegiatan', PerjadinKegiatanController::class);
            Route::get('/check-personnel-conflict', [PerjadinKegiatanController::class, 'checkPersonnelConflict']);
        });
    });

    // Routes yang memerlukan role khusus
    Route::prefix('perjadin')->middleware(['auth:sanctum', 'role:superadmin|sekretariat|sarana_prasarana|kekayaan_keuangan|pemberdayaan_masyarakat|pemerintahan_desa'])->group(function () {
        Route::get('/kegiatan/export-excel', [PerjadinKegiatanController::class, 'exportExcel']);
    });

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
}); // End of auth:sanctum middleware group

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
});

Route::get('/', function () {
    return response()->json(['message' => 'API is running']);
});
