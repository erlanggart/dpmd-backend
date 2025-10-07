<?php

/**
 * Simple test script to demonstrate the kelembagaan API optimization
 * Run this with: php test_kelembagaan_api.php
 */

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$client = new Client([
    'base_uri' => 'http://dpmd.test/api/desa/',
    'timeout'  => 10.0,
]);

echo "Testing Kelembagaan API Performance Optimization\n";
echo "==============================================\n\n";

try {
    echo "1. Testing NEW lightweight summary endpoint:\n";
    $start = microtime(true);

    $response = $client->request('GET', 'kelembagaan/summary', [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'query' => [
            'desa_id' => '1' // You might need to adjust this
        ]
    ]);

    $end = microtime(true);
    $duration = ($end - $start) * 1000; // Convert to milliseconds

    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Duration: " . round($duration, 2) . " ms\n";
    echo "   Response: " . $response->getBody() . "\n\n";

    echo "2. Testing OLD approach (multiple API calls):\n";
    $oldStart = microtime(true);

    $endpoints = ['rw', 'rt', 'posyandu', 'karang-taruna', 'lpm', 'satlinmas', 'pkk'];
    $totalItems = 0;

    foreach ($endpoints as $endpoint) {
        try {
            $response = $client->request('GET', $endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            $count = is_array($data['data']['data'] ?? []) ? count($data['data']['data']) : 0;
            $totalItems += $count;
            echo "   {$endpoint}: {$count} items\n";
        } catch (RequestException $e) {
            echo "   {$endpoint}: Error - " . $e->getMessage() . "\n";
        }
    }

    $oldEnd = microtime(true);
    $oldDuration = ($oldEnd - $oldStart) * 1000;

    echo "   Total Duration: " . round($oldDuration, 2) . " ms\n";
    echo "   Total Items: {$totalItems}\n\n";

    $improvement = (($oldDuration - $duration) / $oldDuration) * 100;
    echo "Performance Improvement:\n";
    echo "   NEW approach: " . round($duration, 2) . " ms\n";
    echo "   OLD approach: " . round($oldDuration, 2) . " ms\n";
    echo "   Improvement: " . round($improvement, 1) . "% faster\n";
    echo "   Reduced API calls: 7 → 1 (86% reduction)\n";
} catch (RequestException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure:\n";
    echo "1. Laravel server is running (php artisan serve)\n";
    echo "2. Database is properly set up\n";
    echo "3. Routes are registered correctly\n";
}
