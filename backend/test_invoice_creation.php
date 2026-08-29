<?php

/**
 * Script untuk test pembuatan invoice di Odoo dengan detail error
 */

echo "=== Test Pembuatan Invoice di Odoo dengan Detail Error ===\n\n";

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load config
$config = require __DIR__ . '/app/config/config.php';

$authCredential = $config['odoo']['api_key'] ?? $config['odoo']['password'];

function jsonRpc($url, $service, $method, $params, $authCredential) {
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
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlErrno !== 0) {
        throw new \RuntimeException(
            'Gagal menghubungi server Odoo: ' . ($curlError ?: 'unknown curl error') .
            ' (curl errno: ' . $curlErrno . ')'
        );
    }

    $response = json_decode($rawResponse, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException('Respons Odoo bukan JSON valid: ' . json_last_error_msg());
    }

    if (isset($response['error'])) {
        $errorMsg = $response['error']['message'] ?? 'Odoo returned an unspecified error';
        $errorData = $response['error']['data'] ?? [];
        throw new \RuntimeException($errorMsg . " | Data: " . json_encode($errorData));
    }

    return $response['result'] ?? null;
}

echo "Test 1: Authenticate...\n";
try {
    $uid = jsonRpc($config['odoo']['url'], 'common', 'authenticate', [
        $config['odoo']['db'],
        $config['odoo']['username'],
        $authCredential,
        []
    ], $authCredential);
    echo "✓ Auth berhasil, User ID: {$uid}\n\n";
} catch (Exception $e) {
    echo "✗ Auth gagal: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Test 2: Cek journal yang tersedia...\n";
try {
    $journals = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'account.journal',
        'search',
        [[]],
        ['limit' => 10]
    ], $authCredential);
    
    echo "✓ Ditemukan " . count($journals) . " journal\n";
    
    $journalDetails = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'account.journal',
        'read',
        [$journals],
        ['fields' => ['id', 'name', 'code', 'type', 'company_id']]
    ], $authCredential);
    
    echo "Journal Details:\n";
    foreach ($journalDetails as $journal) {
        echo "  ID: {$journal['id']}, Name: {$journal['name']}, Code: {$journal['code']}, Type: {$journal['type']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error cek journal: " . $e->getMessage() . "\n\n";
}

echo "Test 3: Cek account yang tersedia...\n";
try {
    $accounts = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'account.account',
        'search',
        [[['company_id', '=', 1]]],
        ['limit' => 10]
    ], $authCredential);
    
    echo "✓ Ditemukan " . count($accounts) . " account\n";
    
    if (!empty($accounts)) {
        $accountDetails = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
            $config['odoo']['db'],
            $uid,
            $authCredential,
            'account.account',
            'read',
            [$accounts],
            ['fields' => ['id', 'name', 'code', 'user_type_id']]
        ], $authCredential);
        
        echo "Account Details:\n";
        foreach ($accountDetails as $account) {
            echo "  ID: {$account['id']}, Name: {$account['name']}, Code: {$account['code']}\n";
        }
    }
    echo "\n";
} catch (Exception $e) {
    echo "✗ Error cek account: " . $e->getMessage() . "\n\n";
}

echo "Test 4: Coba buat invoice minimal...\n";
try {
    // Ambil partner dan product yang sudah ada
    $partners = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'res.partner',
        'search',
        [[]],
        ['limit' => 1]
    ], $authCredential);
    
    $partnerId = $partners[0];
    echo "Menggunakan Partner ID: {$partnerId}\n";
    
    $products = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'product.product',
        'search',
        [[]],
        ['limit' => 1]
    ], $authCredential);
    
    $productId = $products[0];
    echo "Menggunakan Product ID: {$productId}\n";
    
    // Coba buat invoice dengan data minimal
    $lines = [
        [0, 0, [
            'product_id' => $productId,
            'quantity' => 1,
            'price_unit' => 100000
        ]]
    ];
    
    $invoiceData = [
        'move_type' => 'out_invoice',
        'partner_id' => $partnerId,
        'invoice_date' => date('Y-m-d'),
        'ref' => 'TEST-' . time(),
        'invoice_line_ids' => $lines,
    ];
    
    echo "Invoice data yang akan dikirim:\n";
    echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";
    
    $moveId = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'account.move',
        'create',
        [$invoiceData]
    ], $authCredential);
    
    if ($moveId) {
        echo "✓ Invoice berhasil dibuat (ID: {$moveId})\n";
        
        // Coba post invoice
        jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
            $config['odoo']['db'],
            $uid,
            $authCredential,
            'account.move',
            'action_post',
            [[(int) $moveId]]
        ], $authCredential);
        echo "✓ Invoice berhasil dipost\n";
    } else {
        echo "✗ Gagal membuat invoice\n";
    }
} catch (Exception $e) {
    echo "✗ Error membuat invoice: " . $e->getMessage() . "\n";
    echo "Ini biasanya terjadi karena:\n";
    echo "1. Product tidak memiliki account income yang diset\n";
    echo "2. Journal tidak dikonfigurasi dengan benar\n";
    echo "3. Company tidak memiliki chart of account\n";
    echo "4. User tidak memiliki permission yang cukup\n\n";
}

echo "=== Test Selesai ===\n";