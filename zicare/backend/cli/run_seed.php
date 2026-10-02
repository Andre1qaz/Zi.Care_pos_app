<?php

declare(strict_types=1);

/**
 * Import seed data into the configured database.
 * Usage: php cli/run_seed.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = require __DIR__ . '/../app/config/config.php';

$seedFile = dirname(__DIR__, 2) . '/database/seeds/001_seed_data.sql';

if (!is_file($seedFile)) {
    echo "Seed file not found: {$seedFile}\n";
    exit(1);
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['dbname']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents($seedFile);
    $sql = preg_replace('/USE\s+\w+;/', '', $sql);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
    }

    echo "Seed data imported into {$config['database']['dbname']}\n";
    echo "Run: php cli/list_users.php\n";
    echo "Run: php cli/fix_passwords.php\n";
} catch (PDOException $e) {
    echo 'Seed failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
