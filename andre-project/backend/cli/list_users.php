<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';port=' . ($_ENV['DB_PORT'] ?? 3306) . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

echo "Database: {$_ENV['DB_NAME']}\n\n";
foreach ($pdo->query('SELECT id, email, name, is_active FROM users ORDER BY id') as $row) {
    echo "{$row['id']} | {$row['email']} | {$row['name']} | active={$row['is_active']}\n";
}
