<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all BUMDes records
        $bumdesList = DB::table('bumdes')->get();
        
        $fileFields = [
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
        
        $cleanupStats = [
            'total_files_checked' => 0,
            'missing_files_cleaned' => 0,
            'records_updated' => 0
        ];
        
        foreach ($bumdesList as $bumdes) {
            $hasChanges = false;
            $updates = [];
            
            foreach ($fileFields as $field) {
                if (!empty($bumdes->$field)) {
                    $cleanupStats['total_files_checked']++;
                    
                    $filename = basename($bumdes->$field);
                    
                    // Determine folder based on field type
                    $folder = 'dokumen_badanhukum';
                    if (in_array($field, ['LaporanKeuangan2021', 'LaporanKeuangan2022', 'LaporanKeuangan2023', 'LaporanKeuangan2024'])) {
                        $folder = 'laporan_keuangan';
                    }
                    
                    // Check if file exists in storage
                    $storagePath = storage_path('app/uploads/' . $folder . '/' . $filename);
                    $publicPath = public_path('uploads/' . $folder . '/' . $filename);
                    
                    $fileExists = file_exists($storagePath) || file_exists($publicPath);
                    
                    if (!$fileExists) {
                        // File doesn't exist, mark for cleanup
                        $updates[$field] = null;
                        $hasChanges = true;
                        $cleanupStats['missing_files_cleaned']++;
                        
                        echo "Cleaning missing file: {$bumdes->namabumdesa} - {$field}: {$filename}\n";
                    }
                }
            }
            
            // Update record if there are changes
            if ($hasChanges) {
                DB::table('bumdes')
                    ->where('id', $bumdes->id)
                    ->update($updates);
                    
                $cleanupStats['records_updated']++;
                echo "Updated BUMDes: {$bumdes->namabumdesa} (ID: {$bumdes->id})\n";
            }
        }
        
        echo "\n=== CLEANUP SUMMARY ===\n";
        echo "Total files checked: {$cleanupStats['total_files_checked']}\n";
        echo "Missing files cleaned: {$cleanupStats['missing_files_cleaned']}\n";
        echo "Records updated: {$cleanupStats['records_updated']}\n";
        echo "========================\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration as we don't store the original file references
        echo "Cannot reverse file cleanup migration\n";
    }
};