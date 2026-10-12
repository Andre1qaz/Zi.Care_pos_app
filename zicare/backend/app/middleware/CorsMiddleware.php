<?php

declare(strict_types=1);

namespace App\Middleware;

use Phalcon\Events\Event;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function call(Micro $app)
    {
        $this->applyHeaders($app, $app->response);

        if (strtoupper($app->request->getMethod()) === 'OPTIONS') {
            $app->response->setStatusCode(204);
            $app->response->setContent('');
            $app->response->send();
            return false;
        }

        return true;
    }

    /**
     * Re-apply CORS headers to the response that will actually be sent.
     * Controllers return fresh Response objects (ResponseHelper::json), so
     * headers set on the shared response during `before` are lost without this.
     */
    public function applyToResponse(Micro $app): void
    {
        $returned = $app->getReturnedValue();
        $this->applyHeaders($app, $returned instanceof ResponseInterface ? $returned : $app->response);
    }

    public function beforeHandleRoute(Event $event, Micro $app): bool
    {
        return $this->call($app);
    }

    private function applyHeaders(Micro $app, $response): void
    {
        if (!$response instanceof ResponseInterface) {
            return;
        }

        $origin = $app->request->getHeader('Origin') ?: '';

        // Allow-list from CORS_ALLOWED_ORIGINS (comma-separated). When unset we
        // keep the legacy behaviour so dev/local tooling keeps working.
        $allowed = array_values(array_filter(array_map('trim', explode(',', (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '')))));

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        } elseif ($origin !== '' && $allowed === []) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } elseif ($origin === '') {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }

        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Max-Age', '86400');
    }
}