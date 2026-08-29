<?php

/**
 * Script untuk cek status sinkronisasi invoice ke Odoo
 */

echo "=== Cek Status Sinkronisasi Invoice ke Odoo ===\n\n";

// Load database configuration
$config = require __DIR__ . '/app/config/config.php';
$dbConfig = $config['database'];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Koneksi database berhasil\n\n";
    
    // Cek invoice terbaru
    $stmt = $pdo->query("SELECT id, invoice_number, total_amount, paid_amount, sync_status, odoo_move_id, created_at FROM invoices ORDER BY created_at DESC LIMIT 10");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($invoices)) {
        echo "⚠ Tidak ada invoice yang ditemukan di database\n";
        echo "Silakan buat transaksi POS terlebih dahulu\n";
    } else {
        echo "Invoice terbaru:\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-15s %-20s %-15s %-15s %-15s %-15s %-20s\n", 
            "ID", "Invoice Number", "Total", "Paid", "Sync Status", "Odoo Move ID", "Created At");
        echo str_repeat("-", 100) . "\n";
        
        foreach ($invoices as $invoice) {
            printf("%-15s %-20s %-15s %-15s %-15s %-15s %-20s\n",
                $invoice['id'],
                $invoice['invoice_number'],
                number_format($invoice['total_amount'], 0),
                number_format($invoice['paid_amount'], 0),
                $invoice['sync_status'],
                $invoice['odoo_move_id'] ?? 'N/A',
                $invoice['created_at']
            );
        }
        
        echo str_repeat("-", 100) . "\n";
        
        // Cek yang pending sync
        $pending = array_filter($invoices, fn($inv) => $inv['sync_status'] === 'pending');
        if (!empty($pending)) {
            echo "\n⚠ Ada " . count($pending) . " invoice yang pending sync ke Odoo\n";
            echo "Invoice yang pending:\n";
            foreach ($pending as $inv) {
                echo "  - ID: {$inv['id']}, Invoice: {$inv['invoice_number']}\n";
            }
        }
        
        // Cek yang berhasil sync
        $synced = array_filter($invoices, fn($inv) => $inv['sync_status'] === 'synced');
        if (!empty($synced)) {
            echo "\n✓ Ada " . count($synced) . " invoice yang berhasil sync ke Odoo\n";
            echo "Invoice yang berhasil sync:\n";
            foreach ($synced as $inv) {
                echo "  - ID: {$inv['id']}, Invoice: {$inv['invoice_number']}, Odoo Move ID: {$inv['odoo_move_id']}\n";
            }
        }
        
        // Cek yang gagal sync
        $failed = array_filter($invoices, fn($inv) => $inv['sync_status'] === 'failed');
        if (!empty($failed)) {
            echo "\n✗ Ada " . count($failed) . " invoice yang gagal sync ke Odoo\n";
            echo "Invoice yang gagal sync:\n";
            foreach ($failed as $inv) {
                echo "  - ID: {$inv['id']}, Invoice: {$inv['invoice_number']}\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error koneksi database: " . $e->getMessage() . "\n";
}

echo "\n=== Cek Selesai ===\n";
