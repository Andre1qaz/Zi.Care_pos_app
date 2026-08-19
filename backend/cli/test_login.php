<?php

declare(strict_types=1);

/**
 * Verify login credentials against the database.
 * Usage: php cli/test_login.php admin@pos.local Admin@123
 */

require __DIR__ . '/../vendor/autoload.php';

define('BASE_PATH', dirname(__DIR__));

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

use Phalcon\Di\Di;

$di = new Di();
require BASE_PATH . '/app/config/services.php';

use App\Services\AuthService;

$email = $argv[1] ?? 'admin@pos.local';
$password = $argv[2] ?? 'Admin@123';

try {
    $result = (new AuthService())->login($email, $password);
    echo "Login OK for {$email}\n";
    echo 'Role: ' . ($result['user']['role'] ?? 'unknown') . PHP_EOL;
} catch (Throwable $e) {
    echo 'Login failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
