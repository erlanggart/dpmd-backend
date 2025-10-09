<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\KelembagaanController;
use Illuminate\Http\Request;

echo "Testing KelembagaanController Summary Method...\n";
echo "===============================================\n\n";

try {
    $controller = new KelembagaanController();
    $request = new Request();

    echo "Calling controller summary method...\n";
    $response = $controller->summary($request);

    echo "Response status: " . $response->getStatusCode() . "\n";

    $content = $response->getContent();
    $data = json_decode($content, true);

    if ($data && isset($data['success']) && $data['success']) {
        echo "SUCCESS! Summary API working correctly.\n\n";

        echo "=== OVERVIEW ===\n";
        echo "Kecamatan: " . $data['data']['overview']['kecamatan'] . "\n";
        echo "Desa: " . $data['data']['overview']['desa'] . "\n";
        echo "Kelurahan: " . $data['data']['overview']['kelurahan'] . "\n";
        echo "Total Desa + Kelurahan: " . $data['data']['overview']['desa_kelurahan_total'] . "\n\n";

        echo "=== TOTAL KELEMBAGAAN ===\n";
        foreach ($data['data']['total_kelembagaan'] as $key => $value) {
            echo ucfirst($key) . ": " . $value . "\n";
        }
        echo "\n";

        echo "=== TOTAL PENGURUS ===\n";
        foreach ($data['data']['total_pengurus'] as $key => $value) {
            echo ucfirst($key) . ": " . $value . "\n";
        }
        echo "\n";

        echo "=== BREAKDOWN BY STATUS ===\n";
        echo "DESA (" . $data['data']['by_status']['desa']['count'] . "):\n";
        foreach ($data['data']['by_status']['desa'] as $key => $value) {
            if ($key !== 'count' && $key !== 'pengurus') {
                echo "  - " . ucfirst($key) . ": " . $value . "\n";
            }
        }

        echo "  Pengurus Desa:\n";
        foreach ($data['data']['by_status']['desa']['pengurus'] as $key => $value) {
            echo "    - " . ucfirst($key) . ": " . $value . "\n";
        }

        echo "\nKELURAHAN (" . $data['data']['by_status']['kelurahan']['count'] . "):\n";
        foreach ($data['data']['by_status']['kelurahan'] as $key => $value) {
            if ($key !== 'count' && $key !== 'pengurus') {
                echo "  - " . ucfirst($key) . ": " . $value . "\n";
            }
        }

        echo "  Pengurus Kelurahan:\n";
        foreach ($data['data']['by_status']['kelurahan']['pengurus'] as $key => $value) {
            echo "    - " . ucfirst($key) . ": " . $value . "\n";
        }

        echo "\n=== FORMATION STATISTICS ===\n";
        foreach ($data['data']['formation_stats'] as $key => $stats) {
            echo ucfirst($key) . ":\n";
            echo "  - Total: " . $stats['total'] . "\n";
            echo "  - Desa Terbentuk: " . $stats['desa_terbentuk'] . "\n";
            echo "  - Persentase: " . $stats['persentase'] . "%\n\n";
        }

        echo "Data structure is ready for frontend integration! ✅\n";
    } else {
        echo "ERROR: " . ($data['message'] ?? 'Unknown error') . "\n";
        echo "Full response: " . substr($content, 0, 500) . "...\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
