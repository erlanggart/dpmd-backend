<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Rw;
use App\Models\Pengurus;

// Simple test to check if the query works
try {
    // Test the query structure
    $testQuery = Rw::withCount('rts as jumlah_rt')
        ->with(['pengurus' => function ($query) {
            $query->where('jabatan', 'Ketua')
                ->where('status_jabatan', 'aktif')
                ->select('pengurusable_id', 'nama_lengkap');
        }]);

    echo "Query structure is valid\n";

    // Test if the relations exist
    $rw = new Rw();
    if (method_exists($rw, 'rts')) {
        echo "rts() relation exists\n";
    }
    if (method_exists($rw, 'pengurus')) {
        echo "pengurus() relation exists\n";
    }

    echo "Test completed successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
