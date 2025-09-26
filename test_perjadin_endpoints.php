<?php

// dpmd-backend/test_perjadin_endpoints.php

// Setup Laravel Bootstrap
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TESTING PERJADIN ENDPOINTS ===\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "✓ Database connected successfully\n\n";

    // Check tables exist
    echo "2. Checking required tables...\n";
    
    $hasBidangs = Schema::hasTable('bidangs');
    $hasPersonil = Schema::hasTable('personil');
    
    echo "- bidangs table: " . ($hasBidangs ? "✓ EXISTS" : "❌ MISSING") . "\n";
    echo "- personil table: " . ($hasPersonil ? "✓ EXISTS" : "❌ MISSING") . "\n\n";
    
    if (!$hasBidangs || !$hasPersonil) {
        echo "❌ Required tables missing. Please run migrations first.\n";
        echo "Commands to run:\n";
        echo "php artisan migrate\n";
        exit(1);
    }

    // Check data in tables
    echo "3. Checking table data...\n";
    
    $bidangCount = DB::table('bidangs')->count();
    $personilCount = DB::table('personil')->count();
    
    echo "- bidangs records: $bidangCount\n";
    echo "- personil records: $personilCount\n\n";
    
    if ($bidangCount == 0) {
        echo "❌ No bidangs data found. Please run BidangPerjadinSeeder.\n";
        echo "Command: php artisan db:seed --class=BidangPerjadinSeeder\n\n";
    }
    
    if ($personilCount == 0) {
        echo "❌ No personil data found. Please run PersonilSeeder.\n";
        echo "Command: php artisan db:seed --class=PersonilSeeder\n\n";
    }

    // Test endpoint data structure
    echo "4. Testing endpoint data structure...\n";
    
    // Test /api/bidang endpoint data
    echo "\n--- /api/bidang endpoint test ---\n";
    $bidangs = DB::table('bidangs')
        ->select('id', 'nama')
        ->orderBy('nama')
        ->get();
    
    echo "Query result count: " . $bidangs->count() . "\n";
    echo "Sample data:\n";
    foreach ($bidangs->take(3) as $bidang) {
        echo "  {\"id\": {$bidang->id}, \"nama\": \"{$bidang->nama}\"}\n";
    }
    
    if ($bidangs->count() > 0) {
        // Test /api/personil/{bidang_id} endpoint data
        echo "\n--- /api/personil/{bidang_id} endpoint test ---\n";
        $firstBidang = $bidangs->first();
        
        $personil = DB::table('personil')
            ->where('id_bidang', $firstBidang->id)
            ->select('id_personil', 'nama_personil')
            ->orderBy('nama_personil')
            ->get();
        
        echo "Query for bidang_id={$firstBidang->id} result count: " . $personil->count() . "\n";
        echo "Sample data:\n";
        foreach ($personil->take(3) as $p) {
            echo "  {\"id_personil\": {$p->id_personil}, \"nama_personil\": \"{$p->nama_personil}\"}\n";
        }
    }
    
    echo "\n5. Summary of required fixes:\n";
    
    $issues = [];
    
    if ($bidangCount == 0) {
        $issues[] = "Run: php artisan db:seed --class=BidangPerjadinSeeder";
    }
    
    if ($personilCount == 0) {
        $issues[] = "Run: php artisan db:seed --class=PersonilSeeder";
    }
    
    if (empty($issues)) {
        echo "✓ All data is ready!\n";
        echo "✓ Endpoints should work correctly:\n";
        echo "  - GET /api/bidang (returns {$bidangCount} records)\n";
        echo "  - GET /api/personil/{bidang_id} (returns personil for specific bidang)\n\n";
        
        echo "Test in browser or Postman:\n";
        echo "- http://localhost/dpmd/dpmd-backend/public/api/bidang\n";
        echo "- http://localhost/dpmd/dpmd-backend/public/api/personil/1\n";
    } else {
        echo "❌ Issues found:\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}

echo "\n=== TEST COMPLETED ===\n";
