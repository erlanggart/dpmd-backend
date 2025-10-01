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
use App\Http\Controllers\Api\MusdesusMonitoringController;

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



// Admin verification endpoint for secure delete operations
Route::post('/admin/verify-login', [AuthController::class, 'verifyAdminLogin']);
Route::get('/public/hero-gallery', [HeroGalleryController::class, 'publicIndex']);

// Public stats endpoint
Route::get('/public/stats', function () {
    try {
        // Return data statis yang sudah benar sesuai data resmi Kabupaten Bogor
        return response()->json([
            'success' => true,
            'data' => [
                'kecamatan' => 40,
                'desa' => 416,
                'kelurahan' => 19
            ]
        ]);
    } catch (\Exception $e) {
        // Jika ada error, return data default yang sama
        return response()->json([
            'success' => true,
            'data' => [
                'kecamatan' => 40,
                'desa' => 416,
                'kelurahan' => 19
            ]
        ]);
    }
});

// Public musdesus statistics endpoint
Route::get('/public/musdesus/stats', function () {
    try {
        $totalFiles = \App\Models\Musdesus::count();
        $totalSize = \App\Models\Musdesus::sum('ukuran_file');
        
        // Stats by kecamatan dengan jumlah desa yang upload
        $kecamatanStats = \App\Models\Musdesus::join('kecamatans', 'musdesus.kecamatan_id', '=', 'kecamatans.id')
                                             ->selectRaw('
                                                 kecamatans.id,
                                                 kecamatans.nama,
                                                 count(DISTINCT musdesus.desa_id) as desa_upload,
                                                 count(*) as total_files
                                             ')
                                             ->groupBy('kecamatans.id', 'kecamatans.nama')
                                             ->orderBy('desa_upload', 'desc')
                                             ->limit(15)
                                             ->get();

        // Add total desa per kecamatan
        foreach ($kecamatanStats as $kecamatan) {
            $totalDesa = \App\Models\Desa::where('kecamatan_id', $kecamatan->id)->count();
            $kecamatan->total_desa = $totalDesa;
            $kecamatan->percentage = $totalDesa > 0 ? round(($kecamatan->desa_upload / $totalDesa) * 100, 1) : 0;
        }
        
        // Stats desa yang sudah upload (Top 20)
        $desaStats = \App\Models\Musdesus::join('desas', 'musdesus.desa_id', '=', 'desas.id')
                                        ->join('kecamatans', 'desas.kecamatan_id', '=', 'kecamatans.id')
                                        ->selectRaw('
                                            desas.nama as desa_nama,
                                            kecamatans.nama as kecamatan_nama,
                                            count(*) as jumlah_upload
                                        ')
                                        ->groupBy('desas.id', 'desas.nama', 'kecamatans.nama')
                                        ->orderBy('jumlah_upload', 'desc')
                                        ->limit(20)
                                        ->get();
        
        // File type distribution
        $fileTypeStats = \App\Models\Musdesus::selectRaw('
                                CASE 
                                    WHEN LOWER(nama_file) LIKE "%.pdf" THEN "PDF"
                                    WHEN LOWER(nama_file) LIKE "%.doc%" OR LOWER(nama_file) LIKE "%.docx" THEN "Word"
                                    WHEN LOWER(nama_file) LIKE "%.xls%" OR LOWER(nama_file) LIKE "%.xlsx" THEN "Excel"
                                    ELSE "Lainnya"
                                END as file_type, 
                                count(*) as count
                            ')
                            ->groupBy('file_type')
                            ->get();

        // Summary stats
        $totalDesaUpload = \App\Models\Musdesus::distinct('desa_id')->count('desa_id');
        $totalKecamatanUpload = \App\Models\Musdesus::distinct('kecamatan_id')->count('kecamatan_id');
        $totalDesa = \App\Models\Desa::count();
        $totalKecamatan = \App\Models\Kecamatan::count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_files' => $totalFiles,
                    'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                    'total_desa_upload' => $totalDesaUpload,
                    'total_kecamatan_upload' => $totalKecamatanUpload,
                    'total_desa' => $totalDesa,
                    'total_kecamatan' => $totalKecamatan,
                    'coverage_percentage' => $totalDesa > 0 ? round(($totalDesaUpload / $totalDesa) * 100, 1) : 0
                ],
                'kecamatan_stats' => $kecamatanStats,
                'desa_stats' => $desaStats,
                'file_type_stats' => $fileTypeStats
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil statistik musdesus',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Public musdesus files list endpoint
Route::get('/public/musdesus/files', function () {
    try {
        $files = \App\Models\Musdesus::with(['desa', 'kecamatan'])
                                    ->orderBy('created_at', 'desc')
                                    ->limit(100)
                                    ->get();
        
        return response()->json([
            'success' => true,
            'data' => $files
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil daftar file musdesus',
            'error' => $e->getMessage()
        ], 500);
    }
});

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
    Route::apiResource('/aparatur-desa', AparaturDesaController::class);
    Route::post('/aparatur-desa/{id}', [AparaturDesaController::class, 'update']);
});

// Routes untuk Musdesus (Public - tidak perlu auth)
Route::prefix('musdesus')->group(function () {
    Route::get('/kecamatan', [App\Http\Controllers\Api\MusdesusController::class, 'getKecamatan']);
    Route::get('/desa/{kecamatan_id}', [App\Http\Controllers\Api\MusdesusController::class, 'getDesaByKecamatan']);
    Route::get('/check-desa/{desa_id}', [App\Http\Controllers\Api\MusdesusController::class, 'checkDesaUploadStatus']);
    Route::post('/upload', [App\Http\Controllers\Api\MusdesusController::class, 'store']);
    Route::get('/download/{id}', [App\Http\Controllers\Api\MusdesusController::class, 'download']);
    Route::get('/view/{filename}', [App\Http\Controllers\Api\MusdesusController::class, 'viewFile']);
    Route::get('/download-file/{filename}', [App\Http\Controllers\Api\MusdesusController::class, 'downloadByFilename']);
});

// Routes untuk admin musdesus (perlu auth)
Route::middleware(['auth:sanctum', 'role:superadmin|sekretariat'])->prefix('admin/musdesus')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MusdesusController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Api\MusdesusController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\MusdesusController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\MusdesusController::class, 'destroy']);
    
    // Routes untuk monitoring 37 desa target
    Route::get('/monitoring/dashboard', [MusdesusMonitoringController::class, 'getDashboardData']);
    Route::get('/monitoring/desa/{petugasId}', [MusdesusMonitoringController::class, 'getDesaDetail']);
});

// Public monitoring endpoint (read-only) untuk stats page
Route::get('/public/musdesus/monitoring', [MusdesusMonitoringController::class, 'getPublicMonitoringData']);

// Endpoint untuk musdesus monitoring upload page
Route::get('/musdesus/kecamatan-desa', [MusdesusMonitoringController::class, 'getKecamatanDesa']);
Route::post('/musdesus/petugas-by-desa', [MusdesusMonitoringController::class, 'getPetugasByDesa']);

// Secure admin-only delete musdesus endpoint for public stats page
Route::delete('/public/musdesus/{id}', [App\Http\Controllers\Api\MusdesusController::class, 'secureDestroy']);

Route::get('/', function () {
    return response()->json(['message' => 'API is running']);
});
