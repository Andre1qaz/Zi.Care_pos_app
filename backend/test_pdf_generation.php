<?php

/**
 * Script untuk test pembuatan PDF invoice
 */

echo "=== Test PDF Generation ===\n\n";

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

// Ambil invoice terbaru
$stmt = $pdo->query("SELECT id, invoice_number FROM invoices ORDER BY created_at DESC LIMIT 1");
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    echo "Tidak ada invoice untuk di-test\n";
    exit(0);
}

echo "Invoice yang akan di-test:\n";
echo "ID: {$invoice['id']}\n";
echo "Invoice Number: {$invoice['invoice_number']}\n\n";

// Load Phalcon bootstrap
define('BASE_PATH', __DIR__);
require __DIR__ . '/vendor/autoload.php';

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

// Setup repositories
$di->setShared('invoiceRepository', function() {
    return new App\Repositories\InvoiceRepository();
});

echo "Mencoba generate PDF...\n";

try {
    $invoiceService = new App\Services\InvoiceService();
    $pdf = $invoiceService->generatePdf((int)$invoice['id']);
    
    if ($pdf) {
        $pdfSize = strlen($pdf);
        echo "✓ PDF berhasil dibuat!\n";
        echo "Ukuran PDF: {$pdfSize} bytes\n";
        
        // Simpan PDF ke file untuk test
        $filename = __DIR__ . "/test_invoice_{$invoice['id']}.pdf";
        file_put_contents($filename, $pdf);
        echo "✓ PDF disimpan ke: {$filename}\n";
        
        if ($pdfSize > 1000) {
            echo "✓ PDF memiliki konten yang wajar (>{$pdfSize} bytes)\n";
        } else {
            echo "⚠ PDF terlalu kecil, mungkin kosong atau error\n";
        }
    } else {
        echo "✗ Gagal membuat PDF\n";
    }
} catch (Exception $e) {
    echo "✗ Error saat generate PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Selesai ===\n";