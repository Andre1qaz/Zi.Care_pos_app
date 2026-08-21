<?php

/**
 * Script khusus untuk membuat journal sale
 */

echo "=== Setup Sale Journal di Odoo ===\n\n";

$odooConfig = [
    'url' => 'http://localhost:8069',
    'db' => 'pos_db',
    'username' => 'andre',
    'password' => '314b26ce38b12a37b456478179c2666c1ef72dfd',
    'use_api_key' => true,
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

echo "Mencoba authenticate...\n";
$uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
    $odooConfig['db'],
    $odooConfig['username'],
    $odooConfig['password'],
    []
]);

if (!$uid) {
    echo "✗ Authentication gagal\n";
    exit(1);
}

echo "✓ Authentication berhasil! User ID: {$uid}\n\n";

// Cek company
$companies = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'res.company',
    'search',
    [[]],
    ['limit' => 1]
]);

if (empty($companies)) {
    echo "✗ Tidak ada company ditemukan\n";
    exit(1);
}

$companyId = $companies[0];
echo "Company ID: {$companyId}\n\n";

// Cek apakah journal sale sudah ada
echo "Cek journal sale yang sudah ada...\n";
$existingSaleJournals = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.journal',
    'search',
    [[['type', '=', 'sale']]]
]);

if (!empty($existingSaleJournals)) {
    echo "✓ Journal sale sudah ada (ID: " . $existingSaleJournals[0] . ")\n";
    exit(0);
}

echo "Membuat journal sale...\n";
$saleJournalId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.journal',
    'create',
    [[
        'name' => 'Sales',
        'code' => 'SALE',
        'type' => 'sale',
        'company_id' => $companyId,
    ]]
]);

if ($saleJournalId) {
    echo "✓ Journal Sale berhasil dibuat (ID: {$saleJournalId})\n";
} else {
    echo "✗ Gagal membuat journal Sale\n";
}

echo "\n=== Setup Selesai ===\n";
