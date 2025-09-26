<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Bidang;
use App\Models\Personil;
use Illuminate\Support\Facades\DB;

echo "=== DPMD Database Setup Test ===\n\n";

try {
    // 1. Test database connection
    DB::connection()->getPdo();
    echo "✅ Database connection successful\n";

    // 2. Create bidangs if not exist
    $bidangs = [
        ['nama' => 'Sekretariat'],
        ['nama' => 'Sarana Prasarana Kewilayahan dan Ekonomi Desa'],
        ['nama' => 'Kekayaan dan Keuangan Desa'],
        ['nama' => 'Pemberdayaan Masyarakat Desa'],
        ['nama' => 'Pemerintahan Desa'],
        ['nama' => 'Tenaga Alih Daya'],
        ['nama' => 'Tenaga Keamanan'],
        ['nama' => 'Tenaga Kebersihan'],
    ];

    foreach ($bidangs as $bidang) {
        $exists = Bidang::where('nama', $bidang['nama'])->exists();
        if (!$exists) {
            Bidang::create($bidang);
            echo "✅ Created bidang: {$bidang['nama']}\n";
        } else {
            echo "ℹ️  Bidang already exists: {$bidang['nama']}\n";
        }
    }

    // 3. Check tables exist
    $bidangCount = Bidang::count();
    echo "\n📊 Total bidangs: {$bidangCount}\n";

    if (DB::getSchemaBuilder()->hasTable('personil')) {
        $personilCount = DB::table('personil')->count();
        echo "📊 Total personil: {$personilCount}\n";
    } else {
        echo "❌ Table 'personil' does not exist\n";
    }

    // 4. List bidangs with IDs
    echo "\n📋 Bidang List:\n";
    $allBidangs = Bidang::all();
    foreach ($allBidangs as $bidang) {
        echo "  ID: {$bidang->id} - {$bidang->nama}\n";
    }

    echo "\n=== Test Complete ===\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
