<?php

/**
 * Script untuk mengecek status sinkronisasi transaksi ke Odoo
 */

echo "=== Cek Status Sinkronisasi Transaksi ===\n\n";

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

// Cek total invoice
echo "=== Statistik Invoice ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices");
$totalInvoices = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total Invoice: {$totalInvoices}\n\n";

// Cek status sinkronisasi
echo "=== Status Sinkronisasi Invoice ===\n";
$stmt = $pdo->query("SELECT sync_status, COUNT(*) as count FROM invoices GROUP BY sync_status");
$syncStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($syncStatuses as $status) {
    echo "{$status['sync_status']}: {$status['count']}\n";
}
echo "\n";

// Cek invoice yang gagal sync
echo "=== Invoice yang Gagal Sinkronisasi ===\n";
$stmt = $pdo->query("SELECT id, invoice_number, sync_status, odoo_move_id, created_at FROM invoices WHERE sync_status = 'failed' ORDER BY created_at DESC LIMIT 10");
$failedInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($failedInvoices)) {
    echo "Tidak ada invoice yang gagal sinkronisasi\n\n";
} else {
    foreach ($failedInvoices as $invoice) {
        echo "ID: {$invoice['id']}, Invoice: {$invoice['invoice_number']}, Status: {$invoice['sync_status']}, Odoo ID: " . ($invoice['odoo_move_id'] ?? 'NULL') . ", Created: {$invoice['created_at']}\n";
    }
    echo "\n";
}

// Cek invoice yang masih pending
echo "=== Invoice yang Masih Pending Sinkronisasi ===\n";
$stmt = $pdo->query("SELECT id, invoice_number, sync_status, odoo_move_id, created_at FROM invoices WHERE sync_status = 'pending' ORDER BY created_at DESC LIMIT 10");
$pendingInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($pendingInvoices)) {
    echo "Tidak ada invoice yang pending sinkronisasi\n\n";
} else {
    foreach ($pendingInvoices as $invoice) {
        echo "ID: {$invoice['id']}, Invoice: {$invoice['invoice_number']}, Status: {$invoice['sync_status']}, Odoo ID: " . ($invoice['odoo_move_id'] ?? 'NULL') . ", Created: {$invoice['created_at']}\n";
    }
    echo "\n";
}

// Cek odoo_sync_logs
echo "=== Log Sinkronisasi Odoo ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM odoo_sync_logs");
$totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Total Log: {$totalLogs}\n\n";

if ($totalLogs > 0) {
    echo "Status Log:\n";
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM odoo_sync_logs GROUP BY status");
    $logStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($logStatuses as $status) {
        echo "{$status['status']}: {$status['count']}\n";
    }
    echo "\n";
    
    echo "Error Terakhir:\n";
    $stmt = $pdo->query("SELECT invoice_id, sync_type, error_message, created_at FROM odoo_sync_logs WHERE status = 'failed' ORDER BY created_at DESC LIMIT 5");
    $failedLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($failedLogs)) {
        echo "Tidak ada error log\n\n";
    } else {
        foreach ($failedLogs as $log) {
            echo "Invoice ID: {$log['invoice_id']}, Type: {$log['sync_type']}, Error: {$log['error_message']}, Time: {$log['created_at']}\n";
        }
        echo "\n";
    }
}

// Cek 5 invoice terbaru
echo "=== 5 Invoice Terbaru ===\n";
$stmt = $pdo->query("SELECT id, invoice_number, total_amount, payment_status, sync_status, odoo_move_id, created_at FROM invoices ORDER BY created_at DESC LIMIT 5");
$recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recentInvoices as $invoice) {
    echo "ID: {$invoice['id']}, Invoice: {$invoice['invoice_number']}, Amount: {$invoice['total_amount']}, Payment: {$invoice['payment_status']}, Sync: {$invoice['sync_status']}, Odoo ID: " . ($invoice['odoo_move_id'] ?? 'NULL') . ", Created: {$invoice['created_at']}\n";
}
echo "\n";

echo "=== Analisis ===\n";
if ($totalInvoices == 0) {
    echo "⚠ Tidak ada invoice di database. Transaksi belum pernah dilakukan.\n";
} else {
    $pendingCount = 0;
    $failedCount = 0;
    $syncedCount = 0;
    
    foreach ($syncStatuses as $status) {
        if ($status['sync_status'] === 'pending') $pendingCount = $status['count'];
        if ($status['sync_status'] === 'failed') $failedCount = $status['count'];
        if ($status['sync_status'] === 'synced') $syncedCount = $status['count'];
    }
    
    if ($pendingCount > 0) {
        echo "⚠ Ada {$pendingCount} invoice yang masih pending sinkronisasi.\n";
        echo "   Kemungkinan sinkronisasi belum pernah dijalankan atau terjadi error.\n";
    }
    
    if ($failedCount > 0) {
        echo "⚠ Ada {$failedCount} invoice yang gagal sinkronisasi.\n";
        echo "   Cek error log untuk detail error yang terjadi.\n";
    }
    
    if ($syncedCount > 0) {
        echo "✓ Ada {$syncedCount} invoice yang berhasil disinkronkan ke Odoo.\n";
    } else {
        echo "⚠ Tidak ada invoice yang berhasil disinkronkan ke Odoo.\n";
    }
}