<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Creating test user and token for API testing...\n";
echo "===============================================\n\n";

try {
    // Create or find a test user
    $user = User::firstOrCreate(
        ['email' => 'test@kelembagaan.test'],
        [
            'name' => 'Test Kelembagaan User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'superadmin'
        ]
    );

    // Create token
    $token = $user->createToken('kelembagaan-test')->plainTextToken;

    echo "Test user created/found:\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
    echo "Token: {$token}\n\n";

    // Test API call
    echo "Testing API call...\n";
    echo "==================\n";

    $url = 'http://127.0.0.1:8001/api/kelembagaan';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: {$httpCode}\n";

    if ($httpCode === 200) {
        echo "SUCCESS! API endpoint is working.\n\n";

        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "Response structure looks good:\n";
            echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
            echo "Data count: " . (isset($data['data']) ? count($data['data']) : 0) . " kecamatan\n";

            // Show first kecamatan structure if available
            if (isset($data['data'][0])) {
                echo "\nFirst kecamatan structure:\n";
                $firstKec = $data['data'][0];
                echo "- ID: " . ($firstKec['id'] ?? 'N/A') . "\n";
                echo "- Nama: " . ($firstKec['nama'] ?? 'N/A') . "\n";
                echo "- Desa count: " . (isset($firstKec['desas']) ? count($firstKec['desas']) : 0) . "\n";

                if (isset($firstKec['totalKelembagaan'])) {
                    echo "- Total Kelembagaan:\n";
                    foreach ($firstKec['totalKelembagaan'] as $key => $value) {
                        echo "  * {$key}: {$value}\n";
                    }
                }

                if (isset($firstKec['desas'][0])) {
                    echo "- First desa:\n";
                    $firstDesa = $firstKec['desas'][0];
                    echo "  * ID: " . ($firstDesa['id'] ?? 'N/A') . "\n";
                    echo "  * Nama: " . ($firstDesa['nama'] ?? 'N/A') . "\n";
                    echo "  * Status Pemerintahan: " . ($firstDesa['status_pemerintahan'] ?? 'N/A') . "\n";

                    if (isset($firstDesa['kelembagaan'])) {
                        echo "  * Kelembagaan:\n";
                        foreach ($firstDesa['kelembagaan'] as $key => $value) {
                            echo "    - {$key}: {$value}\n";
                        }
                    }
                }
            }
        } else {
            echo "Response format issue:\n";
            echo substr($response, 0, 500) . "...\n";
        }
    } else {
        echo "ERROR: HTTP {$httpCode}\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
