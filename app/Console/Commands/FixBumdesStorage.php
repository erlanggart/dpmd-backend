<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixBumdesStorage extends Command
{
    protected $signature = 'bumdes:fix-storage';
    protected $description = 'Fix BUMDES file storage for production deployment';

    public function handle()
    {
        $this->info('🔧 Memperbaiki penyimpanan file BUMDES untuk production...');
        
        // 1. Buat symbolic link untuk storage jika belum ada
        $this->createStorageLink();
        
        // 2. Pindahkan file dari public ke storage jika ada
        $this->migratePublicFiles();
        
        // 3. Set permission yang tepat
        $this->setPermissions();
        
        $this->info('✅ Perbaikan storage BUMDES selesai!');
    }
    
    private function createStorageLink()
    {
        $this->info('📁 Membuat symbolic link storage...');
        
        try {
            // Pastikan folder storage ada
            $storageUploads = storage_path('app/uploads');
            if (!File::exists($storageUploads)) {
                File::makeDirectory($storageUploads, 0755, true);
                $this->info("✅ Folder storage/app/uploads dibuat");
            }
            
            // Buat folder dokumen_badanhukum dan laporan_keuangan
            $folders = ['dokumen_badanhukum', 'laporan_keuangan'];
            foreach ($folders as $folder) {
                $folderPath = $storageUploads . '/' . $folder;
                if (!File::exists($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                    $this->info("✅ Folder {$folder} dibuat");
                }
            }
            
            // Buat symbolic link dari public/storage ke storage/app/public
            $publicStorage = public_path('storage');
            $storagePublic = storage_path('app/public');
            
            if (!File::exists($publicStorage) && File::exists($storagePublic)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows junction
                    exec("mklink /J \"{$publicStorage}\" \"{$storagePublic}\"");
                } else {
                    // Unix symlink
                    symlink($storagePublic, $publicStorage);
                }
                $this->info("✅ Symbolic link storage dibuat");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error membuat symbolic link: " . $e->getMessage());
        }
    }
    
    private function migratePublicFiles()
    {
        $this->info('📦 Migrasi file dari public ke storage...');
        
        $folders = ['dokumen_badanhukum', 'laporan_keuangan'];
        $movedCount = 0;
        
        foreach ($folders as $folder) {
            $publicPath = public_path("uploads/{$folder}");
            $storagePath = storage_path("app/uploads/{$folder}");
            
            if (File::exists($publicPath)) {
                $files = File::files($publicPath);
                
                foreach ($files as $file) {
                    $filename = $file->getFilename();
                    $destPath = $storagePath . '/' . $filename;
                    
                    // Pindahkan file jika belum ada di storage
                    if (!File::exists($destPath)) {
                        try {
                            File::copy($file->getPathname(), $destPath);
                            $movedCount++;
                            $this->line("📄 Moved: {$filename}");
                        } catch (\Exception $e) {
                            $this->error("❌ Error moving {$filename}: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        $this->info("✅ {$movedCount} file berhasil dipindahkan ke storage");
    }
    
    private function setPermissions()
    {
        $this->info('🔐 Mengatur permission file...');
        
        try {
            $storageUploads = storage_path('app/uploads');
            
            if (PHP_OS_FAMILY !== 'Windows') {
                // Set permission untuk folder storage
                chmod($storageUploads, 0755);
                
                $folders = ['dokumen_badanhukum', 'laporan_keuangan'];
                foreach ($folders as $folder) {
                    $folderPath = $storageUploads . '/' . $folder;
                    if (File::exists($folderPath)) {
                        chmod($folderPath, 0755);
                        
                        // Set permission untuk semua file di folder
                        $files = File::files($folderPath);
                        foreach ($files as $file) {
                            chmod($file->getPathname(), 0644);
                        }
                    }
                }
            }
            
            $this->info("✅ Permission berhasil diatur");
            
        } catch (\Exception $e) {
            $this->error("❌ Error setting permissions: " . $e->getMessage());
        }
    }
}