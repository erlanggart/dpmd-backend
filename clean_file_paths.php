<?php
/**
 * Script untuk membersihkan path file di desk_bumdes2025.json
 * Menghapus prefix folder uploads/dokumen_bumdes/ dan laporan_keuangan/
 */

function cleanFilePath($path) {
    if (empty($path)) {
        return $path;
    }
    
    // Hapus prefix uploads/dokumen_bumdes/
    if (strpos($path, 'uploads/dokumen_bumdes/') === 0) {
        return str_replace('uploads/dokumen_bumdes/', '', $path);
    }
    
    // Hapus prefix laporan_keuangan/
    if (strpos($path, 'laporan_keuangan/') === 0) {
        return str_replace('laporan_keuangan/', '', $path);
    }
    
    return $path;
}

function cleanBumdesData(&$data) {
    $cleaned = false;
    
    // Jika ada key laporan_keuangan
    if (isset($data['laporan_keuangan']) && is_array($data['laporan_keuangan'])) {
        foreach ($data['laporan_keuangan'] as $key => &$value) {
            if (is_string($value)) {
                $originalValue = $value;
                $value = cleanFilePath($value);
                if ($originalValue !== $value) {
                    $cleaned = true;
                }
            }
        }
    }
    
    // Bersihkan field file lainnya jika ada
    $fileFields = [
        'FileBadanHukum', 'FilePerdes', 'FileSK', 'FileNIB', 'FileLKPP', 
        'FileNPWP', 'FileLaporanKeuangan', 'FileAkta'
    ];
    
    foreach ($fileFields as $field) {
        if (isset($data[$field]) && is_string($data[$field])) {
            $originalValue = $data[$field];
            $data[$field] = cleanFilePath($data[$field]);
            if ($originalValue !== $data[$field]) {
                $cleaned = true;
            }
        }
    }
    
    return $cleaned;
}

function main() {
    $inputFile = 'desk_bumdes2025.json';
    $backupFile = 'desk_bumdes2025_backup.json';
    
    try {
        // Backup file asli
        if (file_exists($inputFile)) {
            echo "Membuat backup: $backupFile\n";
            copy($inputFile, $backupFile);
        }
        
        // Baca file JSON
        echo "Membaca file: $inputFile\n";
        $jsonContent = file_get_contents($inputFile);
        $data = json_decode($jsonContent, true);
        
        if ($data === null) {
            throw new Exception("Error decoding JSON file");
        }
        
        echo "Total data BUMDes: " . count($data) . "\n";
        
        // Bersihkan data
        $cleanedCount = 0;
        foreach ($data as $i => &$bumdes) {
            if (cleanBumdesData($bumdes)) {
                $cleanedCount++;
                $namabumdesa = isset($bumdes['namabumdesa']) ? $bumdes['namabumdesa'] : 'Unknown';
                echo "Membersihkan data BUMDes #" . ($i+1) . ": $namabumdesa\n";
            }
        }
        
        // Simpan file yang sudah dibersihkan
        echo "Menyimpan file yang sudah dibersihkan: $inputFile\n";
        $cleanedJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($inputFile, $cleanedJson);
        
        echo "\n✅ Selesai!\n";
        echo "📊 Total data yang dibersihkan: $cleanedCount\n";
        echo "💾 File backup: $backupFile\n";
        echo "✨ File bersih: $inputFile\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

main();
?>
