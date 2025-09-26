<?php

// dpmd-backend/check_seeder_sync.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== CHECKING SEEDER SYNCHRONIZATION ===\n\n";

try {
    // Cek data bidangs yang ada
    echo "1. Checking bidangs data...\n";
    $bidangs = DB::table('bidangs')->orderBy('id')->get();
    
    if ($bidangs->count() == 0) {
        echo "❌ No bidangs found! Run BidangPerjadinSeeder first.\n";
        exit(1);
    }
    
    echo "Found {$bidangs->count()} bidangs:\n";
    foreach ($bidangs as $bidang) {
        echo "  ID: {$bidang->id} - {$bidang->nama}\n";
    }
    
    echo "\n2. Checking PersonilSeeder expected bidang names...\n";
    $expectedBidangs = [
        'Sekretariat',
        'Sarana Prasarana Kewilayahan dan Ekonomi Desa',
        'Kekayaan dan Keuangan Desa',
        'Pemberdayaan Masyarakat Desa',
        'Pemerintahan Desa',
        'Tenaga Alih Daya',
        'Tenaga Keamanan',
        'Tenaga Kebersihan'
    ];
    
    $missingBidangs = [];
    foreach ($expectedBidangs as $expectedNama) {
        $found = $bidangs->where('nama', $expectedNama)->first();
        if ($found) {
            echo "  ✓ Found: {$expectedNama} (ID: {$found->id})\n";
        } else {
            echo "  ❌ Missing: {$expectedNama}\n";
            $missingBidangs[] = $expectedNama;
        }
    }
    
    if (!empty($missingBidangs)) {
        echo "\n❌ SYNC PROBLEM: Missing bidangs found!\n";
        echo "Missing bidangs:\n";
        foreach ($missingBidangs as $missing) {
            echo "  - $missing\n";
        }
        echo "\nSolution: Re-run BidangPerjadinSeeder\n";
        echo "Command: php artisan db:seed --class=BidangPerjadinSeeder\n";
        exit(1);
    }
    
    echo "\n3. Checking personil data distribution...\n";
    $personilCount = DB::table('personil')->count();
    echo "Total personil: $personilCount\n";
    
    if ($personilCount == 0) {
        echo "❌ No personil found! PersonilSeeder failed or not run.\n";
        echo "Command: php artisan db:seed --class=PersonilSeeder\n";
        exit(1);
    }
    
    echo "\nPersonil distribution by bidang:\n";
    foreach ($bidangs as $bidang) {
        $count = DB::table('personil')->where('id_bidang', $bidang->id)->count();
        echo "  {$bidang->nama}: {$count} personil\n";
    }
    
    // Cek untuk NULL atau invalid bidang references
    echo "\n4. Checking for data integrity issues...\n";
    $invalidPersonil = DB::table('personil')
        ->leftJoin('bidangs', 'personil.id_bidang', '=', 'bidangs.id')
        ->whereNull('bidangs.id')
        ->count();
    
    if ($invalidPersonil > 0) {
        echo "❌ Found {$invalidPersonil} personil with invalid bidang references!\n";
        echo "Solution: Truncate personil and re-run PersonilSeeder\n";
    } else {
        echo "✓ All personil have valid bidang references\n";
    }
    
    echo "\n=== SYNC CHECK RESULTS ===\n";
    if (empty($missingBidangs) && $personilCount > 0 && $invalidPersonil == 0) {
        echo "✅ Seeders are synchronized correctly!\n";
        echo "✓ All 8 bidangs exist\n";
        echo "✓ All personil have valid bidang references\n";
        echo "✓ Endpoints should work properly\n";
    } else {
        echo "❌ Synchronization issues found. See above for solutions.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}

echo "\n=== CHECK COMPLETED ===\n";
