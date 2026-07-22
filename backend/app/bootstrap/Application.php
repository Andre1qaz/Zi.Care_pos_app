<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use Phalcon\Di\Di;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\Collection;

class Application
{
    private Micro $app;

    /**
     * Prefix path yang tidak butuh autentikasi (publik).
     */
    private array $publicPrefixes = [
        '/api/v1/auth',
    ];

    public function __construct()
    {
        $di = new Di();
        require BASE_PATH . '/app/config/services.php';
        $this->app = new Micro($di);
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerErrorHandler();
    }

    private function registerMiddleware(): void
    {
        $corsMiddleware = new CorsMiddleware();
        $authMiddleware = new AuthMiddleware();

        $this->app->before(function () use ($corsMiddleware) {
            return $corsMiddleware->call($this->app);
        });

        $this->app->before(function () use ($authMiddleware) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $path = strtok($uri, '?');

            foreach ($this->publicPrefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }

            return $authMiddleware->call($this->app);
        });
    }

    private function registerRoutes(): void
    {
        $routes = require BASE_PATH . '/app/config/routes.php';

        foreach ($routes as $prefix => $config) {
            $collection = new Collection();
            $collection->setHandler($config['handler'], true);
            $collection->setPrefix($prefix);

            foreach ($config['routes'] as $route) {
                $collection->{$route['method']}($route['path'], $route['action']);
            }

            $this->app->mount($collection);
        }
    }

    private function registerErrorHandler(): void
    {
        $this->app->error(function ($exception) {
            $logger = $this->app->getDI()->get('logger');
            $logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);

            $statusCode = method_exists($exception, 'getStatusCode')
                ? $exception->getStatusCode()
                : 500;

            $message = $_ENV['APP_DEBUG'] === 'true'
                ? $exception->getMessage()
                : 'Internal server error';

            return \App\Helpers\ResponseHelper::json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        });
    }

    public function run(): void
    {
        $this->app->handle($_SERVER['REQUEST_URI']);
    }
}