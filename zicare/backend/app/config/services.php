<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\Di;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\MetaData\Memory as ModelsMetaData;
use Phalcon\Mvc\Router;

$config = require BASE_PATH . '/app/config/config.php';

$di = Di::getDefault();

$di->setShared('config', fn () => $config);

$di->setShared('router', function () {
    $router = new Router(false);
    $router->removeExtraSlashes(true);
    return $router;
});

$di->setShared('request', fn () => new Request());

$di->setShared('response', fn () => new Response());

$di->setShared('modelsManager', fn () => new ModelsManager());

$di->setShared('modelsMetadata', fn () => new ModelsMetaData());

$di->setShared('db', function () use ($config) {
    return new Mysql([
        'host'     => $config['database']['host'],
        'port'     => $config['database']['port'],
        'dbname'   => $config['database']['dbname'],
        'username' => $config['database']['username'],
        'password' => $config['database']['password'],
        'charset'  => $config['database']['charset'],
    ]);
});

$di->setShared('logger', function () {
    $logPath = $_ENV['LOG_PATH'] ?? BASE_PATH . '/storage/logs/app.log';
    $logger = new Logger('pos');
    $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    return $logger;
});

// Repositories
$di->setShared('userRepository', fn () => new \App\Repositories\UserRepository());
$di->setShared('productRepository', fn () => new \App\Repositories\ProductRepository());
$di->setShared('categoryRepository', fn () => new \App\Repositories\CategoryRepository());
$di->setShared('customerRepository', fn () => new \App\Repositories\CustomerRepository());
$di->setShared('invoiceRepository', fn () => new \App\Repositories\InvoiceRepository());

// Services
$di->setShared('authService', fn () => new \App\Services\AuthService());
$di->setShared('productService', fn () => new \App\Services\ProductService());
$di->setShared('categoryService', fn () => new \App\Services\CategoryService());
$di->setShared('customerService', fn () => new \App\Services\CustomerService());
$di->setShared('transactionService', fn () => new \App\Services\TransactionService());
$di->setShared('paymentService', fn () => new \App\Services\PaymentService());
$di->setShared('invoiceService', fn () => new \App\Services\InvoiceService());
$di->setShared('dashboardService', fn () => new \App\Services\DashboardService());
$di->setShared('reportService', fn () => new \App\Services\ReportService());
$di->setShared('odooSyncService', fn () => new \App\Services\OdooSyncService());