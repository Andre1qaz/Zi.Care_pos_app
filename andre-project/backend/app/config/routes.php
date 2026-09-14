<?php

declare(strict_types=1);

return [
    '/api/v1/auth' => [
        'handler' => \App\Controllers\AuthController::class,
        'routes' => [
            ['method' => 'post', 'path' => '/login', 'action' => 'login'],
            ['method' => 'post', 'path' => '/logout', 'action' => 'logout'],
            ['method' => 'get', 'path' => '/me', 'action' => 'me'],
        ],
    ],
    '/api/v1/users' => [
        'handler' => \App\Controllers\UserController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'post', 'path' => '', 'action' => 'create'],
            ['method' => 'put', 'path' => '/{id}', 'action' => 'update'],
            ['method' => 'delete', 'path' => '/{id}', 'action' => 'delete'],
        ],
    ],
    '/api/v1/products' => [
        'handler' => \App\Controllers\ProductController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'post', 'path' => '', 'action' => 'create'],
            ['method' => 'put', 'path' => '/{id}', 'action' => 'update'],
            ['method' => 'delete', 'path' => '/{id}', 'action' => 'delete'],
        ],
    ],
    '/api/v1/categories' => [
        'handler' => \App\Controllers\CategoryController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'post', 'path' => '', 'action' => 'create'],
            ['method' => 'put', 'path' => '/{id}', 'action' => 'update'],
            ['method' => 'delete', 'path' => '/{id}', 'action' => 'delete'],
        ],
    ],
    '/api/v1/customers' => [
        'handler' => \App\Controllers\CustomerController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'post', 'path' => '', 'action' => 'create'],
            ['method' => 'put', 'path' => '/{id}', 'action' => 'update'],
            ['method' => 'delete', 'path' => '/{id}', 'action' => 'delete'],
        ],
    ],
    '/api/v1/transactions' => [
        'handler' => \App\Controllers\TransactionController::class,
        'routes' => [
            ['method' => 'post', 'path' => '', 'action' => 'create'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
        ],
    ],
    '/api/v1/invoices' => [
        'handler' => \App\Controllers\InvoiceController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'get', 'path' => '/{id}/pdf', 'action' => 'pdf'],
            ['method' => 'post', 'path' => '/{id}/payment', 'action' => 'addPayment'],
            ['method' => 'get', 'path' => '/{id}/payments', 'action' => 'paymentHistory'],
        ],
    ],
    '/api/v1/outstanding-payments' => [
        'handler' => \App\Controllers\OutstandingPaymentController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'list'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'get'],
            ['method' => 'post', 'path' => '/{id}/payment', 'action' => 'addPayment'],
            ['method' => 'get', 'path' => '/stats', 'action' => 'stats'],
        ],
    ],
    '/api/v1/dashboard' => [
        'handler' => \App\Controllers\DashboardController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
        ],
    ],
    '/api/v1/reports' => [
        'handler' => \App\Controllers\ReportController::class,
        'routes' => [
            ['method' => 'get', 'path' => '/daily-sales', 'action' => 'dailySales'],
            ['method' => 'get', 'path' => '/monthly-sales', 'action' => 'monthlySales'],
            ['method' => 'get', 'path' => '/product-sales', 'action' => 'productSales'],
            ['method' => 'get', 'path' => '/payments', 'action' => 'payments'],
            ['method' => 'get', 'path' => '/customer-transactions', 'action' => 'customerTransactions'],
        ],
    ],
    '/api/v1/audit-logs' => [
        'handler' => \App\Controllers\AuditLogController::class,
        'routes' => [
            ['method' => 'get', 'path' => '', 'action' => 'index'],
            ['method' => 'get', 'path' => '/{id}', 'action' => 'show'],
            ['method' => 'get', 'path' => '/stats', 'action' => 'stats'],
        ],
    ],
];
