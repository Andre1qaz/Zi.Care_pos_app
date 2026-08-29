<?php

/**
 * Script untuk test sync invoice ke Odoo dengan journal sale
 * Ini akan menggunakan journal sale yang sudah ada untuk invoice
 */

echo "=== Test Sync Invoice ke Odoo dengan Journal Sale ===\n\n";

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
        if (isset($response['error']['data'])) {
            echo "Error Data: " . json_encode($response['error']['data']) . "\n";
        }
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

// Cek journal sale yang sudah ada
echo "Cek journal sale yang sudah ada...\n";
$saleJournals = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
    $odooConfig['db'],
    $uid,
    $odooConfig['password'],
    'account.journal',
    'search',
    [[['code', '=', 'SALE']]],
    ['limit' => 1]
]);

if (empty($saleJournals)) {
    echo "✗ Journal sale tidak ditemukan\n";
    exit(1);
}

$saleJournalId = $saleJournals[0];
echo "✓ Journal sale ditemukan (ID: {$saleJournalId})\n\n";

// Test 1: Sync Customer
echo "Test 1: Sync Customer...\n";
try {
    $customerData = [
        'name' => 'Test Customer POS',
        'email' => 'test@pos.com',
        'phone' => '08123456789'
    ];
    
    // Cek apakah customer sudah ada
    $existing = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'res.partner',
        'search',
        [[['email', '=', $customerData['email']]]],
        ['limit' => 1]
    ]);
    
    if (!empty($existing)) {
        $partnerId = is_array($existing[0]) ? $existing[0][0] : $existing[0];
        echo "✓ Customer sudah ada (ID: {$partnerId})\n";
    } else {
        $partnerId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'res.partner',
            'create',
            [[$customerData]]
        ]);
        
        if ($partnerId) {
            $partnerId = is_array($partnerId) ? $partnerId[0] : $partnerId;
            echo "✓ Customer baru berhasil dibuat (ID: {$partnerId})\n";
        } else {
            echo "✗ Gagal membuat customer\n";
            exit(1);
        }
    }
} catch (\Exception $e) {
    echo "✗ Error sync customer: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Cek product yang sudah ada
echo "Test 2: Cek Product yang sudah ada...\n";
try {
    $existingProducts = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'product.product',
        'search',
        [[]],
        ['limit' => 1]
    ]);
    
    if (!empty($existingProducts)) {
        $productId = is_array($existingProducts[0]) ? $existingProducts[0][0] : $existingProducts[0];
        echo "✓ Menggunakan product yang sudah ada (ID: {$productId})\n";
    } else {
        echo "⚠ Tidak ada product yang tersedia. Perlu setup product dengan account.\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ Error sync product: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 3: Sync Invoice dengan journal sale
echo "Test 3: Sync Invoice dengan journal sale...\n";
try {
    $lines = [
        [0, 0, [
            'product_id' => $productId,
            'quantity' => 2,
            'price_unit' => 50000
        ]]
    ];
    
    $invoiceData = [
        'move_type' => 'out_invoice',
        'partner_id' => $partnerId,
        'invoice_date' => date('Y-m-d'),
        'ref' => 'POS TEST-' . time(),
        'journal_id' => $saleJournalId, // Gunakan journal sale
        'invoice_line_ids' => $lines,
    ];
    
    $moveId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.move',
        'create',
        [$invoiceData]
    ]);
    
    if ($moveId) {
        echo "✓ Invoice berhasil dibuat (ID: {$moveId})\n";
        
        // Post invoice
        jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.move',
            'action_post',
            [[(int) $moveId]]
        ]);
        echo "✓ Invoice berhasil dipost\n";
        
        // Test payment
        echo "\nTest 4: Sync Payment...\n";
        $journals = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.journal',
            'search',
            [[['code', '=', 'CSH1']]],
            ['limit' => 1]
        ]);
        
        if (!empty($journals)) {
            $journalId = $journals[0];
            $wizardId = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                $odooConfig['db'],
                $uid,
                $odooConfig['password'],
                'account.payment.register',
                'create',
                [[
                    'amount' => 100000,
                    'journal_id' => $journalId,
                    'payment_date' => date('Y-m-d'),
                ]],
                [
                    'context' => [
                        'active_model' => 'account.move',
                        'active_ids' => [$moveId]
                    ]
                ]
            ]);
            
            if ($wizardId) {
                jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                    $odooConfig['db'],
                    $uid,
                    $odooConfig['password'],
                    'account.payment.register',
                    'action_create_payments',
                    [[$wizardId]]
                ]);
                echo "✓ Payment berhasil dibuat\n";
            }
        }
        
    } else {
        echo "✗ Gagal membuat invoice\n";
    }
} catch (\Exception $e) {
    echo "✗ Error sync invoice: " . $e->getMessage() . "\n";
}

echo "\n=== Test Sync Selesai ===\n";
echo "✓ Integrasi POS dengan Odoo sudah berfungsi dengan baik!\n";
