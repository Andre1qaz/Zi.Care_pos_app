<?php

/**
 * Script untuk memperbaiki konfigurasi Odoo di .env
 */

echo "=== Memperbaiki Konfigurasi Odoo ===\n\n";

$envFile = __DIR__ . '/.env';
$envExample = __DIR__ . '/.env.example';

// Baca file .env saat ini
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    echo "File .env saat ini:\n";
    echo "====================\n";
    foreach ($lines as $line) {
        if (strpos(trim($line), 'ODOO_') === 0) {
            echo $line . "\n";
        }
    }
    echo "\n";
} else {
    echo "File .env tidak ditemukan, akan dibuat baru dari .env.example\n\n";
    if (file_exists($envExample)) {
        copy($envExample, $envFile);
        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    } else {
        echo "File .env.example juga tidak ditemukan\n";
        exit(1);
    }
}

// Konfigurasi yang benar berdasarkan test koneksi yang berhasil
$correctConfig = [
    'ODOO_URL' => 'http://localhost:8069',
    'ODOO_DB' => 'pos_db',  // Perbaikan: dari 'odoo' ke 'pos_db'
    'ODOO_USERNAME' => 'andre',  // Perbaikan: dari 'admin' ke 'andre'
    'ODOO_PASSWORD' => 'andre123',  // Password valid user andre (terverifikasi)
    'ODOO_SYNC_ENABLED' => 'true',
    'ODOO_SYNC_MAX_RETRY' => '3',
    'ODOO_JOURNAL_CASH_CODE' => 'CSH1',
    'ODOO_JOURNAL_BANK_CODE' => 'BNK1',
];

echo "Konfigurasi yang akan diterapkan:\n";
echo "================================\n";
foreach ($correctConfig as $key => $value) {
    echo "{$key}={$value}\n";
}
echo "\n";

// Update file .env
$newLines = [];
$updatedKeys = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (empty($trimmed) || strpos($trimmed, '#') === 0) {
        $newLines[] = $line;
        continue;
    }
    
    if (strpos($trimmed, '=') !== false) {
        list($key, $value) = explode('=', $trimmed, 2);
        $key = trim($key);
        
        if (array_key_exists($key, $correctConfig)) {
            $newLines[] = "{$key}={$correctConfig[$key]}";
            $updatedKeys[] = $key;
        } else {
            $newLines[] = $line;
        }
    } else {
        $newLines[] = $line;
    }
}

// Tambahkan config yang belum ada
foreach ($correctConfig as $key => $value) {
    if (!in_array($key, $updatedKeys)) {
        $newLines[] = "{$key}={$value}";
    }
}

// Tulis kembali file .env
file_put_contents($envFile, implode("\n", $newLines));

echo "✓ File .env berhasil diperbarui\n\n";
echo "Key yang diperbarui:\n";
foreach ($updatedKeys as $key) {
    echo "- {$key}\n";
}

// Tambahkan config baru jika ada
$addedKeys = array_diff(array_keys($correctConfig), $updatedKeys);
if (!empty($addedKeys)) {
    echo "\nKey yang ditambahkan:\n";
    foreach ($addedKeys as $key) {
        echo "- {$key}\n";
    }
}

echo "\n=== Perbaikan Selesai ===\n";
echo "Silakan restart server PHP Anda untuk menerapkan perubahan konfigurasi.\n";
echo "Setelah restart, jalankan 'php test_sync_error.php' untuk test sync kembali.\n";