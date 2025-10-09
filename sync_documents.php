<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Bumdes;
use Illuminate\Support\Facades\Storage;

echo "=== SINKRONISASI FILE DOKUMEN BUMDES ===\n\n";

$documentColumns = [
    'LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024',
    'Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 'AnggaranRumahTangga',
    'ProgramKerja', 'SK_BUM_Desa'
];

$bumdesList = Bumdes::all();
$totalFixed = 0;
$totalMissing = 0;
$totalFound = 0;

foreach ($bumdesList as $bumdes) {
    $hasDocuments = false;
    
    foreach ($documentColumns as $column) {
        if (!empty($bumdes->$column)) {
            $hasDocuments = true;
            $filePath = $bumdes->$column;
            $filename = basename($filePath);
            
            // Check if file exists in the specified path
            $fileExists = Storage::disk('public')->exists($filePath);
            
            if (!$fileExists) {
                // Try to find the file in dokumen_badanhukum folder
                $backupPath = 'dokumen_badanhukum/' . $filename;
                if (Storage::disk('public')->exists($backupPath)) {
                    echo "✅ Ditemukan file backup: {$filename} untuk {$bumdes->namabumdesa}\n";
                    
                    // Update database dengan path yang benar
                    $bumdes->$column = $backupPath;
                    $bumdes->save();
                    $totalFixed++;
                } else {
                    // Try to find similar filename
                    $documentsPath = storage_path('app/public/dokumen_badanhukum');
                    if (is_dir($documentsPath)) {
                        $files = glob($documentsPath . '/*.{pdf,doc,docx}', GLOB_BRACE);
                        $found = false;
                        
                        foreach ($files as $file) {
                            $backupFilename = basename($file);
                            
                            // Check if filename contains BUMDes name or desa name
                            $bumdesNameLower = strtolower($bumdes->namabumdesa);
                            $desaNameLower = strtolower($bumdes->desa);
                            $filenameLower = strtolower($backupFilename);
                            
                            if ((strpos($filenameLower, $bumdesNameLower) !== false && strlen($bumdesNameLower) > 5) ||
                                (strpos($filenameLower, $desaNameLower) !== false && strlen($desaNameLower) > 4)) {
                                
                                $newPath = 'dokumen_badanhukum/' . $backupFilename;
                                echo "🔄 Menghubungkan file: {$backupFilename} ke {$bumdes->namabumdesa} ({$column})\n";
                                
                                $bumdes->$column = $newPath;
                                $bumdes->save();
                                $totalFixed++;
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            echo "❌ File hilang: {$filename} untuk {$bumdes->namabumdesa} ({$column})\n";
                            $totalMissing++;
                        }
                    }
                }
            } else {
                $totalFound++;
            }
        }
    }
}

echo "\n=== HASIL SINKRONISASI ===\n";
echo "File yang sudah benar: {$totalFound}\n";
echo "File yang diperbaiki: {$totalFixed}\n";
echo "File yang masih hilang: {$totalMissing}\n";

// Juga scan folder dokumen_badanhukum untuk file yang belum terhubung
echo "\n=== SCAN FILE BACKUP YANG BELUM TERHUBUNG ===\n";
$documentsPath = storage_path('app/public/dokumen_badanhukum');
if (is_dir($documentsPath)) {
    $files = glob($documentsPath . '/*.{pdf,doc,docx}', GLOB_BRACE);
    $unlinkedFiles = [];
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Check if this file is referenced in database
        $isLinked = false;
        foreach ($bumdesList as $bumdes) {
            foreach ($documentColumns as $column) {
                if (!empty($bumdes->$column) && basename($bumdes->$column) === $filename) {
                    $isLinked = true;
                    break 2;
                }
            }
        }
        
        if (!$isLinked) {
            $unlinkedFiles[] = $filename;
        }
    }
    
    echo "File backup yang belum terhubung: " . count($unlinkedFiles) . "\n";
    if (count($unlinkedFiles) > 0) {
        echo "Contoh file yang belum terhubung:\n";
        foreach (array_slice($unlinkedFiles, 0, 10) as $file) {
            echo "   📄 {$file}\n";
        }
        if (count($unlinkedFiles) > 10) {
            echo "   ... dan " . (count($unlinkedFiles) - 10) . " file lainnya\n";
        }
    }
}

echo "\n✅ Sinkronisasi selesai!\n";

?>
