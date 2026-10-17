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

    public function __construct()
    {
        $di = new Di();
        require BASE_PATH . '/app/config/services.php';
        $this->app = new Micro($di);
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerNotFoundHandler();
        $this->registerErrorHandler();
    }

    private function registerMiddleware(): void
    {
        $corsMiddleware = new CorsMiddleware();
        $authMiddleware = new AuthMiddleware();

        $this->app->before(function () use ($corsMiddleware) {
            return $corsMiddleware->call($this->app);
        });

        // Re-apply CORS headers after routing: controllers return fresh Response
        // objects, so shared-response headers would otherwise be dropped.
        $this->app->after(function () use ($corsMiddleware) {
            $corsMiddleware->applyToResponse($this->app);
        });

        $this->app->before(function () use ($authMiddleware) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $path = strtok($uri, '?');

            // Only skip auth for truly public auth routes.
            // /api/v1/auth/me must still require a valid token.
            $publicPaths = [
                '/api/v1/auth/login',
                '/api/v1/auth/logout',
                '/api/v1/auth/status',
                '/api/v1/auth/forgot-password',
                '/api/v1/auth/reset-password',
                '/api/v1/auth/verify-2fa',
            ];

            foreach ($publicPaths as $publicPath) {
                if (str_starts_with($path, $publicPath)) {
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

    private function registerNotFoundHandler(): void
    {
        $this->app->notFound(function () {
            return \App\Helpers\ResponseHelper::json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        });
    }

    private function registerErrorHandler(): void
    {
        $this->app->error(function ($exception) {
            $logger = $this->app->getDI()->get('logger');
            $logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);

            $statusCode = method_exists($exception, 'getStatusCode')
                ? $exception->getStatusCode()
                : 500;

            $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $message = $debug
                ? $exception->getMessage()
                : 'Internal server error';

            $errors = method_exists($exception, 'getErrors') ? $exception->getErrors() : [];

            $payload = [
                'success' => false,
                'message' => $message,
            ];
            if (!empty($errors)) {
                $payload['errors'] = $errors;
            }

            return \App\Helpers\ResponseHelper::json($payload, $statusCode);
        });
    }

    public function run(): void
    {
        $this->app->handle($_SERVER['REQUEST_URI']);
    }
}