<?php

/**
 * Script untuk test koneksi ke Odoo
 * Jalankan dengan: php test_odoo_connection.php
 */

echo "=== Test Koneksi Odoo ===\n\n";

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

// Konfigurasi Odoo (diambil dari .env)
$odooConfig = [
    'url' => $_ENV['ODOO_URL'] ?? 'http://localhost:8069',
    'db' => $_ENV['ODOO_DB'] ?? 'pos_db',
    'username' => $_ENV['ODOO_USERNAME'] ?? 'andre',
    'password' => $_ENV['ODOO_PASSWORD'] ?? '',
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
        $errorMsg = $response['error']['message'] ?? 'Odoo returned an unspecified error';
        $errorData = $response['error']['data'] ?? [];
        echo "Detail Error Odoo:\n";
        echo "Message: {$errorMsg}\n";
        if (!empty($errorData)) {
            echo "Data: " . json_encode($errorData, JSON_PRETTY_PRINT) . "\n";
        }
        throw new \RuntimeException($errorMsg);
    }

    return $response['result'] ?? null;
}

// Test 1: Koneksi dasar
echo "Test 1: Mencoba koneksi ke {$odooConfig['url']}...\n";
try {
    $ch = curl_init($odooConfig['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode > 0) {
        echo "✓ Server Odoo dapat diakses (HTTP {$httpCode})\n\n";
    } else {
        echo "✗ Gagal mengakses server Odoo\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error koneksi: " . $e->getMessage() . "\n\n";
}

// Test 1.5: Cek database yang tersedia
echo "Test 1.5: Mencoba mendapatkan list database...\n";
try {
    // Method 'list' tidak tersedia di Odoo 17, kita skip test ini
    echo "ℹ Method 'list' tidak tersedia di Odoo 17\n";
    echo "⚠ Silakan cek nama database Odoo Anda secara manual:\n";
    echo "   1. Buka browser dan akses http://localhost:8069\n";
    echo "   2. Lihat nama database di halaman login atau\n";
    echo "   3. Cek file konfigurasi Odoo (odoo.conf)\n";
    echo "   4. Atau jalankan query di PostgreSQL: SELECT datname FROM pg_database WHERE datistemplate = false;\n\n";
} catch (\Exception $e) {
    echo "✗ Error mendapatkan list database: " . $e->getMessage() . "\n\n";
}

// Test 2: Authentication
echo "Test 2: Mencoba authenticate ke Odoo...\n";
echo "Metode: Password\n";
try {
    $uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
        $odooConfig['db'],
        $odooConfig['username'],
        $odooConfig['password'],
        []
    ]);

    if ($uid) {
        echo "✓ Authentication berhasil! User ID: {$uid}\n";
        echo "✓ User yang digunakan: {$odooConfig['username']}\n\n";
    } else {
        echo "✗ Authentication gagal\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error authentication: " . $e->getMessage() . "\n\n";
}

// Test 3: Cek akses ke model
echo "Test 3: Mencoba mengakses model res.partner...\n";
try {
    $uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
        $odooConfig['db'],
        $odooConfig['username'],
        $odooConfig['password'],
        []
    ]);

    $partners = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'res.partner',
        'search',
        [[]],
        ['limit' => 5]
    ]);

    echo "✓ Berhasil mengakses res.partner. Ditemukan " . count($partners) . " partner\n\n";
} catch (\Exception $e) {
    echo "✗ Error mengakses model: " . $e->getMessage() . "\n\n";
}

// Test 4: Cek journal
echo "Test 4: Mencoba mengakses account.journal...\n";
try {
    $uid = jsonRpc($odooConfig['url'], 'common', 'authenticate', [
        $odooConfig['db'],
        $odooConfig['username'],
        $odooConfig['password'],
        []
    ]);

    $journals = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
        $odooConfig['db'],
        $uid,
        $odooConfig['password'],
        'account.journal',
        'search',
        [[]],
        ['limit' => 10]
    ]);

    echo "✓ Berhasil mengakses account.journal. Ditemukan " . count($journals) . " journal\n";
    
    // Tampilkan detail journal
    if (!empty($journals)) {
        $journalDetails = jsonRpc($odooConfig['url'], 'object', 'execute_kw', [
            $odooConfig['db'],
            $uid,
            $odooConfig['password'],
            'account.journal',
            'read',
            [$journals],
            ['fields' => ['name', 'code', 'type']]
        ]);
        
        echo "Journal yang tersedia:\n";
        foreach ($journalDetails as $journal) {
            echo "  - {$journal['name']} (Code: {$journal['code']}, Type: {$journal['type']})\n";
        }
    } else {
        echo "⚠ Tidak ada journal yang tersedia. Perlu setup journal di Odoo.\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "✗ Error mengakses journal: " . $e->getMessage() . "\n\n";
}

echo "=== Test Selesai ===\n";

echo "\n=== Tips untuk mengatasi masalah ===\n";
echo "1. Pastikan server Odoo berjalan dan database PostgreSQL tersedia:\n";
echo "   - Odoo di http://localhost:8069\n";
echo "   - PostgreSQL di 127.0.0.1:5432 (diperlukan agar Odoo berfungsi)\n\n";

echo "2. Jika menggunakan password biasa:\n";
echo "   - Pastikan password user benar di file backend/.env (ODOO_PASSWORD)\n\n";

echo "3. Pastikan database Odoo benar:\n";
echo "   - Cek list database di Test 1.5\n";
echo "   - Sesuaikan nilai 'db' dengan database yang tersedia\n\n";

echo "4. Untuk debugging lebih lanjut:\n";
echo "   - Cek log Odoo di terminal/server\n";
echo "   - Pastikan user admin memiliki akses ke model yang dibutuhkan\n";
