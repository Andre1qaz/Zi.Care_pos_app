<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\JwtHelper;
use Phalcon\Events\Event;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Micro\MiddlewareInterface;
use Phalcon\Di\Di;

class AuthMiddleware implements MiddlewareInterface
{
    public function call(Micro $app)
    {
        $authHeader = $app->request->getHeader('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            $app->response->setStatusCode(401);
            $app->response->setJsonContent(['success' => false, 'message' => 'Unauthorized: Token is missing']);
            $app->response->send();
            exit; // Menghentikan eksekusi secara paksa agar tidak tembus ke controller
        }

        try {
            $token = substr($authHeader, 7);
            $decoded = JwtHelper::decode($token);
            
            // Konversi stdClass dari JWT ke array secara penuh
            $userData = json_decode(json_encode($decoded), true);
            
            // Antisipasi jika data JWT dibungkus di dalam key 'data' atau 'user'
            if (isset($userData['data'])) {
                $userData = $userData['data'];
            } elseif (isset($userData['user'])) {
                $userData = $userData['user'];
            }

            // Antisipasi jika database/JWT menggunakan 'role_id' bukan 'role' string
            if (!isset($userData['role']) && isset($userData['role_id'])) {
                if ($userData['role_id'] == 1) {
                    $userData['role'] = 'administrator';
                } elseif ($userData['role_id'] == 2) {
                    $userData['role'] = 'manager';
                } else {
                    $userData['role'] = 'cashier';
                }
            }

            // Memastikan data disuntikkan ke Global Dependency Injection
            Di::getDefault()->setShared('authUser', function () use ($userData) {
                return $userData;
            });
            
        } catch (\Exception $e) {
            $app->response->setStatusCode(401);
            $app->response->setJsonContent(['success' => false, 'message' => 'Invalid or expired token']);
            $app->response->send();
            exit;
        }

        return true;
    }

    public function beforeHandleRoute(Event $event, Micro $app): bool
    {
        return $this->call($app);
    }
}