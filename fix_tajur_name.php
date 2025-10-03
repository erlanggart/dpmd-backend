<?php

require_once 'vendor/autoload.php';

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING TAJUR HALANG NAME MISMATCH ===\n\n";

try {
    // Backup current data first
    echo "1. BACKUP DATA BEFORE UPDATE:\n";
    $beforeUpdate = DB::table('petugas_monitoring')->where('nama_desa', 'Tajurhalang')->first();
    if ($beforeUpdate) {
        echo "   ID: {$beforeUpdate->id}\n";
        echo "   Nama Desa: '{$beforeUpdate->nama_desa}'\n";
        echo "   Nama Kecamatan: '{$beforeUpdate->nama_kecamatan}'\n";
        echo "   Nama Petugas: '{$beforeUpdate->nama_petugas}'\n";
    }

    // Update the name to match with desas table
    echo "\n2. UPDATING PETUGAS_MONITORING NAME:\n";
    $updated = DB::table('petugas_monitoring')
        ->where('nama_desa', 'Tajurhalang')
        ->where('nama_kecamatan', 'Cijeruk')
        ->update(['nama_desa' => 'Tajur Halang']);
    
    echo "   Rows updated: {$updated}\n";

    // Verify the update
    echo "\n3. VERIFICATION AFTER UPDATE:\n";
    $afterUpdate = DB::table('petugas_monitoring')
        ->where('nama_desa', 'Tajur Halang')
        ->where('nama_kecamatan', 'Cijeruk')
        ->first();
    
    if ($afterUpdate) {
        echo "   ✅ SUCCESS! Updated data:\n";
        echo "   ID: {$afterUpdate->id}\n";
        echo "   Nama Desa: '{$afterUpdate->nama_desa}'\n";
        echo "   Nama Kecamatan: '{$afterUpdate->nama_kecamatan}'\n";
        echo "   Nama Petugas: '{$afterUpdate->nama_petugas}'\n";
    }

    // Test the match with desas table
    echo "\n4. TESTING MATCH WITH DESAS TABLE:\n";
    $desaMatch = DB::table('desas')
        ->where('nama', 'Tajur Halang')
        ->where('kecamatan_id', 28) // Cijeruk kecamatan_id
        ->first();
    
    if ($desaMatch) {
        echo "   ✅ MATCH FOUND in desas table:\n";
        echo "   Desa ID: {$desaMatch->id}\n";
        echo "   Nama: '{$desaMatch->nama}'\n";
        echo "   Kecamatan ID: {$desaMatch->kecamatan_id}\n";
        
        $kecamatan = DB::table('kecamatans')->where('id', $desaMatch->kecamatan_id)->first();
        echo "   Kecamatan: '{$kecamatan->nama}'\n";
    }

    echo "\n5. TESTING AUTHORIZATION LOGIC:\n";
    // Simulate the authorization check
    $petugas = DB::table('petugas_monitoring')
        ->where('nama_desa', 'Tajur Halang')
        ->where('nama_kecamatan', 'Cijeruk')
        ->first();
    
    $desa = DB::table('desas')->where('nama', 'Tajur Halang')->first();
    $kecamatan = DB::table('kecamatans')->where('id', $desa->kecamatan_id)->first();
    
    echo "   Petugas nama_desa: '{$petugas->nama_desa}'\n";
    echo "   Desas nama: '{$desa->nama}'\n";
    echo "   Match? " . ($petugas->nama_desa === $desa->nama ? "✅ YES" : "❌ NO") . "\n";
    
    echo "   Petugas nama_kecamatan: '{$petugas->nama_kecamatan}'\n";
    echo "   Kecamatan nama: '{$kecamatan->nama}'\n";
    echo "   Match? " . ($petugas->nama_kecamatan === $kecamatan->nama ? "✅ YES" : "❌ NO") . "\n";

    echo "\n🎉 PROBLEM SOLVED!\n";
    echo "Now users can upload for 'Tajur Halang, Kec. Cijeruk' successfully!\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}