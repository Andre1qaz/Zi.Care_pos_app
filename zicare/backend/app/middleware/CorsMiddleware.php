<?php

declare(strict_types=1);

namespace App\Middleware;

use Phalcon\Events\Event;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function call(Micro $app)
    {
        $response = $app->response;
        $origin = $app->request->getHeader('Origin') ?: '*';

        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->setHeader('Access-Control-Max-Age', '86400');

        if (strtoupper($app->request->getMethod()) === 'OPTIONS') {
            $response->setStatusCode(204);
            $response->setContent('');
            $response->send();
            return false;
        }

        return true;
    }

    public function beforeHandleRoute(Event $event, Micro $app): bool
    {
        return $this->call($app);
    }
}
