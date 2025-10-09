<?php

/**
 * Script untuk mengonversi SQL INSERT statements menjadi PHP array
 * untuk BumdesTableSeeder.php
 */

// Pastikan file bumdes.sql ada
$sqlFile = 'bumdes.sql';
if (!file_exists($sqlFile)) {
    echo "Error: File {$sqlFile} tidak ditemukan!\n";
    echo "Letakkan file bumdes.sql di direktori yang sama dengan script ini.\n";
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);

// Pattern untuk mencari INSERT statements
$pattern = '/INSERT\s+INTO\s+`?bumdes`?\s*\([^)]+\)\s*VALUES\s*\(([^;]+)\);?/i';

if (!preg_match_all($pattern, $sqlContent, $matches)) {
    echo "Error: Tidak ditemukan INSERT statements dalam file SQL!\n";
    exit(1);
}

echo "Ditemukan " . count($matches[1]) . " record INSERT statements.\n\n";

// Kolom-kolom yang diharapkan (sesuai dengan seeder)
$expectedColumns = [
    'kecamatan', 'desa', 'namabumdesa', 'status', 'keterangan_tidak_aktif',
    'NIB', 'LKPP', 'NPWP', 'badanhukum', 'NamaPenasihat', 'JenisKelaminPenasihat',
    'HPPenasihat', 'NamaPengawas', 'JenisKelaminPengawas', 'HPPengawas',
    'NamaDirektur', 'JenisKelaminDirektur', 'HPDirektur', 'NamaSekretaris',
    'JenisKelaminSekretaris', 'HPSekretaris', 'NamaBendahara', 'JenisKelaminBendahara',
    'HPBendahara', 'TahunPendirian', 'AlamatBumdesa', 'Alamatemail',
    'TotalTenagaKerja', 'TelfonBumdes', 'JenisUsaha', 'JenisUsahaUtama',
    'JenisUsahaLainnya', 'Omset2023', 'Laba2023', 'Omset2024', 'Laba2024',
    'PenyertaanModal2019', 'PenyertaanModal2020', 'PenyertaanModal2021',
    'PenyertaanModal2022', 'PenyertaanModal2023', 'PenyertaanModal2024',
    'SumberLain', 'JenisAset', 'NilaiAset', 'KerjasamaPihakKetiga',
    'TahunMulai-TahunBerakhir', 'KontribusiTerhadapPADes2021',
    'KontribusiTerhadapPADes2022', 'KontribusiTerhadapPADes2023',
    'KontribusiTerhadapPADes2024', 'Ketapang2024', 'Ketapang2025',
    'DesaWisata', 'BantuanKementrian', 'BantuanLaptopShopee', 'NomorPerdes'
];

// Ekstrak nama kolom dari SQL jika ada
$columnPattern = '/INSERT\s+INTO\s+`?bumdes`?\s*\(([^)]+)\)/i';
if (preg_match($columnPattern, $sqlContent, $columnMatch)) {
    $columnString = $columnMatch[1];
    $columns = array_map('trim', explode(',', $columnString));
    $columns = array_map(function($col) {
        return trim($col, '`');
    }, $columns);
    echo "Kolom yang ditemukan: " . implode(', ', $columns) . "\n\n";
} else {
    echo "Menggunakan kolom default dari seeder.\n\n";
    $columns = $expectedColumns;
}

$phpArray = [];

