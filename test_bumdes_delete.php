<?php

echo "=== TEST BUMDES DELETE ENDPOINT ===\n\n";

// First, let's check if there are any BUMDes records
echo "1. Checking BUMDes records...\n";
$getResponse = file_get_contents('http://localhost:8000/api/bumdes');
$bumdesData = json_decode($getResponse, true);

if ($bumdesData && isset($bumdesData['data']) && !empty($bumdesData['data'])) {
    echo "Found " . count($bumdesData['data']) . " BUMDes records\n";
    
    // Get the first record for testing
    $firstBumdes = $bumdesData['data'][0];
    $testId = $firstBumdes['id'];
    
    echo "Testing with BUMDes ID: {$testId} - {$firstBumdes['namabumdesa']}\n\n";
    
    // Test DELETE endpoint
    echo "2. Testing DELETE endpoint...\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'DELETE',
            'header' => 'Content-Type: application/json',
        ]
    ]);
    
    $deleteResponse = file_get_contents("http://localhost:8000/api/bumdes/{$testId}", false, $context);
    
    if ($deleteResponse) {
        echo "Delete Response: " . $deleteResponse . "\n";
        
        // Verify the record was deleted
        echo "\n3. Verifying deletion...\n";
        $verifyResponse = @file_get_contents("http://localhost:8000/api/bumdes/{$testId}");
        
        if ($verifyResponse === false) {
            echo "✅ Record successfully deleted (404 response expected)\n";
        } else {
            echo "❌ Record still exists: " . $verifyResponse . "\n";
        }
    } else {
        echo "❌ Delete request failed\n";
        $error = error_get_last();
        if ($error) {
            echo "Error details: " . $error['message'] . "\n";
        }
    }
} else {
    echo "❌ No BUMDes records found or API error\n";
    echo "Response: " . $getResponse . "\n";
}

echo "\n=== TEST SELESAI ===\n";
