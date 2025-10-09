<?php

/**
 * Test script untuk verifikasi avatar upload dengan public_uploads disk
 * Jalankan dengan: php test_avatar_upload.php
 */

require_once 'vendor/autoload.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "Testing Avatar Upload Configuration\n";
echo "==================================\n\n";

try {
    // Test 1: Cek konfigurasi disk public_uploads
    echo "1. Checking public_uploads disk configuration:\n";
    $config = config('filesystems.disks.public_uploads');
    echo "   Root: " . $config['root'] . "\n";
    echo "   URL: " . $config['url'] . "\n";
    echo "   Visibility: " . $config['visibility'] . "\n";

    // Test 2: Cek apakah folder uploads ada
    echo "\n2. Checking uploads folder structure:\n";
    $uploadsPath = storage_path('app/uploads');
    $avatarsPath = storage_path('app/uploads/avatars');

    echo "   Uploads folder exists: " . (is_dir($uploadsPath) ? "✓ YES" : "✗ NO") . "\n";
    echo "   Avatars folder exists: " . (is_dir($avatarsPath) ? "✓ YES" : "✗ NO") . "\n";

    // Test 3: Cek symlink public/uploads
    echo "\n3. Checking public symlink:\n";
    $publicUploadsLink = public_path('uploads');
    echo "   Public/uploads link exists: " . (is_link($publicUploadsLink) || is_dir($publicUploadsLink) ? "✓ YES" : "✗ NO") . "\n";

    if (is_link($publicUploadsLink)) {
        echo "   Link target: " . readlink($publicUploadsLink) . "\n";
    }

    // Test 4: Cek permissions
    echo "\n4. Checking permissions:\n";
    echo "   Uploads folder writable: " . (is_writable($uploadsPath) ? "✓ YES" : "✗ NO") . "\n";
    echo "   Avatars folder writable: " . (is_writable($avatarsPath) ? "✓ YES" : "✗ NO") . "\n";

    // Test 5: Simulasi path yang akan digunakan
    echo "\n5. Avatar URL simulation:\n";
    $avatarPath = "avatars/sample-avatar.jpg";
    $fullUrl = env('APP_URL') . "/uploads/" . $avatarPath;
    echo "   Sample avatar path: {$avatarPath}\n";
    echo "   Full URL: {$fullUrl}\n";
    echo "   Backend storage path: " . storage_path("app/uploads/{$avatarPath}") . "\n";

    echo "\n✅ Configuration Analysis Complete!\n";
    echo "\nNext steps:\n";
    echo "1. Make sure 'php artisan storage:link' has been run\n";
    echo "2. Test actual file upload through the API\n";
    echo "3. Verify avatar URLs are accessible via browser\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
