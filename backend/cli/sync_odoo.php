#!/usr/bin/env php
<?php

/**
 * Odoo Sync Worker CLI
 * Usage: php cli/sync_odoo.php
 * Cron: */5 * * * * php /var/www/pos/backend/cli/sync_odoo.php
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

$pending = $db->query(
    "SELECT id FROM invoices WHERE sync_status IN ('pending', 'failed') ORDER BY created_at ASC LIMIT 50"
)->fetchAll();

echo "Processing " . count($pending) . " invoices...\n";

foreach ($pending as $row) {
    $invoiceId = (int) $row['id'];
    echo "Syncing invoice ID: {$invoiceId}... ";

    try {
        $syncService->retrySync($invoiceId);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
