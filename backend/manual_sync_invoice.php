<?php

/**
 * Script untuk manual sync invoice ke Odoo dengan detail error
 */

echo "=== Manual Sync Invoice ke Odoo ===\n\n";

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

$odooConfig = [
    'url' => isset($_ENV['ODOO_URL']) ? $_ENV['ODOO_URL'] : 'http://localhost:8069',
    'db' => isset($_ENV['ODOO_DB']) ? $_ENV['ODOO_DB'] : 'pos_db',
    'username' => isset($_ENV['ODOO_USERNAME']) ? $_ENV['ODOO_USERNAME'] : 'andre',
    'password' => isset($_ENV['ODOO_API_KEY']) ? $_ENV['ODOO_API_KEY'] : (isset($_ENV['ODOO_PASSWORD']) ? $_ENV['ODOO_PASSWORD'] : ''),
    'use_api_key' => true,
];

echo "Konfigurasi Odoo:\n";
echo "URL: {$odooConfig['url']}\n";
echo "Database: {$odooConfig['db']}\n";
echo "Username: {$odooConfig['username']}\n";
echo "Password: " . str_repeat('*', strlen($odooConfig['password'])) . "\n\n";

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
        $errorMsg = isset($response['error']['message']) ? $response['error']['message'] : 'Odoo returned an unspecified error';
        $errorData = isset($response['error']['data']) ? $response['error']['data'] : [];
        echo "Detail Error Odoo:\n";
        echo "Message: {$errorMsg}\n";
        if (!empty($errorData)) {
            echo "Data: " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
        }
        throw new \RuntimeException($errorMsg);
    }

    return isset($response['result']) ? $response['result'] : null;
}

