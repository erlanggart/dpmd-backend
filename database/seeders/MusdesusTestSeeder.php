<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Musdesus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MusdesusTestSeeder extends Seeder
{
    public function run()
    {
        // Clear existing test data
        Musdesus::where('nama_desa', 'LIKE', 'Test Desa%')->delete();

        // Create test data
        $testData = [
            [
                'id_kecamatan' => 1,
                'nama_kecamatan' => 'Test Kecamatan A',
                'nama_desa' => 'Test Desa Makmur',
                'tahun' => '2024',
                'nama_file' => 'musdesus_test_1.pdf',
                'status' => 'complete',
                'tanggal_upload' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_kecamatan' => 2,
                'nama_kecamatan' => 'Test Kecamatan B',
                'nama_desa' => 'Test Desa Sejahtera',
                'tahun' => '2024',
                'nama_file' => 'musdesus_test_2.pdf',
                'status' => 'complete',
                'tanggal_upload' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_kecamatan' => 3,
                'nama_kecamatan' => 'Test Kecamatan C',
                'nama_desa' => 'Test Desa Jaya',
                'tahun' => '2024',
                'nama_file' => 'musdesus_test_3.png',
                'status' => 'complete',
                'tanggal_upload' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insert test data
        foreach ($testData as $data) {
            Musdesus::create($data);
        }

        // Create test files in storage
        $this->createTestFiles();

        $this->command->info('Musdesus test data created successfully!');
    }

    private function createTestFiles()
    {
        // Create musdesus directory if not exists
        if (!Storage::disk('public')->exists('musdesus')) {
            Storage::disk('public')->makeDirectory('musdesus');
        }

        // Create dummy PDF content (minimal PDF structure)
        $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test Musdesus File) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000010 00000 n 
0000000079 00000 n 
0000000173 00000 n 
0000000301 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
398
%%EOF';

        // Create dummy PNG content (1x1 pixel transparent PNG)
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        // Save test files
        Storage::disk('public')->put('musdesus/musdesus_test_1.pdf', $pdfContent);
        Storage::disk('public')->put('musdesus/musdesus_test_2.pdf', $pdfContent);
        Storage::disk('public')->put('musdesus/musdesus_test_3.png', $pngContent);

        $this->command->info('Test files created in storage/app/public/musdesus/');
    }
}
