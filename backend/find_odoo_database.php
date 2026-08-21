<?php

/**
 * Script untuk membantu menemukan nama database Odoo yang benar
 * Jalankan dengan: php find_odoo_database.php
 */

echo "=== Mencari Database Odoo ===\n\n";

// Coba beberapa nama database yang umum
$possibleDatabases = [
    'postgres',
    'app_db',
    'pos_db',
    'odoo',
    'odoo_dev', 
    'odoo17',
    'odoo_17',
    'pos',
    'test',
    'demo',
    'production'
];

$odooConfig = [
    'url' => 'http://localhost:8069',
    'username' => 'admin',
    'password' => 'admin',
];

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
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);

    $rawResponse = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlErrno !== 0) {
        return false;
    }

    $response = json_decode($rawResponse, true);
    if (isset($response['error'])) {
        return false;
    }

    return $response['result'] ?? null;
}

echo "Mencoba authenticate dengan beberapa nama database yang umum...\n\n";

$foundDatabase = null;

foreach ($possibleDatabases as $db) {
    echo "Mencoba database: '{$db}'... ";
    
    $uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
        $db,
        $odooConfig['username'],
        $odooConfig['password'],
        []
    ]);

    if ($uid) {
        echo "✓ BERHASIL! (User ID: {$uid})\n";
        $foundDatabase = $db;
        break;
    } else {
        echo "✗ Gagal\n";
    }
}

if ($foundDatabase) {
    echo "\n=== Database yang ditemukan: {$foundDatabase} ===\n";
    echo "\nSilakan update konfigurasi .env Anda dengan:\n";
    echo "ODOO_DB={$foundDatabase}\n";
} else {
    echo "\n=== Tidak ada database yang cocok ===\n";
    echo "\nSilakan cek secara manual:\n";
    echo "1. Buka browser dan akses http://localhost:8069\n";
    echo "2. Perhatikan nama database yang muncul di halaman login\n";
    echo "3. Atau cek file konfigurasi Odoo (biasanya di C:\\Users\\ACER\\odoo\\odoo.conf)\n";
    echo "4. Atau jalankan perintah PostgreSQL untuk melihat database:\n";
    echo "   psql -U postgres -l\n";
}

echo "\n";
