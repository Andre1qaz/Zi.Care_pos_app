<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "Mencoba koneksi dengan konfigurasi:\n";
echo "  HOST: " . ($_ENV['DB_HOST'] ?? '(tidak ada)') . "\n";
echo "  PORT: " . ($_ENV['DB_PORT'] ?? '(tidak ada)') . "\n";
echo "  NAME: " . ($_ENV['DB_NAME'] ?? '(tidak ada)') . "\n";
echo "  USER: " . ($_ENV['DB_USER'] ?? '(tidak ada)') . "\n";
echo "\n";

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "✅ BERHASIL terhubung ke database: " . $_ENV['DB_NAME'] . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Jumlah data di tabel 'users': " . $row['total'] . "\n";
} catch (Exception $e) {
    echo "❌ GAGAL: " . $e->getMessage() . "\n";
}