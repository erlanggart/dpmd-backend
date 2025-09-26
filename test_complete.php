<?php

// Test login and API functionality
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== DPMD Authentication Test ===\n\n";

try {
    // 1. Test user exists
    $user = User::where('email', 'test@sekretariat.com')->first();
    
    if (!$user) {
        echo "❌ User not found, creating new user...\n";
        $user = User::create([
            'name' => 'Test Sekretariat',
            'email' => 'test@sekretariat.com', 
            'password' => Hash::make('password'),
            'role' => 'sekretariat'
        ]);
        echo "✅ User created successfully\n";
    } else {
        echo "✅ User found: {$user->name} ({$user->role})\n";
    }
    
    // 2. Test password verification
    if (Hash::check('password', $user->password)) {
        echo "✅ Password verification successful\n";
    } else {
        echo "❌ Password verification failed\n";
        exit(1);
    }
    
    // 3. Test token creation
    $token = $user->createToken('test_token')->plainTextToken;
    echo "✅ Token created: " . substr($token, 0, 20) . "...\n";
    
    // 4. Test API endpoint with token
    $url = 'http://localhost:8000/api/products';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Test - Products endpoint:\n";
    echo "  HTTP Code: {$httpCode}\n";
    echo "  Response length: " . strlen($response) . " characters\n";
    
    if ($httpCode === 200) {
        echo "✅ API endpoint accessible\n";
    } else {
        echo "❌ API endpoint error: {$httpCode}\n";
    }
    
    // 5. Test login endpoint 
    $loginData = json_encode([
        'email' => 'test@sekretariat.com',
        'password' => 'password'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/loginBidang');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $loginResponse = curl_exec($ch);
    $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\nLogin Test - loginBidang endpoint:\n";
    echo "  HTTP Code: {$loginHttpCode}\n";
    echo "  Response: " . substr($loginResponse, 0, 100) . "...\n";
    
    if ($loginHttpCode === 200) {
        echo "✅ Login endpoint working\n";
        $loginData = json_decode($loginResponse, true);
        if (isset($loginData['access_token'])) {
            echo "✅ Token returned in login response\n";
        }
    } else {
        echo "❌ Login endpoint error: {$loginHttpCode}\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
