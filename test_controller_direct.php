<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\KelembagaanController;
use App\Models\User;
use Illuminate\Http\Request;

echo "Testing KelembagaanController directly...\n";
echo "=======================================\n\n";

try {
    // Create controller instance
    $controller = new KelembagaanController();

    // Create mock request
    $request = new Request();

    echo "Calling controller index method...\n";
    $response = $controller->index($request);

    echo "Response status: " . $response->getStatusCode() . "\n";

    $content = $response->getContent();
    $data = json_decode($content, true);

    echo "Response structure:\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";

    if (isset($data['data'])) {
        echo "Data type: " . gettype($data['data']) . "\n";
        echo "Data count: " . (is_array($data['data']) ? count($data['data']) : 'N/A') . "\n";

        if (is_array($data['data']) && count($data['data']) > 0) {
            echo "\nFirst kecamatan keys: " . implode(', ', array_keys($data['data'][0])) . "\n";

            $firstKec = $data['data'][0];
            echo "First kecamatan nama: " . ($firstKec['nama'] ?? 'N/A') . "\n";

            if (isset($firstKec['desas']) && is_array($firstKec['desas'])) {
                echo "Desas count: " . count($firstKec['desas']) . "\n";

                if (count($firstKec['desas']) > 0) {
                    echo "First desa keys: " . implode(', ', array_keys($firstKec['desas'][0])) . "\n";
                }
            }
        }
    }

    echo "\nTesting summary method...\n";
    $summaryResponse = $controller->summary($request);
    echo "Summary response status: " . $summaryResponse->getStatusCode() . "\n";

    $summaryContent = $summaryResponse->getContent();
    $summaryData = json_decode($summaryContent, true);

    if (isset($summaryData['data'])) {
        echo "Summary data keys: " . implode(', ', array_keys($summaryData['data'])) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
