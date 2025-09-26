<?php

// dpmd-backend/setup_database.php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Bidang;
use Database\Seeders\BidangPerjadinSeeder;
use Database\Seeders\PersonilSeeder;

echo "=== SETUP DATABASE PERJADIN ===\n\n";

try {
    // Test connection
    echo "1. Testing database connection...\n";
    DB::connection()->getPdo();
    echo "✓ Database connected successfully\n\n";

    // Check if tables exist
    echo "2. Checking tables...\n";
    
    if (!Schema::hasTable('bidangs')) {
        echo "⚠ Table 'bidangs' not found. Running migration...\n";
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_09_06_092910_create_bidangs_table.php']);
        echo "✓ Table 'bidangs' created\n";
    } else {
        echo "✓ Table 'bidangs' exists\n";
    }
    
    if (!Schema::hasTable('personil')) {
        echo "⚠ Table 'personil' not found. Running migration...\n";
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_09_23_100000_create_personils_table.php']);
        echo "✓ Table 'personil' created\n";
    } else {
        echo "✓ Table 'personil' exists\n";
    }
    
    echo "\n3. Checking table data...\n";
    
    // Check bidangs data
    $bidangCount = DB::table('bidangs')->count();
    echo "Bidangs count: $bidangCount\n";
    
    if ($bidangCount == 0) {
        echo "⚠ No bidangs data found. Running BidangPerjadinSeeder...\n";
        $seeder = new BidangPerjadinSeeder();
        $seeder->run();
        $bidangCount = DB::table('bidangs')->count();
        echo "✓ BidangPerjadinSeeder completed. New count: $bidangCount\n";
    }
    
    // Show bidangs
    echo "\nBidangs data:\n";
    $bidangs = DB::table('bidangs')->get();
    foreach ($bidangs as $bidang) {
        echo "- ID: {$bidang->id}, Nama: {$bidang->nama}\n";
    }
    
    // Check personil data
    $personilCount = DB::table('personil')->count();
    echo "\nPersonil count: $personilCount\n";
    
    if ($personilCount == 0) {
        echo "⚠ No personil data found. Running PersonilSeeder...\n";
        $seeder = new PersonilSeeder();
        $seeder->run();
        $personilCount = DB::table('personil')->count();
        echo "✓ PersonilSeeder completed. New count: $personilCount\n";
    }
    
    echo "\n4. Testing endpoints...\n";
    
    // Test bidang endpoint data
    echo "Testing bidang data for endpoint:\n";
    $bidangs = DB::table('bidangs')->select('id', 'nama')->orderBy('nama')->get();
    echo "Bidang endpoint will return: " . $bidangs->count() . " records\n";
    foreach ($bidangs->take(3) as $bidang) {
        echo "- {$bidang->id}: {$bidang->nama}\n";
    }
    
    // Test personil endpoint data for each bidang
    echo "\nTesting personil data for endpoints:\n";
    foreach ($bidangs->take(3) as $bidang) {
        $personil = DB::table('personil')
            ->where('id_bidang', $bidang->id)
            ->select('id_personil', 'nama_personil')
            ->orderBy('nama_personil')
            ->get();
        echo "Bidang {$bidang->id} ({$bidang->nama}): {$personil->count()} personil\n";
        foreach ($personil->take(2) as $p) {
            echo "  - {$p->id_personil}: {$p->nama_personil}\n";
        }
    }
    
    echo "\n=== SETUP COMPLETED SUCCESSFULLY ===\n";
    echo "Endpoints ready:\n";
    echo "- GET /api/bidang\n";
    echo "- GET /api/personil/{bidang_id}\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
