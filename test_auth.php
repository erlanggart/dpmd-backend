<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test authentication
try {
    $user = User::where('email', 'test@sekretariat.com')->first();
    
    if ($user) {
        echo "User found: " . $user->name . " (Role: " . $user->role . ")\n";
        
        // Test password
        if (password_verify('password', $user->password)) {
            echo "Password verified successfully\n";
            
            // Test token creation
            $token = $user->createToken('test_token')->plainTextToken;
            echo "Token created: " . substr($token, 0, 20) . "...\n";
        } else {
            echo "Password verification failed\n";
        }
    } else {
        echo "User not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
