<?php

require_once 'vendor/autoload.php';

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Petugas Monitoring Data ===\n";
$petugasData = DB::table('petugas_monitoring')
    ->where('nama_desa', 'like', '%Tajur%')
    ->orWhere('nama_desa', 'like', '%tajur%')
    ->get(['id', 'nama_desa', 'nama_kecamatan', 'nama_petugas']);

foreach ($petugasData as $petugas) {
    echo "ID: {$petugas->id}\n";
    echo "Nama Desa: '{$petugas->nama_desa}'\n";
    echo "Nama Kecamatan: '{$petugas->nama_kecamatan}'\n";
    echo "Nama Petugas: '{$petugas->nama_petugas}'\n";
    echo "---\n";
}

echo "\n=== Checking Desas Table ===\n";
$desasData = DB::table('desas')
    ->where('nama', 'like', '%Tajur%')
    ->orWhere('nama', 'like', '%tajur%')
    ->get(['id', 'nama', 'kecamatan_id']);

foreach ($desasData as $desa) {
    $kecamatan = DB::table('kecamatans')->where('id', $desa->kecamatan_id)->first();
    echo "ID: {$desa->id}\n";
    echo "Nama Desa: '{$desa->nama}'\n";
    echo "Kecamatan ID: {$desa->kecamatan_id}\n";
    echo "Nama Kecamatan: '{$kecamatan->nama}'\n";
    echo "---\n";
}

echo "\n=== Exact Match Test ===\n";
$petugasTest = DB::table('petugas_monitoring')
    ->where('nama_desa', 'Tajur halang')
    ->where('nama_kecamatan', 'Cijeruk')
    ->first();

if ($petugasTest) {
    echo "Found petugas monitoring with 'Tajur halang'\n";
    echo "Nama Desa: '{$petugasTest->nama_desa}'\n";
    echo "Nama Kecamatan: '{$petugasTest->nama_kecamatan}'\n";
} else {
    echo "No petugas monitoring found with 'Tajur halang'\n";
}

$desaTest = DB::table('desas')
    ->where('nama', 'Tajur halang')
    ->first();

if ($desaTest) {
    echo "\nFound desa with 'Tajur halang'\n";
    echo "Nama Desa: '{$desaTest->nama}'\n";
} else {
    echo "\nNo desa found with 'Tajur halang'\n";
    
    // Check what's actually in desas table
    $desaAlternative = DB::table('desas')
        ->where('nama', 'like', '%Tajur%')
        ->first();
    
    if ($desaAlternative) {
        echo "But found: '{$desaAlternative->nama}'\n";
    }
}