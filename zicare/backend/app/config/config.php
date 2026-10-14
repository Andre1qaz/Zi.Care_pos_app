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
        // No insecure fallback: JwtHelper refuses to sign/verify when this is empty.
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 28800),
    ],
    'odoo' => [
        'url'               => $_ENV['ODOO_URL'] ?? 'http://localhost:8069',
        'db'                => $_ENV['ODOO_DB'] ?? 'odoo',
        'username'          => $_ENV['ODOO_USERNAME'] ?? 'admin',
        'password'          => $_ENV['ODOO_PASSWORD'] ?? '',
        'api_key'           => $_ENV['ODOO_API_KEY'] ?? '',
        'journal_cash_code' => $_ENV['ODOO_JOURNAL_CASH_CODE'] ?? 'CSH1',
        'journal_bank_code' => $_ENV['ODOO_JOURNAL_BANK_CODE'] ?? 'BNK1',
        'enabled'           => filter_var($_ENV['ODOO_SYNC_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        // Bila true (default), sinkronisasi saat checkout dijalankan di proses
        // worker terpisah supaya respons transaksi tetap cepat. Set false untuk
        // sinkron langsung (memblokir) — berguna untuk pengujian.
        'async'             => filter_var($_ENV['ODOO_SYNC_ASYNC'] ?? true, FILTER_VALIDATE_BOOLEAN),
        // Bila true, service akan melengkapi akun "Outstanding Receipts" pada
        // payment method line Odoo yang belum diisi (dibutuhkan agar pembayaran
        // benar-benar terposting & invoice lunas di modul Accounting).
        'auto_configure'    => filter_var($_ENV['ODOO_AUTO_CONFIGURE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'max_retry'         => (int) ($_ENV['ODOO_SYNC_MAX_RETRY'] ?? 3),
    ],
    'app' => [
        'env'   => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url'   => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
];