<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Perjadin\Kegiatan;

$kegiatan = Kegiatan::with(['details.bidang'])->first();

if ($kegiatan) {
    echo "Kegiatan ID: " . $kegiatan->id_kegiatan . PHP_EOL;
    echo "Kegiatan Name: " . $kegiatan->nama_kegiatan . PHP_EOL;
    echo "Details count: " . $kegiatan->details->count() . PHP_EOL;
    echo "---" . PHP_EOL;
    
    foreach ($kegiatan->details as $detail) {
        echo "Detail ID: " . $detail->id_kegiatan_bidang . PHP_EOL;
        echo "Bidang ID: " . $detail->id_bidang . PHP_EOL;
        echo "Bidang: " . ($detail->bidang ? $detail->bidang->nama_bidang : 'None') . PHP_EOL;
        echo "Personil: " . $detail->personil . PHP_EOL;
        echo "Personil Type: " . gettype($detail->personil) . PHP_EOL;
        echo "---" . PHP_EOL;
    }
} else {
    echo "No kegiatan found" . PHP_EOL;
}