<?php

/**
 * Script untuk update konfigurasi Odoo langsung di config.php
 * Jalankan dengan: php update_config_direct.php
 */

echo "=== Update Konfigurasi Odoo di config.php ===\n\n";

$configFile = __DIR__ . '/app/config/config.php';

if (!file_exists($configFile)) {
    echo "✗ File config.php tidak ditemukan!\n";
    exit(1);
}

// Konfigurasi Odoo yang benar
$correctConfig = [
    'url' => 'http://localhost:8069',
    'db' => 'pos_db',
    'username' => 'andre',
    'password' => '314b26ce38b12a37b456478179c2666c1ef72dfd',
    'journal_cash_code' => 'CSH1',
    'journal_bank_code' => 'BNK1',
];

// Baca file config.php saat ini
$currentContent = file_get_contents($configFile);

// Update konfigurasi Odoo
foreach ($correctConfig as $key => $value) {
    // Pattern untuk mencari dan mengganti konfigurasi
    $pattern = "/'{$key}'\s*=>\s*\$_ENV\['ODOO_" . strtoupper($key) . "'\]\s*\?\?\s*'[^']*'/";
    $replacement = "'{$key}' => \$_ENV['ODOO_" . strtoupper($key) . "'] ?? '{$value}'";
    
    $currentContent = preg_replace($pattern, $replacement, $currentContent);
}

// Tulis kembali file config.php
file_put_contents($configFile, $currentContent);
echo "✓ File config.php berhasil diupdate!\n\n";

// Update juga file .env
$envFile = __DIR__ . '/.env';
$envCorrectConfig = [
    'ODOO_URL' => 'http://localhost:8069',
    'ODOO_DB' => 'pos_db',
    'ODOO_USERNAME' => 'andre',
    'ODOO_PASSWORD' => '314b26ce38b12a37b456478179c2666c1ef72dfd',
    'ODOO_SYNC_ENABLED' => 'true',
    'ODOO_SYNC_MAX_RETRY' => '3',
    'ODOO_JOURNAL_CASH_CODE' => 'CSH1',
    'ODOO_JOURNAL_BANK_CODE' => 'BNK1',
];

if (file_exists($envFile)) {
    $currentEnvContent = file_get_contents($envFile);
    $lines = explode("\n", $currentEnvContent);
    $updatedLines = [];
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
            $updatedLines[] = $line;
            continue;
        }
        
        if (strpos($trimmedLine, '=') !== false) {
            list($key, $value) = explode('=', $trimmedLine, 2);
            $key = trim($key);
            
            if (array_key_exists($key, $envCorrectConfig)) {
                $updatedLines[] = "{$key}={$envCorrectConfig[$key]}";
            } else {
                $updatedLines[] = $line;
            }
        } else {
            $updatedLines[] = $line;
        }
    }
    
    file_put_contents($envFile, implode("\n", $updatedLines));
    echo "✓ File .env berhasil diupdate!\n\n";
} else {
    echo "⚠ File .env tidak ditemukan, membuat baru...\n";
    $envContent = "";
    foreach ($envCorrectConfig as $key => $value) {
        $envContent .= "{$key}={$value}\n";
    }
    file_put_contents($envFile, $envContent);
    echo "✓ File .env baru berhasil dibuat!\n\n";
}

// Verifikasi konfigurasi
echo "=== Verifikasi Konfigurasi ===\n";
$config = require $configFile;
$odooConfig = $config['odoo'];

echo "URL: {$odooConfig['url']}\n";
echo "Database: {$odooConfig['db']}\n";
echo "Username: {$odooConfig['username']}\n";
echo "Password: " . substr($odooConfig['password'], 0, 8) . "...\n";
echo "Enabled: " . ($odooConfig['enabled'] ? 'true' : 'false') . "\n\n";

// Test koneksi
echo "=== Test Koneksi ke Odoo ===\n";
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
    echo "✓ Authentication berhasil! User ID: {$uid}\n";
    echo "✓ Konfigurasi Odoo sudah benar dan siap digunakan!\n\n";
    echo "=== Langkah Selanjutnya ===\n";
    echo "1. Restart server backend (tekan Ctrl+C, lalu jalankan lagi)\n";
    echo "2. Buat transaksi baru di aplikasi POS\n";
    echo "3. Cek apakah sync berhasil di Odoo\n";
} else {
    echo "✗ Authentication gagal. Perlu cek konfigurasi lagi.\n";
}

echo "\n=== Selesai ===\n";
