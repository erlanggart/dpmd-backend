<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

Route::get('/', function () {
    return view('welcome');
});

// Route untuk melayani file storage BUMDes (hanya untuk development/local)
if (app()->environment('local')) {
    Route::get('/storage/app/uploads/{folder}/{filename}', function ($folder, $filename) {
        // Decode URL-encoded filename
        $decodedFilename = urldecode($filename);
        
        // Path langsung ke storage/app/uploads
        $fullPath = storage_path("app/uploads/{$folder}/{$decodedFilename}");
        
        // Log untuk debugging
        Log::info("Trying to serve file: {$fullPath}");
        Log::info("Decoded filename: {$decodedFilename}");
        Log::info("File exists: " . (file_exists($fullPath) ? 'Yes' : 'No'));
        
        if (!file_exists($fullPath)) {
            Log::error("File not found: {$fullPath}");
            abort(404, "File not found: {$fullPath}");
        }
        
        $file = file_get_contents($fullPath);
        $mimeType = mime_content_type($fullPath);
        
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . basename($decodedFilename) . '"')
            ->header('Cache-Control', 'public, max-age=31536000');
    })->where(['folder' => '[a-zA-Z0-9_-]+', 'filename' => '.*']);
}
