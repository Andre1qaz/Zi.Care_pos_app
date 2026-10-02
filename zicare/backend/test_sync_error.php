<?php

/**
 * Script untuk test sync dan melihat error detail
 */

echo "=== Test Sync Invoice ke Odoo dengan Error Detail ===\n\n";

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

echo "Konfigurasi Odoo:\n";
echo "URL: {$config['odoo']['url']}\n";
echo "Database: {$config['odoo']['db']}\n";
echo "Username: {$config['odoo']['username']}\n";
echo "Sync Enabled: " . ($config['odoo']['enabled'] ? 'YES' : 'NO') . "\n\n";

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Koneksi database berhasil\n\n";
} catch (PDOException $e) {
    echo "✗ Gagal koneksi ke database: " . $e->getMessage() . "\n";
    exit(1);
}

// Ambil invoice terbaru yang gagal sync
$stmt = $pdo->query("SELECT id, invoice_number FROM invoices WHERE sync_status = 'failed' ORDER BY created_at DESC LIMIT 1");
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    echo "Tidak ada invoice yang gagal sync untuk di-test\n";
    exit(0);
}

echo "Invoice yang akan di-test sync:\n";
echo "ID: {$invoice['id']}\n";
echo "Invoice Number: {$invoice['invoice_number']}\n\n";

// Ambil detail invoice lengkap
$stmt = $pdo->prepare("
    SELECT i.*, c.customer_name, c.email as customer_email, c.phone as customer_phone
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$invoice['id']]);
$invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil detail invoice items
$stmt = $pdo->prepare("
    SELECT id.*, p.product_name, p.product_code
    FROM invoice_details id
    JOIN products p ON id.product_id = p.id
    WHERE id.invoice_id = ?
");
$stmt->execute([$invoice['id']]);
$invoiceDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Invoice Data:\n";
echo "Customer: " . ($invoiceData['customer_name'] ?? 'Walk-in Customer') . "\n";
echo "Email: " . ($invoiceData['customer_email'] ?? 'walkin@pos.com') . "\n";
echo "Phone: " . ($invoiceData['customer_phone'] ?? '') . "\n";
echo "Total Amount: {$invoiceData['total_amount']}\n";
echo "Paid Amount: {$invoiceData['paid_amount']}\n";
echo "Payment Method: {$invoiceData['payment_method']}\n\n";

echo "Invoice Items:\n";
foreach ($invoiceDetails as $item) {
    echo "- {$item['product_name']} ({$item['product_code']}) x {$item['quantity']} @ {$item['price']}\n";
}
echo "\n";

// Test Odoo connection manually
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
        throw new \RuntimeException($response['error']['message'] ?? 'Odoo returned an unspecified error');
    }

    return $response['result'] ?? null;
}

$authCredential = $config['odoo']['password'];

echo "Test 1: Authenticate ke Odoo...\n";
try {
    $uid = jsonRpc($config['odoo']['url'], 'common', 'authenticate', [
        $config['odoo']['db'],
        $config['odoo']['username'],
        $authCredential,
        []
    ], $authCredential);
    
    if ($uid) {
        echo "✓ Authentication berhasil! User ID: {$uid}\n\n";
    } else {
        echo "✗ Authentication gagal\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Error authentication: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Test 2: Sync Customer...\n";
try {
    $customerName = !empty($invoiceData['customer_name']) ? $invoiceData['customer_name'] : 'Walk-in Customer';
    $customerEmail = !empty($invoiceData['customer_email']) && $invoiceData['customer_email'] !== 'walkin@pos.com' ? $invoiceData['customer_email'] : '';
    $customerPhone = !empty($invoiceData['customer_phone']) ? $invoiceData['customer_phone'] : '';

    $domain = [];
    if (!empty($customerEmail)) {
        $domain[] = ['email', '=', $customerEmail];
    } else {
        $domain[] = ['name', '=', $customerName];
    }

    $existing = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'res.partner',
        'search',
        [$domain],
        ['limit' => 1]
    ], $authCredential);

    if (!empty($existing)) {
        $partnerId = (int) $existing[0];
        echo "✓ Customer sudah ada (ID: {$partnerId})\n\n";
    } else {
        $partnerId = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
            $config['odoo']['db'],
            $uid,
            $authCredential,
            'res.partner',
            'create',
            [[
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone ?? '',
            ]]
        ], $authCredential);
        
        if ($partnerId) {
            echo "✓ Customer baru berhasil dibuat (ID: {$partnerId})\n\n";
        } else {
            echo "✗ Gagal membuat customer\n\n";
            exit(1);
        }
    }
} catch (Exception $e) {
    echo "✗ Error sync customer: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Test 3: Sync Products dan Invoice...\n";
try {
    $lines = [];
    foreach ($invoiceDetails as $item) {
        // Sync product
        $existing = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
            $config['odoo']['db'],
            $uid,
            $authCredential,
            'product.product',
            'search',
            [[['default_code', '=', $item['product_code']]]],
            ['limit' => 1]
        ], $authCredential);

        if (!empty($existing)) {
            $productId = (int) $existing[0];
            echo "✓ Product {$item['product_name']} sudah ada (ID: {$productId})\n";
        } else {
            $productId = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
                $config['odoo']['db'],
                $uid,
                $authCredential,
                'product.product',
                'create',
                [[
                    'name' => $item['product_name'],
                    'list_price' => (float) $item['price'],
                    'default_code' => $item['product_code'],
                    'type' => 'consu',
                    'sale_ok' => true
                ]]
            ], $authCredential);
            
            if ($productId) {
                echo "✓ Product {$item['product_name']} baru berhasil dibuat (ID: {$productId})\n";
            } else {
                echo "✗ Gagal membuat product {$item['product_name']}\n";
                exit(1);
            }
        }

        $lines[] = [0, 0, [
            'product_id' => $productId,
            'quantity' => (int) $item['quantity'],
            'price_unit' => (float) $item['price']
        ]];
    }

    echo "\nMembuat invoice di Odoo...\n";
    $moveId = jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
        $config['odoo']['db'],
        $uid,
        $authCredential,
        'account.move',
        'create',
        [[
            'move_type' => 'out_invoice',
            'partner_id' => $partnerId,
            'invoice_date' => date('Y-m-d', strtotime($invoiceData['created_at'])),
            'ref' => 'POS: ' . $invoiceData['invoice_number'],
            'invoice_line_ids' => $lines,
        ]]
    ], $authCredential);

    if ($moveId) {
        echo "✓ Invoice berhasil dibuat (ID: {$moveId})\n";
        
        // Post invoice
        jsonRpc($config['odoo']['url'], 'object', 'execute_kw', [
            $config['odoo']['db'],
            $uid,
            $authCredential,
            'account.move',
            'action_post',
            [[(int) $moveId]]
        ], $authCredential);
        echo "✓ Invoice berhasil dipost\n";
        
        // Update database
        $stmt = $pdo->prepare("UPDATE invoices SET sync_status = 'synced', odoo_move_id = ? WHERE id = ?");
        $stmt->execute([$moveId, $invoice['id']]);
        echo "✓ Status invoice di database berhasil diupdate\n";
        
    } else {
        echo "✗ Gagal membuat invoice\n";
    }
} catch (Exception $e) {
    echo "✗ Error sync invoice: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Update status ke failed
    $stmt = $pdo->prepare("UPDATE invoices SET sync_status = 'failed' WHERE id = ?");
    $stmt->execute([$invoice['id']]);
}

echo "\n=== Test Selesai ===\n";