<?php

/**
 * Script untuk setup chart of accounts yang lengkap di Odoo
 * Ini akan membuat income account yang diperlukan untuk invoice
 */

echo "=== Setup Chart of Accounts di Odoo ===\n\n";

$odooConfig = [
    'url' => 'http://localhost:8069',
    'db' => 'pos_db',
    'username' => 'andre',
    'password' => '1fad75c3f8f458422e6f68573a03661991c05101',
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

// Cek parent account untuk income
echo "Mencari atau membuat parent account untuk income...\n";
$parentAccounts = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.account',
    'search',
    [[['account_type', '=', 'income'], ['company_id', '=', $companyId]]],
    ['limit' => 1]
]);

if (empty($parentAccounts)) {
    echo "Membuat parent income account...\n";
    $parentAccountId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.account',
        'create',
        [[
            'name' => 'Income',
            'code' => '4000',
            'account_type' => 'income',
            'company_id' => $companyId,
            'reconcile' => false,
        ]]
    ]);
    
    if ($parentAccountId) {
        echo "✓ Parent income account berhasil dibuat (ID: {$parentAccountId})\n";
    } else {
        echo "✗ Gagal membuat parent income account\n";
        exit(1);
    }
} else {
    $parentAccountId = $parentAccounts[0];
    echo "✓ Parent income account sudah ada (ID: {$parentAccountId})\n";
}

// Buat income account khusus untuk sales
echo "\nMembuat Sales Revenue account...\n";
$salesRevenueAccountId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.account',
    'create',
    [[
        'name' => 'Sales Revenue',
        'code' => '4100',
        'account_type' => 'income',
        'company_id' => $companyId,
        'reconcile' => false,
    ]]
]);

if ($salesRevenueAccountId) {
    echo "✓ Sales Revenue account berhasil dibuat (ID: {$salesRevenueAccountId})\n";
} else {
    echo "✗ Gagal membuat Sales Revenue account\n";
    exit(1);
}

// Update semua product untuk menggunakan income account ini
echo "\nUpdate product untuk menggunakan Sales Revenue account...\n";
$products = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'product.product',
    'search',
    [[]]
]);

echo "Ditemukan " . count($products) . " product\n";

if (!empty($products)) {
    $updated = 0;
    foreach ($products as $productId) {
        $result = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'product.product',
            'write',
            [[(int)$productId], [
                'property_account_income_id' => (int)$salesRevenueAccountId
            ]]
        ]);
        
        if ($result) {
            $updated++;
        }
    }
    
    echo "✓ Berhasil update {$updated} product dengan Sales Revenue account\n";
} else {
    echo "⚠ Tidak ada product yang ditemukan\n";
}

echo "\n=== Setup Selesai ===\n";
echo "✓ Chart of accounts sudah disetup dengan income account yang proper\n";
