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
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        if ($app->request->isOptions()) {
            $response->setStatusCode(200);
            return false;
        }

        return true;
    }

    public function beforeHandleRoute(Event $event, Micro $app): bool
    {
        return $this->call($app);
    }
}
