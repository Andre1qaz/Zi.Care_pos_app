<?php

/**
 * Script untuk test manual sync invoice ke Odoo
 * Ini akan mencoba sync invoice yang sudah ada ke Odoo
 */

echo "=== Test Manual Sync Invoice ke Odoo ===\n\n";

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

// Load Phalcon bootstrap
define('BASE_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';

// Load services configuration
require __DIR__ . '/app/config/services.php';

// Setup Phalcon DI
$di = new Phalcon\Di\FactoryDefault();

// Setup database connection
$di->setShared('db', function() use ($config) {
    return new Phalcon\Db\Adapter\Pdo\Mysql([
        'host' => $config['database']['host'],
        'port' => $config['database']['port'],
        'username' => $config['database']['username'],
        'password' => $config['database']['password'],
        'dbname' => $config['database']['dbname'],
        'charset' => $config['database']['charset'],
    ]);
});

// Setup logger
$di->setShared('logger', function() {
    $logger = new Phalcon\Logger\Logger('messages', [
        'main' => new Phalcon\Logger\Adapter\Stream(__DIR__ . '/storage/logs/app.log')
    ]);
    return $logger;
});

// Setup repositories
$di->setShared('invoiceRepository', function() {
    return new App\Repositories\InvoiceRepository();
});

// Setup services
$di->setShared('odooSyncService', function() {
    return new App\Services\OdooSyncService();
});

echo "Mencoba sync invoice...\n";

try {
    $odooSyncService = $di->getShared('odooSyncService');
    $result = $odooSyncService->syncInvoice((int)$invoice['id']);
    
    if ($result) {
        echo "✓ Sync invoice berhasil!\n";
        
        // Cek status invoice setelah sync
        $stmt = $pdo->prepare("SELECT sync_status, odoo_move_id FROM invoices WHERE id = ?");
        $stmt->execute([$invoice['id']]);
        $updatedInvoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Status setelah sync:\n";
        echo "Sync Status: {$updatedInvoice['sync_status']}\n";
        echo "Odoo Move ID: " . ($updatedInvoice['odoo_move_id'] ?? 'NULL') . "\n";
    } else {
        echo "✗ Sync invoice gagal\n";
    }
} catch (Exception $e) {
    echo "✗ Error saat sync: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Selesai ===\n";