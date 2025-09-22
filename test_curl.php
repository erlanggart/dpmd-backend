<?php
echo "Testing response...\n";

// Test curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
echo "Error: " . $error . "\n";
?>
