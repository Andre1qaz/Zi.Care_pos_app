<?php

/**
 * Script untuk cek konfigurasi dan test sync invoice
 */

echo "=== Cek Konfigurasi dan Test Sync Invoice ===\n\n";

// Load konfigurasi dari config.php
$configFile = __DIR__ . '/app/config/config.php';
if (!file_exists($configFile)) {
    echo "✗ File config.php tidak ditemukan\n";
    exit(1);
}

$config = require $configFile;
$odooConfig = $config['odoo'];

echo "Konfigurasi Odoo saat ini:\n";
echo "URL: {$odooConfig['url']}\n";
echo "Database: {$odooConfig['db']}\n";
echo "Username: {$odooConfig['username']}\n";
echo "Password: " . substr($odooConfig['password'], 0, 8) . "...\n";
echo "Enabled: " . ($odooConfig['enabled'] ? 'true' : 'false') . "\n";
echo "Journal Cash Code: {$odooConfig['journal_cash_code']}\n";
echo "Journal Bank Code: {$odooConfig['journal_bank_code']}\n\n";

// Cek apakah konfigurasi sudah benar
$expectedConfig = [
    'url' => 'http://localhost:8069',
    'db' => 'pos_db',
    'username' => 'andre',
    'password' => '314b26ce38b12a37b456478179c2666c1ef72dfd',
];

$configCorrect = true;
if ($odooConfig['url'] !== $expectedConfig['url']) {
    echo "⚠ URL tidak sesuai. Current: {$odooConfig['url']}, Expected: {$expectedConfig['url']}\n";
    $configCorrect = false;
}
if ($odooConfig['db'] !== $expectedConfig['db']) {
    echo "⚠ Database tidak sesuai. Current: {$odooConfig['db']}, Expected: {$expectedConfig['db']}\n";
    $configCorrect = false;
}
if ($odooConfig['username'] !== $expectedConfig['username']) {
    echo "⚠ Username tidak sesuai. Current: {$odooConfig['username']}, Expected: {$expectedConfig['username']}\n";
    $configCorrect = false;
}
if ($odooConfig['password'] !== $expectedConfig['password']) {
    echo "⚠ Password tidak sesuai. Current: " . substr($odooConfig['password'], 0, 8) . "..., Expected: " . substr($expectedConfig['password'], 0, 8) . "...\n";
    $configCorrect = false;
}

if ($configCorrect) {
    echo "✓ Konfigurasi sudah benar!\n\n";
} else {
    echo "✗ Konfigurasi belum benar. Perlu update file .env\n\n";
    echo "Update file backend/.env dengan:\n";
    echo "ODOO_URL=http://localhost:8069\n";
    echo "ODOO_DB=pos_db\n";
    echo "ODOO_USERNAME=andre\n";
    echo "ODOO_PASSWORD=314b26ce38b12a37b456478179c2666c1ef72dfd\n";
    echo "ODOO_SYNC_ENABLED=true\n\n";
    exit(1);
}

// Test koneksi
echo "Test koneksi ke Odoo...\n";
function jsonRpc($url, $service, $method, $params) {
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'call',
        'params' => [
            'service' => $service,
            'method' => $method,
            'args' => $params
        ],
        'id' => rand(1, 999999)
    ]);

    $ch = curl_init(rtrim($url, '/') . '/jsonrpc');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $rawResponse = curl_exec($ch);
    curl_close($ch);

    if ($rawResponse === false) return false;

    $response = json_decode($rawResponse, true);
    if (isset($response['error'])) {
        echo "Error: " . ($response['error']['message'] ?? 'Unknown error') . "\n";
        return false;
    }

    return $response['result'] ?? null;
}

$uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
    $odooConfig['db'],
    $odooConfig['username'],
    $odooConfig['password'],
    []
]);

if ($uid) {
    echo "✓ Authentication berhasil! User ID: {$uid}\n\n";
} else {
    echo "✗ Authentication gagal\n\n";
    exit(1);
}

// Cek log aplikasi
echo "Cek log aplikasi...\n";
$logFile = __DIR__ . '/storage/logs/app.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $recentLogs = substr($logs, -2000); // Ambil 2000 karakter terakhir
    echo "Log terbaru:\n";
    echo $recentLogs . "\n";
} else {
    echo "⚠ File log tidak ditemukan: {$logFile}\n";
}

echo "\n=== Selesai ===\n";
