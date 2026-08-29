<?php

/**
 * Script untuk install chart of accounts localization
 * Ini akan mencoba menginstall chart of accounts untuk Indonesia
 */

echo "=== Install Chart of Accounts Localization ===\n\n";

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

// Cek apakah ada chart of template yang tersedia
echo "Mencari chart of accounts template...\n";
$templates = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.chart.template',
    'search',
    [[]],
    ['limit' => 10]
]);

if (!empty($templates)) {
    $templateDetails = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.chart.template',
        'read',
        [$templates],
        ['fields' => ['name', 'code']]
    ]);
    
    echo "Chart of accounts template yang tersedia:\n";
    foreach ($templateDetails as $template) {
        echo "  - {$template['name']} (Code: {$template['code']})\n";
    }
    
    // Gunakan template pertama
    $templateId = $templates[0];
    echo "\nMenggunakan template ID {$templateId}\n";
    
    // Load chart of template
    echo "Mencoba load chart of accounts template...\n";
    $result = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.chart.template',
        'try_loading',
        [[$templateId], $companyId]
    ]);
    
    if ($result) {
        echo "✓ Chart of accounts berhasil diinstall\n";
    } else {
        echo "⚠ Gagal menginstall chart of accounts secara otomatis\n";
    }
} else {
    echo "⚠ Tidak ada chart of accounts template yang tersedia\n";
    echo "Silakan install chart of accounts secara manual di Odoo:\n";
    echo "1. Buka Odoo > Accounting > Configuration > Settings\n";
    echo "2. Pilih localization yang sesuai\n";
    echo "3. Install chart of accounts\n";
}

echo "\n=== Setup Selesai ===\n";
