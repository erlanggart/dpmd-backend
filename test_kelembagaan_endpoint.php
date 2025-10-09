<?php
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

echo "Testing Kelembagaan API Endpoint\n";
echo "================================\n\n";

// Simulate authenticated request
$url = 'http://127.0.0.1:8001/api/kelembagaan';

// Create a simple request without authentication for testing structure
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 401) {
    echo "\nExpected 401 - Authentication required. This is correct behavior.\n";
    echo "The endpoint is properly protected.\n";
} else {
    echo "\nUnexpected response code.\n";
}

// Test structure by checking routes
echo "\n\nChecking available routes:\n";
echo "==========================\n";

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get route list
exec('cd "' . __DIR__ . '" && php artisan route:list --name=kelembagaan', $output);
foreach ($output as $line) {
    echo $line . "\n";
}
