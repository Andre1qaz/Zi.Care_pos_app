<?php

/**
 * Script untuk cek koneksi database Odoo dan status chart of accounts
 */

echo "=== Cek Koneksi Database Odoo dan Chart of Accounts ===\n\n";

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

echo "Test 1: Authentication...\n";
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

// Test 2: Cek jumlah account yang tersedia
echo "Test 2: Cek jumlah account yang tersedia di Odoo...\n";
$accounts = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.account',
    'search',
    [[]]
]);

echo "Jumlah account yang tersedia: " . count($accounts) . "\n";

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
    
    echo "\nDetail account yang tersedia:\n";
    $incomeAccounts = [];
    foreach ($accountDetails as $account) {
        echo "  - {$account['name']} (Code: {$account['code']}, Type: {$account['account_type']})\n";
        if ($account['account_type'] === 'income') {
            $incomeAccounts[] = $account;
        }
    }
    
    if (empty($incomeAccounts)) {
        echo "\n⚠ TIDAK ADA INCOME ACCOUNT yang tersedia!\n";
        echo "Ini menyebabkan error 'Missing required account on accountable line'\n";
    } else {
        echo "\n✓ Ada " . count($incomeAccounts) . " income account yang tersedia:\n";
        foreach ($incomeAccounts as $acc) {
            echo "  - {$acc['name']} (Code: {$acc['code']})\n";
        }
    }
} else {
    echo "⚠ TIDAK ADA ACCOUNT yang tersedia di Odoo!\n";
    echo "Ini menyebabkan error accounting validation\n";
}

echo "\n";

// Test 3: Cek apakah Odoo Accounting module terinstall
echo "Test 3: Cek module yang terinstall di Odoo...\n";
$modules = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'ir.module.module',
    'search',
    [[['name', '=', 'account']]],
    ['limit' => 1]
]);

if (!empty($modules)) {
    $moduleDetails = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'ir.module.module',
        'read',
        [$modules],
        ['fields' => ['name', 'state', 'latest_version']]
    ]);
    
    echo "Module Accounting:\n";
    foreach ($moduleDetails as $module) {
        echo "  - Name: {$module['name']}\n";
        echo "  - State: {$module['state']}\n";
        echo "  - Version: {$module['latest_version']}\n";
        
        if ($module['state'] === 'installed') {
            echo "  ✓ Accounting module sudah terinstall\n";
        } else {
            echo "  ✗ Accounting module belum terinstall atau tidak aktif\n";
        }
    }
} else {
    echo "✗ Accounting module tidak ditemukan\n";
}

echo "\n";

// Test 4: Cek database configuration Odoo
echo "Test 4: Rekomendasi setup database...\n";
echo "Jika PostgreSQL dijalankan melalui Portainer (Docker):\n";
echo "1. Pastikan Odoo bisa mengakses PostgreSQL container\n";
echo "2. Cek docker network - Odoo dan PostgreSQL harus di network yang sama\n";
echo "3. Cek port mapping - pastikan port PostgreSQL terexpose jika diperlukan\n";
echo "4. Cek Odoo configuration (odoo.conf) untuk koneksi database yang benar\n";
echo "\n";
echo "File odoo.conf biasanya berisi:\n";
echo "  db_host = <postgres_container_name or IP>\n";
echo "  db_port = 5432\n";
echo "  db_user = odoo\n";
echo "  db_password = <password>\n";
echo "  dbfilter = ^pos_db$\n";

echo "\n=== Test Selesai ===\n";
