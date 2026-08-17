<?php

declare(strict_types=1);

return [
    'database' => [
        'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'dbname'   => $_ENV['DB_NAME'] ?? 'pos_db',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'charset'  => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 28800),
    ],
    'odoo' => [
        'url'               => $_ENV['ODOO_URL'] ?? 'http://127.0.0.1:8069',
        'db'                => $_ENV['ODOO_DB'] ?? 'odoo_dev',
        'username'          => $_ENV['ODOO_USERNAME'] ?? 'admin',
        'password'          => $_ENV['ODOO_PASSWORD'] ?? 'c835d5ec4fad4d4006a964f1c6ee96678614d393',
        'journal_cash_code' => $_ENV['ODOO_JOURNAL_CASH_CODE'] ?? 'CSH1',
        'journal_bank_code' => $_ENV['ODOO_JOURNAL_BANK_CODE'] ?? 'BNK1',
        'enabled'           => filter_var($_ENV['ODOO_SYNC_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'max_retry'         => (int) ($_ENV['ODOO_SYNC_MAX_RETRY'] ?? 3),
    ],
    'app' => [
        'env'   => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url'   => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
];