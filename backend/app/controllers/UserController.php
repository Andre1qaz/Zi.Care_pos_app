<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;

class UserController extends BaseController
{
    public function index()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $page = (int) ($this->request->getQuery('page') ?? 1);
            $limit = (int) ($this->request->getQuery('limit') ?? 20);
            $users = $this->di->getShared('userRepository')->findAll($page, $limit);
            return ResponseHelper::success($users);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $user = $this->di->getShared('userRepository')->findById($id);
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }
            $data = $user->toArray();
            unset($data['password']);
            return ResponseHelper::success($data);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function create()
    {
        try {
            $this->requireRole(['administrator']);
            $body = $this->getJsonBody();
            $user = new User();
            $user->name = $body['name'];
            $user->email = $body['email'];
            $user->password = password_hash($body['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $user->role_id = (int) $body['role_id'];
            $user->save();

            $data = $user->toArray();
            unset($data['password']);
            return ResponseHelper::success($data, 'User created', null);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $user = $this->di->getShared('userRepository')->findById($id);
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            $body = $this->getJsonBody();
            $user->assign($body);
            if (!empty($body['password'])) {
                $user->password = password_hash($body['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            }
            $user->save();

            $data = $user->toArray();
            unset($data['password']);
            return ResponseHelper::success($data, 'User updated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $user = $this->di->getShared('userRepository')->findById($id);
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }
            $user->is_active = 0;
            $user->save();
            return ResponseHelper::success(null, 'User deactivated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}