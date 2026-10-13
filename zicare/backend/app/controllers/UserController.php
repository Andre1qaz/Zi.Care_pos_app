<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
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

            // Never expose password hashes through the API.
            foreach ($users as &$user) {
                unset($user['password']);
            }
            unset($user);

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

            $errors = [];
            if (empty($body['name'])) {
                $errors['name'] = 'Name is required';
            }
            if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required';
            }
            if (empty($body['password']) || strlen((string) $body['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            if (empty($body['role_id'])) {
                $errors['role_id'] = 'Role is required';
            }
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            $repo = $this->di->getShared('userRepository');
            if ($repo->findByEmail($body['email'])) {
                throw new ValidationException(['email' => 'Email is already registered']);
            }

            $user = new User();
            $user->name = $body['name'];
            $user->email = $body['email'];
            $user->password = password_hash($body['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $user->role_id = (int) $body['role_id'];
            $user->is_active = isset($body['is_active']) ? (int) (bool) $body['is_active'] : 1;

            if (!$user->save()) {
                throw new AppException('Failed to create user');
            }

            $data = $user->toArray();
            unset($data['password']);
            $this->logAudit("Created user {$user->email}", 'user', (int) $user->id);
            return ResponseHelper::success($data, 'User created', null);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(int $id)
    {
        try {
            $this->requireRole(['administrator']);
            $repo = $this->di->getShared('userRepository');
            $user = $repo->findById($id);
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            $body = $this->getJsonBody();

            // Only update whitelisted, validated fields (no mass assignment).
            $errors = [];
            if (array_key_exists('name', $body) && trim((string) $body['name']) === '') {
                $errors['name'] = 'Name cannot be empty';
            }
            if (array_key_exists('email', $body) && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email is required';
            }
            if (!empty($body['password']) && strlen((string) $body['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            if (array_key_exists('name', $body)) {
                $user->name = $body['name'];
            }
            if (array_key_exists('email', $body)) {
                $existing = $repo->findByEmail($body['email']);
                if ($existing && (int) $existing->id !== $id) {
                    throw new ValidationException(['email' => 'Email is already registered']);
                }
                $user->email = $body['email'];
            }
            if (!empty($body['password'])) {
                $user->password = password_hash($body['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            }
            if (array_key_exists('role_id', $body)) {
                $user->role_id = (int) $body['role_id'];
            }
            if (array_key_exists('is_active', $body)) {
                $user->is_active = (int) (bool) $body['is_active'];
            }

            if (!$user->save()) {
                throw new AppException('Failed to update user');
            }

            $data = $user->toArray();
            unset($data['password']);
            $this->logAudit("Updated user {$user->email}", 'user', (int) $user->id);
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
            $this->logAudit("Deactivated user {$user->email}", 'user', (int) $user->id);
            return ResponseHelper::success(null, 'User deactivated');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}