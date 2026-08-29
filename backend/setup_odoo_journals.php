<?php

/**
 * Script untuk setup journal di Odoo
 * Ini akan membuat journal cash dan bank yang diperlukan untuk sync invoice
 */

echo "=== Setup Journal di Odoo ===\n\n";

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

// Cek apakah sudah ada journal
echo "Cek journal yang sudah ada...\n";
$existingJournals = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.journal',
    'search',
    [[]]
]);

echo "Ditemukan " . count($existingJournals) . " journal yang sudah ada\n\n";

if (empty($existingJournals)) {
    echo "Membuat journal baru...\n\n";
    
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
    echo "Company ID: {$companyId}\n";
    
    // Cek atau buat account untuk journal
    try {
        // Membuat journal Cash
        echo "Membuat journal Cash...\n";
        $cashJournalId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.journal',
            'create',
            [[
                'name' => 'Cash',
                'code' => 'CSH1',
                'type' => 'cash',
                'company_id' => $companyId,
            ]]
        ]);
        
        if ($cashJournalId) {
            echo "✓ Journal Cash berhasil dibuat (ID: {$cashJournalId})\n";
        } else {
            echo "✗ Gagal membuat journal Cash\n";
        }
        
        // Membuat journal Bank
        echo "Membuat journal Bank...\n";
        $bankJournalId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.journal',
            'create',
            [[
                'name' => 'Bank',
                'code' => 'BNK1',
                'type' => 'bank',
                'company_id' => $companyId,
            ]]
        ]);
        
        if ($bankJournalId) {
            echo "✓ Journal Bank berhasil dibuat (ID: {$bankJournalId})\n";
        } else {
            echo "✗ Gagal membuat journal Bank\n";
        }
        
        // Membuat journal Sale (diperlukan untuk invoice)
        echo "Membuat journal Sale...\n";
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
        
    } catch (\Exception $e) {
        echo "✗ Error membuat journal: " . $e->getMessage() . "\n";
        echo "\nSilakan setup journal secara manual di Odoo:\n";
        echo "1. Buka Odoo > Accounting > Configuration > Journals\n";
        echo "2. Create journal untuk Cash dan Bank\n";
        echo "3. Pastikan code journal sesuai dengan konfigurasi\n";
    }
} else {
    echo "Journal sudah ada. Detail:\n";
    $journalDetails = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.journal',
        'read',
        [$existingJournals],
        ['fields' => ['name', 'code', 'type']]
    ]);
    
    foreach ($journalDetails as $journal) {
        echo "  - {$journal['name']} (Code: {$journal['code']}, Type: {$journal['type']})\n";
    }
}

echo "\n=== Setup Selesai ===\n";
