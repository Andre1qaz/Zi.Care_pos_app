<?php

/**
 * Script untuk membuat user admin baru di Odoo via API
 * Ini akan mencoba membuat user dengan password sederhana
 */

echo "=== Mencoba membuat user admin di Odoo ===\n\n";

$odooConfig = [
    'url' => 'http://localhost:8069',
    'db' => 'pos_db',
    'username' => 'andre',
    'password' => 'admin', // Kita tidak tahu password andre
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
    curl_close($ch);

    if ($rawResponse === false) return false;

    $response = json_decode($rawResponse, true);
    if (isset($response['error'])) return false;

    return $response['result'] ?? null;
}

echo "Karena kita tidak bisa authenticate dengan password, kita perlu:\n\n";
echo "1. Login ke Odoo via browser: http://localhost:8069\n";
echo "2. Login dengan user andre (password yang Anda ketahui)\n";
echo "3. Setelah login, buka Settings > Users & Companies > Users\n";
echo "4. Pilih user andre > Action > API Keys\n";
echo "5. Buat API Key baru\n";
echo "6. Copy API Key tersebut\n\n";

echo "Atau:\n\n";
echo "1. Login ke Odoo via browser\n";
echo "2. Buat user baru dengan password yang Anda inginkan\n";
echo "3. Berikan akses admin ke user tersebut\n";
echo "4. Gunakan user baru untuk integrasi\n\n";

echo "Setelah mendapatkan API Key atau password baru, beritahu saya dan saya akan update konfigurasi.\n";
