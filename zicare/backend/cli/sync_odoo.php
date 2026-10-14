#!/usr/bin/env php
<?php

/**
 * Odoo Sync Worker CLI
 * Usage: php cli/sync_odoo.php
 * Cron: jalankan setiap 5 menit (misal via scheduler)
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

use Phalcon\Di\Di;
use App\Services\OdooSyncService;

$di = new Di();
require BASE_PATH . '/app/config/services.php';

$db = $di->getShared('db');
$syncService = $di->getShared('odooSyncService');

// Opsi: --force untuk re-sync paksa (hapus & buat ulang move di Odoo),
//        --id=NN untuk menyinkronkan satu invoice tertentu.
$force = in_array('--force', $argv ?? [], true);
$onlyId = 0;
foreach (($argv ?? []) as $arg) {
    if (str_starts_with($arg, '--id=')) {
        $onlyId = (int) substr($arg, 5);
    }
}

if ($onlyId > 0) {
    $pending = [['id' => $onlyId]];
} else {
    $pending = $db->query(
        "SELECT id FROM invoices WHERE sync_status IN ('pending', 'failed') ORDER BY created_at ASC LIMIT 50"
    )->fetchAll();
}

echo "Processing " . count($pending) . " invoices" . ($force ? " (force re-sync)" : "") . "...\n";

$okCount = 0;
$failCount = 0;

foreach ($pending as $row) {
    $invoiceId = (int) $row['id'];
    echo "Syncing invoice ID: {$invoiceId}... ";

    try {
        $ok = $syncService->retrySync($invoiceId, $force);
        if ($ok) {
            echo "OK\n";
            $okCount++;
        } else {
            echo "FAILED (lihat storage/logs/app.log)\n";
            $failCount++;
        }
    } catch (\Throwable $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

echo "Done. Success: {$okCount}, Failed: {$failCount}.\n";

// Exit code non-zero bila ada kegagalan, agar cron/scheduler bisa mendeteksi.
exit($failCount > 0 ? 1 : 0);
