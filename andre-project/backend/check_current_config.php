<?php

/**
 * Script untuk mengecek konfigurasi Odoo yang sedang aktif
 */

echo "=== Cek Konfigurasi Odoo yang Aktif ===\n\n";

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
    echo "✓ File .env berhasil dimuat\n\n";
} else {
    echo "✗ File .env tidak ditemukan\n\n";
}

// Load config
$config = require __DIR__ . '/app/config/config.php';

echo "Konfigurasi Odoo yang Aktif:\n";
echo "============================\n";
echo "URL: " . ($config['odoo']['url'] ?? 'Tidak di-set') . "\n";
echo "Database: " . ($config['odoo']['db'] ?? 'Tidak di-set') . "\n";
echo "Username: " . ($config['odoo']['username'] ?? 'Tidak di-set') . "\n";
echo "Password: " . (isset($config['odoo']['password']) ? str_repeat('*', strlen($config['odoo']['password'])) : 'Tidak di-set') . "\n";
echo "API Key: " . (isset($config['odoo']['api_key']) ? substr($config['odoo']['api_key'], 0, 8) . '...' : 'Tidak di-set') . "\n";
echo "Journal Cash Code: " . ($config['odoo']['journal_cash_code'] ?? 'Tidak di-set') . "\n";
echo "Journal Bank Code: " . ($config['odoo']['journal_bank_code'] ?? 'Tidak di-set') . "\n";
echo "Sync Enabled: " . ($config['odoo']['enabled'] ? 'YES' : 'NO') . "\n";
echo "Max Retry: " . ($config['odoo']['max_retry'] ?? 'Tidak di-set') . "\n";

echo "\n=== Analisis Potensi Masalah ===\n";

if (!($config['odoo']['enabled'] ?? false)) {
    echo "⚠ MASALAH UTAMA: ODOO_SYNC_ENABLED = false\n";
    echo "   Sinkronisasi Odoo dinonaktifkan. Transaksi tidak akan disinkronkan ke Odoo.\n";
    echo "   Solusi: Set ODOO_SYNC_ENABLED=true di file .env\n\n";
}

if ($config['odoo']['db'] === 'odoo') {
    echo "⚠ POTENSI MASALAH: Database Odoo diset ke 'odoo' (default)\n";
    echo "   Pastikan ini sesuai dengan nama database Odoo yang sebenarnya.\n";
    echo "   Dari test koneksi, database yang digunakan adalah 'pos_db'\n\n";
}

if (empty($config['odoo']['api_key']) && $config['odoo']['password'] === 'admin') {
    echo "⚠ POTENSI MASALAH: Masih menggunakan password default 'admin'\n";
    echo "   Pastikan password/API key sudah dikonfigurasi dengan benar.\n\n";
}

echo "=== Rekomendasi ===\n";
echo "1. Pastikan ODOO_SYNC_ENABLED=true di .env\n";
echo "2. Pastikan ODOO_DB sesuai dengan database Odoo yang sebenarnya\n";
echo "3. Pastikan ODOO_API_KEY sudah di-set dengan benar\n";
echo "4. Restart server PHP setelah mengubah konfigurasi\n";