<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class AuthController extends BaseController
{
    public function login()
    {
        try {
            $body = $this->getJsonBody();
            $result = $this->di->getShared('authService')->login(
                $body['email'] ?? '',
                $body['password'] ?? ''
            );
            return ResponseHelper::success($result, 'Login successful');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function logout()
    {
        return ResponseHelper::success(null, 'Logout successful');
    }

    public function me()
    {
        try {
            $user = $this->di->getShared('authService')->getCurrentUser($this->getAuthUser());
            return ResponseHelper::success($user);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
