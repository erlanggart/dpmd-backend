<?php

// dpmd-backend/fix_seeder_sync.php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Database\Seeders\BidangPerjadinSeeder;
use Database\Seeders\PersonilSeeder;

echo "=== FIXING SEEDER SYNCHRONIZATION ===\n\n";

try {
    echo "1. Truncating existing data...\n";
    DB::table('personil')->truncate();
    DB::table('bidangs')->truncate();
    echo "✓ Tables cleared\n\n";

    echo "2. Running BidangPerjadinSeeder...\n";
    $bidangSeeder = new BidangPerjadinSeeder();
    $bidangSeeder->run();
    
    $bidangCount = DB::table('bidangs')->count();
    echo "✓ BidangPerjadinSeeder completed: {$bidangCount} bidangs created\n";
    
    // Show created bidangs
    $bidangs = DB::table('bidangs')->orderBy('id')->get();
    foreach ($bidangs as $bidang) {
        echo "  - ID: {$bidang->id}, Nama: {$bidang->nama}\n";
    }
    echo "\n";

    echo "3. Running PersonilSeeder...\n";
    $personilSeeder = new PersonilSeeder();
    $personilSeeder->run();
    
    $personilCount = DB::table('personil')->count();
    echo "✓ PersonilSeeder completed: {$personilCount} personil created\n\n";

    echo "4. Verifying synchronization...\n";
    
    // Check distribution
    echo "Personil distribution by bidang:\n";
    foreach ($bidangs as $bidang) {
        $count = DB::table('personil')->where('id_bidang', $bidang->id)->count();
        echo "  {$bidang->nama}: {$count} personil\n";
    }
    
    // Check for orphaned records
    $orphaned = DB::table('personil')
        ->leftJoin('bidangs', 'personil.id_bidang', '=', 'bidangs.id')
        ->whereNull('bidangs.id')
        ->count();
    
    echo "\nOrphaned personil records: {$orphaned}\n";
    
    if ($orphaned == 0 && $bidangCount == 8 && $personilCount > 0) {
        echo "\n✅ SYNCHRONIZATION SUCCESSFUL!\n";
        echo "✓ 8 bidangs created and populated\n";
        echo "✓ {$personilCount} personil created with valid bidang references\n";
        echo "✓ No orphaned records found\n";
        echo "\nEndpoints are now ready:\n";
        echo "- GET /api/bidang (returns {$bidangCount} bidangs)\n";
        echo "- GET /api/personil/{bidang_id} (returns personil for each bidang)\n";
    } else {
        echo "\n❌ SYNCHRONIZATION FAILED!\n";
        echo "Issues:\n";
        if ($bidangCount != 8) echo "  - Expected 8 bidangs, got {$bidangCount}\n";
        if ($personilCount == 0) echo "  - No personil created\n";
        if ($orphaned > 0) echo "  - {$orphaned} orphaned personil records\n";
    }

} catch (Exception $e) {
    echo "❌ Error during synchronization: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    
    if (strpos($e->getMessage(), 'not found') !== false) {
        echo "\n🔧 Solution: Make sure BidangPerjadinSeeder runs before PersonilSeeder\n";
        echo "Commands:\n";
        echo "1. php artisan db:seed --class=BidangPerjadinSeeder\n";
        echo "2. php artisan db:seed --class=PersonilSeeder\n";
    }
}

echo "\n=== FIX COMPLETED ===\n";