echo "Test 1: Authentication...\n";
try {
    $uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
        $odooConfig['db'],
        $odooConfig['username'],
        $odooConfig['password'],
        []
    ]);

    if ($uid) {
        echo "✓ Authentication berhasil! User ID: {$uid}\n\n";
    } else {
        echo "✗ Authentication gagal\n\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ Error authentication: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Cek invoice yang failed
$config = require __DIR__ . '/app/config/config.php';
$dbConfig = $config['database'];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Test 2: Cek invoice yang failed...\n";
    $stmt = $pdo->query("SELECT i.id, i.invoice_number, i.total_amount, i.paid_amount, i.payment_method, i.created_at, 
                            COALESCE(c.customer_name, 'Walk-in Customer') as customer_name,
                            COALESCE(c.email, 'walkin@pos.com') as customer_email,
                            COALESCE(c.phone, '') as customer_phone
                         FROM invoices i 
                         LEFT JOIN customers c ON i.customer_id = c.id 
                         WHERE i.sync_status = 'failed' ORDER BY i.created_at DESC LIMIT 1");
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        echo "⚠ Tidak ada invoice yang failed\n";
        exit(0);
    }
    
    echo "✓ Ditemukan invoice yang failed: {$invoice['invoice_number']}\n";
    echo "  Customer: {$invoice['customer_name']}\n";
    echo "  Total: " . number_format($invoice['total_amount'], 0) . "\n";
    echo "  Paid: " . number_format($invoice['paid_amount'], 0) . "\n\n";
    
    // Get invoice details
    $stmt = $pdo->prepare("SELECT id.*, p.product_name, p.product_code 
                            FROM invoice_details id 
                            LEFT JOIN products p ON id.product_id = p.id 
                            WHERE id.invoice_id = ?");
    $stmt->execute([$invoice['id']]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Invoice details:\n";
    foreach ($details as $detail) {
        $productName = isset($detail['product_name']) ? $detail['product_name'] : 'Unknown Product';
        $productCode = isset($detail['product_code']) ? $detail['product_code'] : 'SKU-000';
        echo "  - {$productName} ({$productCode}) - {$detail['quantity']} x " . number_format($detail['price'], 0) . "\n";
    }
    echo "\n";
    
    // Test 3: Sync Customer
    echo "Test 3: Sync Customer...\n";
    try {
        $customerName = isset($invoice['customer_name']) && !empty($invoice['customer_name']) ? $invoice['customer_name'] : 'Walk-in Customer';
        $customerEmail = isset($invoice['customer_email']) && !empty($invoice['customer_email']) && $invoice['customer_email'] !== 'walkin@pos.com' ? $invoice['customer_email'] : '';
        $customerPhone = isset($invoice['customer_phone']) && !empty($invoice['customer_phone']) ? $invoice['customer_phone'] : '';
        
        $domain = [];
        if (!empty($customerEmail)) {
            $domain[] = ['email', '=', $customerEmail];
        } else {
            $domain[] = ['name', '=', $customerName];
        }
        
        $existing = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'res.partner',
            'search',
            [$domain],
            ['limit' => 1]
        ]);
        
        if (!empty($existing)) {
            $partnerId = (int) $existing[0];
            echo "✓ Customer sudah ada (ID: {$partnerId})\n";
        } else {
            $partnerId = (int) jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                $odooConfig['db'],
                $uid,
                $odooConfig['password'],
                'res.partner',
                'create',
                [[
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                ]]
            ]);
            echo "✓ Customer baru berhasil dibuat (ID: {$partnerId})\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error sync customer: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 4: Sync Product
    echo "Test 4: Sync Product...\n";
    $lines = [];
    foreach ($details as $item) {
        try {
            $productName = isset($item['product_name']) ? $item['product_name'] : 'Unknown Product';
            $productCode = isset($item['product_code']) ? $item['product_code'] : 'SKU-000';
            
            $existing = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                $odooConfig['db'],
                $uid,
                $odooConfig['password'],
                'product.product',
                'search',
                [[['default_code', '=', $productCode]]],
                ['limit' => 1]
            ]);
            
            if (!empty($existing)) {
                $productId = (int) $existing[0];
                echo "✓ Product {$productName} sudah ada (ID: {$productId})\n";
            } else {
                $productId = (int) jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                    $odooConfig['db'],
                    $uid,
                    $odooConfig['password'],
                    'product.product',
                    'create',
                    [[
                        'name' => $productName,
                        'list_price' => (float) $item['price'],
                        'default_code' => $productCode,
                        'type' => 'consu',
                        'sale_ok' => true
                    ]]
                ]);
                echo "✓ Product {$productName} baru berhasil dibuat (ID: {$productId})\n";
            }
            
            $lines[] = [0, 0, [
                'product_id' => $productId,
                'quantity' => (int) $item['quantity'],
                'price_unit' => (float) $item['price']
            ]];
        } catch (\Exception $e) {
            $productName = isset($item['product_name']) ? $item['product_name'] : 'Unknown';
            echo "✗ Error sync product {$productName}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Test 5: Sync Invoice
    echo "Test 5: Sync Invoice...\n";
    try {
        // Get sale journal
        $saleJournal = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.journal',
            'search',
            [[['code', '=', 'SALE']]],
            ['limit' => 1]
        ]);
        
        if (empty($saleJournal)) {
            echo "⚠ Journal sale tidak ditemukan, menggunakan journal lain\n";
            $saleJournal = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
                $odooConfig['db'],
                $uid,
                $odooConfig['password'],
                'account.journal',
                'search',
                [[]],
                ['limit' => 1]
            ]);
        }
        
        $journalId = !empty($saleJournal) ? (int)$saleJournal[0] : 6;
        echo "Menggunakan journal ID: {$journalId}\n";
        
        $invoiceData = [
            'move_type' => 'out_invoice',
            'partner_id' => $partnerId,
            'invoice_date' => date('Y-m-d', strtotime($invoice['created_at'])),
            'ref' => 'POS: ' . $invoice['invoice_number'],
            'journal_id' => $journalId,
            'invoice_line_ids' => $lines,
        ];
        
        echo "Mencoba membuat invoice dengan data:\n";
        echo json_encode($invoiceData, JSON_PRETTY_PRINT) . "\n\n";
        
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
            
            // Update database
            $stmt = $pdo->prepare("UPDATE invoices SET sync_status = 'synced', odoo_move_id = ? WHERE id = ?");
            $stmt->execute([$moveId, $invoice['id']]);
            echo "✓ Database updated\n";
            
            // Test payment
            if ((float)$invoice['paid_amount'] > 0) {
                echo "\nTest 6: Sync Payment...\n";
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
                            'amount' => (float)$invoice['paid_amount'],
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
            }
            
        } else {
            echo "✗ Gagal membuat invoice\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error sync invoice: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error database: " . $e->getMessage() . "\n";
}

echo "\n=== Test Selesai ===\n";
