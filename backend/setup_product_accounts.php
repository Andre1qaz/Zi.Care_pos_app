<?php

/**
 * Script untuk setup account untuk product di Odoo
 * Product harus memiliki income account untuk bisa di-invoicing
 */

echo "=== Setup Account untuk Product di Odoo ===\n\n";

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

// Cek account yang tersedia
echo "Cek account yang tersedia...\n";
$accounts = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.account',
    'search',
    [[['account_type', '=', 'income'], ['company_id', '=', 1]]],
    ['limit' => 5]
]);

if (empty($accounts)) {
    echo "⚠ Tidak ada income account yang tersedia. Mencoba mencari semua account...\n";
    $accounts = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.account',
        'search',
        [[]],
        ['limit' => 10]
    ]);
}

if (!empty($accounts)) {
    $accountDetails = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.account',
        'read',
        [$accounts],
        ['fields' => ['name', 'code', 'account_type']]
    ]);
    
    echo "Account yang tersedia:\n";
    foreach ($accountDetails as $account) {
        echo "  - {$account['name']} (Code: {$account['code']}, Type: {$account['account_type']})\n";
    }
    
    // Gunakan account pertama sebagai default income account
    $defaultIncomeAccountId = $accounts[0];
    echo "\nMenggunakan account ID {$defaultIncomeAccountId} sebagai default income account\n";
} else {
    echo "✗ Tidak ada account yang tersedia. Perlu setup chart of account di Odoo.\n";
    exit(1);
}

// Update semua product untuk memiliki income account
echo "\nUpdate product untuk memiliki income account...\n";
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
                'property_account_income_id' => (int)$defaultIncomeAccountId
            ]]
        ]);
        
        if ($result) {
            $updated++;
        }
    }
    
    echo "✓ Berhasil update {$updated} product dengan income account\n";
} else {
    echo "⚠ Tidak ada product yang ditemukan\n";
}

echo "\n=== Setup Selesai ===\n";