foreach ($matches[1] as $index => $valueString) {
    // Parse values dari string
    $values = [];
    $current = '';
    $inQuotes = false;
    $quoteChar = '';
    
    for ($i = 0; $i < strlen($valueString); $i++) {
        $char = $valueString[$i];
        
        if (!$inQuotes && ($char === '"' || $char === "'")) {
            $inQuotes = true;
            $quoteChar = $char;
        } elseif ($inQuotes && $char === $quoteChar) {
            $inQuotes = false;
            $quoteChar = '';
        } elseif (!$inQuotes && $char === ',') {
            $values[] = trim($current);
            $current = '';
            continue;
        }
        
        $current .= $char;
    }
    
    if (trim($current) !== '') {
        $values[] = trim($current);
    }
    
    // Bersihkan values
    $cleanValues = [];
    foreach ($values as $value) {
        $value = trim($value);
        
        if ($value === 'NULL' || $value === 'null') {
            $cleanValues[] = null;
        } elseif (preg_match('/^["\'](.*)["\']\s*$/', $value, $match)) {
            $cleanValues[] = $match[1];
        } elseif (is_numeric($value)) {
            $cleanValues[] = $value;
        } else {
            $cleanValues[] = $value;
        }
    }
    
    // Buat array asosiatif
    $record = [];
    for ($i = 0; $i < count($columns) && $i < count($cleanValues); $i++) {
        $record[$columns[$i]] = $cleanValues[$i];
    }
    
    // Tambahkan struktur nested untuk dokumen jika diperlukan
    if (isset($record['LaporanKeuangan2021'])) {
        $record['laporan_keuangan'] = [
            'LaporanKeuangan2021' => $record['LaporanKeuangan2021']
        ];
        unset($record['LaporanKeuangan2021']);
    }
    
    // Dokumen badan hukum
    $dokumenFields = ['Perdes', 'ProfilBUMDesa', 'BeritaAcara', 'AnggaranDasar', 
                     'AnggaranRumahTangga', 'ProgramKerja', 'SK_BUM_Desa'];
    $dokumenBadanHukum = [];
    
    foreach ($dokumenFields as $field) {
        if (isset($record[$field])) {
            $dokumenBadanHukum[$field] = $record[$field];
            unset($record[$field]);
        }
    }
    
    if (!empty($dokumenBadanHukum)) {
        $record['dokumen_badanhukum'] = $dokumenBadanHukum;
    }
    
    $phpArray[] = $record;
}

// Generate PHP array string
echo "Generating PHP array...\n";

$output = "        // Data BUMDes - Dikonversi dari SQL INSERT statements\n";
$output .= "        \$bumdesArray = [\n";

foreach ($phpArray as $index => $record) {
    $output .= "            [\n";
    
    foreach ($record as $key => $value) {
        if (is_array($value)) {
            $output .= "                '{$key}' => [\n";
            foreach ($value as $subKey => $subValue) {
                if ($subValue === null) {
                    $output .= "                    '{$subKey}' => null,\n";
                } else {
                    $escapedValue = addslashes($subValue);
                    $output .= "                    '{$subKey}' => '{$escapedValue}',\n";
                }
            }
            $output .= "                ],\n";
        } elseif ($value === null) {
            $output .= "                '{$key}' => null,\n";
        } else {
            $escapedValue = addslashes($value);
            $output .= "                '{$key}' => '{$escapedValue}',\n";
        }
    }
    
    $output .= "            ],\n";
}

$output .= "        ];\n";

// Simpan ke file
$outputFile = 'bumdes_array_output.txt';
file_put_contents($outputFile, $output);

echo "Konversi selesai!\n";
echo "Total record: " . count($phpArray) . "\n";
echo "Output disimpan ke: {$outputFile}\n";
echo "\nCopy isi file {$outputFile} dan ganti bagian array kosong di BumdesTableSeeder.php\n";

// Tampilkan preview 2 record pertama
echo "\n--- PREVIEW (2 record pertama) ---\n";
$previewArray = array_slice($phpArray, 0, 2);
foreach ($previewArray as $index => $record) {
    echo "Record " . ($index + 1) . ":\n";
    foreach ($record as $key => $value) {
        if (is_array($value)) {
            echo "  {$key} => Array(" . count($value) . " items)\n";
        } else {
            $displayValue = $value === null ? 'NULL' : substr($value, 0, 50);
            echo "  {$key} => {$displayValue}\n";
        }
    }
    echo "\n";
}
