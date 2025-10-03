<?php
// Test script untuk API Perjadin
echo "=== TEST API PERJADIN ===\n\n";

// Test Dashboard
echo "1. Testing Dashboard API...\n";
$dashboard_url = "http://localhost/dpmd/dpmd-backend/public/api/perjadin/dashboard";
$dashboard_response = file_get_contents($dashboard_url);
echo "Dashboard Response: " . $dashboard_response . "\n\n";

// Test Kegiatan List
echo "2. Testing Kegiatan List API...\n";
$kegiatan_url = "http://localhost/dpmd/dpmd-backend/public/api/perjadin/kegiatan";
$kegiatan_response = file_get_contents($kegiatan_url);
echo "Kegiatan Response: " . $kegiatan_response . "\n\n";

// Test Statistik
echo "3. Testing Statistik API...\n";
$statistik_url = "http://localhost/dpmd/dpmd-backend/public/api/perjadin/statistik";
$statistik_response = file_get_contents($statistik_url);
echo "Statistik Response: " . $statistik_response . "\n\n";

echo "=== TEST SELESAI ===\n";
?>