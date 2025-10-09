<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Bumdes;
use Illuminate\Support\Facades\Storage;

echo "=== ANALISIS DOKUMEN BUMDES ===\n\n";

// Ambil semua data BUMDes
$bumdes = Bumdes::all();
echo "Total BUMDes: " . $bumdes->count() . "\n\n";

// Kolom dokumen yang ada di database
$documentColumns = [
    'LaporanKeuangan2021',
    'LaporanKeuangan2022', 
    'LaporanKeuangan2023',
    'LaporanKeuangan2024',
    'Perdes',
    'ProfilBUMDesa',
    'BeritaAcara',
    'AnggaranDasar',
    'AnggaranRumahTangga',
    'ProgramKerja',
    'SK_BUM_Desa'
];

$documentsFound = [];
$totalDocuments = 0;

echo "=== DOKUMEN YANG TERSIMPAN DI DATABASE ===\n";
foreach ($bumdes as $bumdesData) {
    $hasDocuments = false;
    $bumdesDocuments = [];
    
    foreach ($documentColumns as $column) {
        if (!empty($bumdesData->$column)) {
            $hasDocuments = true;
            $totalDocuments++;
            
            // Cek apakah file benar-benar ada
            $filePath = $bumdesData->$column;
            $fileExists = Storage::disk('public')->exists($filePath);
            
            $bumdesDocuments[] = [
                'type' => $column,
                'path' => $filePath,
                'exists' => $fileExists
            ];
        }
    }
    
    if ($hasDocuments) {
        $documentsFound[] = [
            'id' => $bumdesData->id,
            'name' => $bumdesData->namabumdesa,
            'desa' => $bumdesData->desa,
            'kecamatan' => $bumdesData->kecamatan,
            'documents' => $bumdesDocuments
        ];
        
        echo "\n📁 {$bumdesData->namabumdesa} ({$bumdesData->desa}, {$bumdesData->kecamatan})\n";
        foreach ($bumdesDocuments as $doc) {
            $status = $doc['exists'] ? '✅' : '❌';
            echo "   {$status} {$doc['type']}: {$doc['path']}\n";
        }
    }
}

echo "\n=== RINGKASAN ===\n";
echo "BUMDes dengan dokumen: " . count($documentsFound) . "\n";
echo "Total dokumen dalam database: {$totalDocuments}\n";

// Cek folder dokumen_badanhukum
echo "\n=== DOKUMEN DI FOLDER BACKUP ===\n";
$backupPath = storage_path('app/public/dokumen_badanhukum');
if (is_dir($backupPath)) {
    $backupFiles = glob($backupPath . '/*.pdf');
    echo "Dokumen backup ditemukan: " . count($backupFiles) . "\n";
    
    if (count($backupFiles) > 0) {
        echo "Contoh file backup:\n";
        foreach (array_slice($backupFiles, 0, 5) as $file) {
            $filename = basename($file);
            $size = filesize($file);
            echo "   📄 {$filename} (" . number_format($size / 1024 / 1024, 2) . " MB)\n";
        }
        if (count($backupFiles) > 5) {
            echo "   ... dan " . (count($backupFiles) - 5) . " file lainnya\n";
        }
    }
} else {
    echo "❌ Folder dokumen_badanhukum tidak ditemukan!\n";
}

echo "\n=== REKOMENDASI ===\n";
echo "1. Sinkronkan dokumen backup dengan data BUMDes\n";
echo "2. Update endpoint API untuk menampilkan dokumen yang tepat\n";
echo "3. Pastikan semua file dapat diakses melalui URL\n";

?>
