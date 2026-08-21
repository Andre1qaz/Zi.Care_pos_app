<?php

/**
 * Script helper untuk update konfigurasi Odoo di file .env
 * Jalankan dengan: php update_odoo_config.php
 */

echo "=== Update Konfigurasi Odoo di .env ===\n\n";

$envFile = __DIR__ . '/.env';
$envExample = __DIR__ . '/.env.example';

// Konfigurasi Odoo yang benar
$correctConfig = [
    'ODOO_URL' => 'http://localhost:8069',
    'ODOO_DB' => 'pos_db',
    'ODOO_USERNAME' => 'andre',
    'ODOO_PASSWORD' => '314b26ce38b12a37b456478179c2666c1ef72dfd',
    'ODOO_SYNC_ENABLED' => 'true',
    'ODOO_SYNC_MAX_RETRY' => '3',
    'ODOO_JOURNAL_CASH_CODE' => 'CSH1',
    'ODOO_JOURNAL_BANK_CODE' => 'BNK1',
];

if (!file_exists($envFile)) {
    echo "File .env tidak ditemukan. Membuat dari .env.example...\n";
    
    if (!file_exists($envExample)) {
        echo "✗ File .env.example juga tidak ditemukan!\n";
        exit(1);
    }
    
    copy($envExample, $envFile);
    echo "✓ File .env dibuat dari .env.example\n\n";
}

// Baca file .env saat ini
$currentContent = file_get_contents($envFile);
$lines = explode("\n", $currentContent);
$updatedLines = [];
$configUpdated = false;

foreach ($lines as $line) {
    $trimmedLine = trim($line);
    
    // Skip komentar dan baris kosong
    if (empty($trimmedLine) || strpos($trimmedLine, '#') === 0) {
        $updatedLines[] = $line;
        continue;
    }
    
    // Parse baris konfigurasi
    if (strpos($trimmedLine, '=') !== false) {
        list($key, $value) = explode('=', $trimmedLine, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Cek apakah ini adalah konfigurasi Odoo yang perlu diupdate
        if (array_key_exists($key, $correctConfig)) {
            $newValue = $correctConfig[$key];
            if ($value !== $newValue) {
                echo "Update: {$key} = '{$value}' -> '{$newValue}'\n";
                $updatedLines[] = "{$key}={$newValue}";
                $configUpdated = true;
            } else {
                $updatedLines[] = $line; // Sudah benar, keep as is
            }
        } else {
            $updatedLines[] = $line; // Bukan konfigurasi Odoo, keep as is
        }
    } else {
        $updatedLines[] = $line; // Baris lain, keep as is
    }
}

// Jika ada konfigurasi yang diupdate, tulis kembali file
if ($configUpdated) {
    file_put_contents($envFile, implode("\n", $updatedLines));
    echo "\n✓ File .env berhasil diupdate!\n";
} else {
    echo "\n✓ Konfigurasi Odoo sudah benar. Tidak ada perubahan.\n";
}

// Verifikasi konfigurasi setelah update
echo "\n=== Verifikasi Konfigurasi ===\n";
$config = require __DIR__ . '/app/config/config.php';
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
