<?php

require_once 'vendor/autoload.php';

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DETAILED ANALYSIS ===\n\n";

echo "1. Data di tabel PETUGAS_MONITORING:\n";
$petugasData = DB::table('petugas_monitoring')
    ->where('nama_desa', 'like', '%Tajur%')
    ->get(['id', 'nama_desa', 'nama_kecamatan']);

foreach ($petugasData as $petugas) {
    echo "   ID: {$petugas->id} | Desa: '{$petugas->nama_desa}' | Kecamatan: '{$petugas->nama_kecamatan}'\n";
}

echo "\n2. Data di tabel DESAS:\n";
$desasData = DB::table('desas')
    ->where('nama', 'like', '%Tajur%')
    ->get(['id', 'nama', 'kecamatan_id']);

foreach ($desasData as $desa) {
    $kecamatan = DB::table('kecamatans')->where('id', $desa->kecamatan_id)->first();
    echo "   ID: {$desa->id} | Desa: '{$desa->nama}' | Kecamatan: '{$kecamatan->nama}'\n";
}

echo "\n3. MASALAH YANG DITEMUKAN:\n";
echo "   - Petugas monitoring: nama_desa = 'Tajurhalang' (tanpa spasi)\n";
echo "   - Desas table: nama = 'Tajur Halang' (dengan spasi)\n";
echo "   - Ketika user memilih 'Tajur Halang' dari frontend, sistem mencari petugas dengan nama 'Tajur Halang'\n";
echo "   - Tapi petugas monitoring masih bernama 'Tajurhalang'\n";

echo "\n4. SOLUSI YANG DIPERLUKAN:\n";
echo "   Update petugas_monitoring.nama_desa dari 'Tajurhalang' menjadi 'Tajur Halang'\n";

echo "\n5. UPDATE QUERY:\n";
echo "   UPDATE petugas_monitoring SET nama_desa = 'Tajur Halang' WHERE nama_desa = 'Tajurhalang';\n";

// Cek current petugas monitoring untuk Tajurhalang
$currentPetugas = DB::table('petugas_monitoring')->where('nama_desa', 'Tajurhalang')->first();
if ($currentPetugas) {
    echo "\n6. CURRENT PETUGAS DATA:\n";
    echo "   ID: {$currentPetugas->id}\n";
    echo "   Nama Desa: '{$currentPetugas->nama_desa}'\n";
    echo "   Nama Kecamatan: '{$currentPetugas->nama_kecamatan}'\n";
    echo "   Nama Petugas: '{$currentPetugas->nama_petugas}'\n";
}

// Test if we can find desa Tajur Halang
$targetDesa = DB::table('desas')->where('nama', 'Tajur Halang')->first();
if ($targetDesa) {
    echo "\n7. TARGET DESA DATA:\n";
    echo "   ID: {$targetDesa->id}\n";
    echo "   Nama: '{$targetDesa->nama}'\n";
    echo "   Kecamatan ID: {$targetDesa->kecamatan_id}\n";
}