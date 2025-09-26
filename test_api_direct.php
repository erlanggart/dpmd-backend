<?php

// dpmd-backend/test_api_direct.php
// Test API endpoints directly without authentication

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Perjadin\BidangController as PerjadinBidangController;
use App\Http\Controllers\Api\Perjadin\PersonilController as PerjadinPersonilController;

echo "=== DIRECT API ENDPOINT TEST ===\n\n";

try {
    echo "1. Testing BidangController::index()...\n";
    $bidangController = new PerjadinBidangController();
    $bidangResponse = $bidangController->index();
    $bidangData = json_decode($bidangResponse->getContent(), true);
    
    echo "Response status: " . $bidangResponse->getStatusCode() . "\n";
    echo "Data count: " . count($bidangData) . "\n";
    echo "Sample data:\n";
    foreach (array_slice($bidangData, 0, 3) as $bidang) {
        echo "  - ID: {$bidang['id']}, Nama: {$bidang['nama']}\n";
    }
    echo "\n";

    if (count($bidangData) > 0) {
        echo "2. Testing PersonilController::getByBidang()...\n";
        $personilController = new PerjadinPersonilController();
        $firstBidangId = $bidangData[0]['id'];
        
        $personilResponse = $personilController->getByBidang($firstBidangId);
        $personilData = json_decode($personilResponse->getContent(), true);
        
        echo "Response status: " . $personilResponse->getStatusCode() . "\n";
        echo "Data count for bidang_id=$firstBidangId: " . count($personilData) . "\n";
        echo "Sample data:\n";
        foreach (array_slice($personilData, 0, 3) as $personil) {
            echo "  - ID: {$personil['id_personil']}, Nama: {$personil['nama_personil']}\n";
        }
    } else {
        echo "❌ No bidang data found, skipping personil test\n";
    }
    
    echo "\n=== ENDPOINT TEST RESULTS ===\n";
    echo "✓ BidangController working: " . (count($bidangData) > 0 ? "YES" : "NO") . "\n";
    echo "✓ PersonilController working: " . (isset($personilData) && count($personilData) > 0 ? "YES" : "NO") . "\n";
    
    if (count($bidangData) > 0) {
        echo "\n✓ API endpoints are ready!\n";
        echo "Frontend should be able to call:\n";
        echo "- /api/bidang (returns " . count($bidangData) . " bidangs)\n";
        echo "- /api/personil/{bidang_id} (returns personil for bidang)\n";
    } else {
        echo "\n❌ Database is empty. Run seeders first!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}

echo "\n=== TEST COMPLETED ===\n";
