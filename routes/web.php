<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth']], function () {

    // Hanya bisa diakses oleh superadmin
    Route::get('/dashboard-superadmin', function () {
        return view('dashboard.superadmin');
    })->middleware('role:superadmin');

    // Bisa diakses oleh admin bidang ATAU superadmin
    Route::get('/laporan-bidang', function () {
        return view('laporan.bidang');
    })->middleware('role:admin bidang|superadmin');

    // Route group untuk semua admin
    Route::group(['prefix' => 'admin', 'middleware' => ['role:superadmin|admin bidang|admin kecamatan']], function () {
        // Route di dalam sini hanya bisa diakses oleh peran yang disebutkan
    });
});
