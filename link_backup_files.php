<?php

// Bootstrap Laravel dengan benar
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Bumdes;

echo "=== MENGHUBUNGKAN FILE BACKUP KE DATABASE ===\n\n";

try {
    // Path ke folder backup
    $backupPath = storage_path('app/public/dokumen_badanhukum');
    
    if (!is_dir($backupPath)) {
        echo "❌ Folder backup tidak ditemukan: $backupPath\n";
        exit(1);
    }
    
    // Scan file backup
    $backupFiles = [];
    $files = scandir($backupPath);
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($backupPath . '/' . $file)) {
            $backupFiles[] = $file;
        }
    }
    
    echo "File backup yang ditemukan: " . count($backupFiles) . "\n\n";
    
    $linkedCount = 0;
    $skippedCount = 0;
    
    // Get semua BUMDes untuk referensi
    $allBumdes = Bumdes::all();
    $bumdesNamesMap = [];
    foreach ($allBumdes as $bumdes) {
        $cleanName = strtolower(trim($bumdes->nama_bumdes));
        $bumdesNamesMap[$cleanName] = $bumdes;
    }
    
    foreach ($backupFiles as $filename) {
        // Skip jika file sudah ada di database
        $existsInDb = false;
        foreach ($allBumdes as $bumdes) {
            $columns = [
                'laporan_keuangan_2021', 'laporan_keuangan_2022', 'laporan_keuangan_2023', 'laporan_keuangan_2024',
                'perdes', 'profil_bumdesa', 'berita_acara', 'anggaran_dasar', 'anggaran_rumah_tangga', 
                'program_kerja', 'sk_bum_desa'
            ];
            
            foreach ($columns as $column) {
                if ($bumdes->$column && basename($bumdes->$column) === $filename) {
                    $existsInDb = true;
                    break 2;
                }
            }
        }
        
        if ($existsInDb) {
            $skippedCount++;
            continue;
        }
        
        echo "🔍 Mencari match untuk: $filename\n";
        
        // Strategi matching berdasarkan nama file
        $matchedBumdes = null;
        $matchedColumn = null;
        
        // Ekstrak informasi dari nama file
        $lowerFilename = strtolower($filename);
        
        // Mapping kata kunci ke kolom database  
        $keywordToColumn = [
            'laporan' => 'laporan_keuangan_2021',
            'keuangan' => 'laporan_keuangan_2021',
            'perdes' => 'perdes',
            'profil' => 'profil_bumdesa',
            'berita' => 'berita_acara',
            'acara' => 'berita_acara',
            'musdes' => 'berita_acara',
            'anggaran dasar' => 'anggaran_dasar',
            'anggaran_dasar' => 'anggaran_dasar',
            'ad ' => 'anggaran_dasar',
            'anggaran rumah tangga' => 'anggaran_rumah_tangga',
            'anggaran_rumah_tangga' => 'anggaran_rumah_tangga',
            'art ' => 'anggaran_rumah_tangga',
            'program' => 'program_kerja',
            'kerja' => 'program_kerja',
            'proker' => 'program_kerja',
            'sk ' => 'sk_bum_desa',
            'surat keputusan' => 'sk_bum_desa'
        ];
        
        // Deteksi jenis dokumen
        foreach ($keywordToColumn as $keyword => $column) {
            if (strpos($lowerFilename, $keyword) !== false) {
                $matchedColumn = $column;
                break;
            }
        }
        
        if (!$matchedColumn) {
            echo "   ❌ Tidak dapat menentukan jenis dokumen\n";
            continue;
        }
        
        // Cari BUMDes yang paling cocok berdasarkan nama dalam file
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($bumdesNamesMap as $cleanBumdesName => $bumdes) {
            // Skip jika kolom sudah terisi
            if (!empty($bumdes->$matchedColumn)) {
                continue;
            }
            
            $score = 0;
            
            // Pisah nama BUMDes menjadi kata-kata
            $bumdesWords = preg_split('/\s+/', $cleanBumdesName);
            
            foreach ($bumdesWords as $word) {
                if (strlen($word) > 3 && strpos($lowerFilename, $word) !== false) {
                    $score += strlen($word); // Skor berdasarkan panjang kata yang cocok
                }  
            }
            
            // Bonus jika nama desa cocok
            if ($bumdes->desa && strpos($lowerFilename, strtolower($bumdes->desa)) !== false) {
                $score += 10;
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $bumdes;
            }
        }
        
        if ($bestMatch && $bestScore > 5) {
            // Update database
            $relativePath = 'dokumen_badanhukum/' . $filename;
            
            try {
                $bestMatch->update([
                    $matchedColumn => $relativePath
                ]);
                
                echo "   ✅ Terhubung ke: {$bestMatch->nama_bumdes} -> $matchedColumn\n";
                echo "      File: $relativePath\n";
                $linkedCount++;
                
            } catch (Exception $e) {
                echo "   ❌ Error updating database: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ⚠️  Tidak ada BUMDes yang cocok (score: $bestScore)\n";
        }
        
        echo "\n";
    }
    
    echo "\n=== HASIL LINKING FILE BACKUP ===\n";
    echo "File yang berhasil dihubungkan: $linkedCount\n";
    echo "File yang dilewati (sudah ada): $skippedCount\n";
    echo "Total file backup: " . count($backupFiles) . "\n";
    
    // Verifikasi hasil
    echo "\n=== VERIFIKASI HASIL ===\n";
    $bumdesWithDocs = Bumdes::where(function($query) {
        $query->whereNotNull('laporan_keuangan_2021')
              ->orWhereNotNull('laporan_keuangan_2022')
              ->orWhereNotNull('laporan_keuangan_2023')
              ->orWhereNotNull('laporan_keuangan_2024')
              ->orWhereNotNull('perdes')
              ->orWhereNotNull('profil_bumdesa')
              ->orWhereNotNull('berita_acara')
              ->orWhereNotNull('anggaran_dasar')
              ->orWhereNotNull('anggaran_rumah_tangga')
              ->orWhereNotNull('program_kerja')
              ->orWhereNotNull('sk_bum_desa');
    })->count();
    
    echo "BUMDes yang memiliki dokumen: $bumdesWithDocs\n";
    
    // Hitung file yang masih tersedia
    $availableFiles = 0;
    $missingFiles = 0;
    
    foreach (Bumdes::all() as $bumdes) {
        $columns = [
            'laporan_keuangan_2021', 'laporan_keuangan_2022', 'laporan_keuangan_2023', 'laporan_keuangan_2024',
            'perdes', 'profil_bumdesa', 'berita_acara', 'anggaran_dasar', 'anggaran_rumah_tangga', 
            'program_kerja', 'sk_bum_desa'
        ];
        
        foreach ($columns as $column) {
            if (!empty($bumdes->$column)) {
                $filePath = storage_path('app/public/' . $bumdes->$column);
                if (file_exists($filePath)) {
                    $availableFiles++;
                } else {
                    $missingFiles++;
                }
            }
        }
    }
    
    echo "File yang tersedia: $availableFiles\n";
    echo "File yang masih hilang: $missingFiles\n";
    
    echo "\n✅ Proses linking selesai!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
