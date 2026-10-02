<?php

declare(strict_types=1);

/**
 * Setup or reset default users and passwords.
 * Usage: php cli/fix_passwords.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$config = require __DIR__ . '/../app/config/config.php';

$defaultUsers = [
    [
        'name'     => 'Administrator',
        'email'    => 'admin@pos.local',
        'password' => 'Password123',
        'role_id'  => 1,
    ],
    [
        'name'     => 'Manager',
        'email'    => 'manager@pos.local',
        'password' => 'Password123',
        'role_id'  => 2,
    ],
    [
        'name'     => 'Cashier',
        'email'    => 'cashier@pos.local',
        'password' => 'Password123',
        'role_id'  => 3,
    ],
];

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['dbname']
    );

    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database: {$config['database']['dbname']}\n\n";

    $findStmt = $pdo->prepare('SELECT id, password FROM users WHERE email = :email LIMIT 1');
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, role_id, is_active) VALUES (:name, :email, :password, :role_id, 1)'
    );
    $updateStmt = $pdo->prepare('UPDATE users SET password = :password, role_id = :role_id WHERE email = :email');

    foreach ($defaultUsers as $user) {
        $findStmt->execute(['email' => $user['email']]);
        $existing = $findStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $insertStmt->execute([
                'name'     => $user['name'],
                'email'    => $user['email'],
                'password' => $user['password'],
                'role_id'  => $user['role_id'],
            ]);
            echo "Created user {$user['email']}\n";
            continue;
        }

        if ($existing['password'] === $user['password']) {
            echo "Password already correct for {$user['email']}\n";
            continue;
        }

        $updateStmt->execute([
            'password' => $user['password'],
            'role_id'  => $user['role_id'],
            'email'    => $user['email'],
        ]);
        echo "Updated password for {$user['email']}\n";
    }

    echo "\nDefault credentials:\n";
    echo "  admin@pos.local   / Password123\n";
    echo "  manager@pos.local / Password123\n";
    echo "  cashier@pos.local / Password123\n";
} catch (PDOException $e) {
    echo 'Failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
