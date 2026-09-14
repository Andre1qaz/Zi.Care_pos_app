<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Helpers\JwtHelper;
use App\Models\AuditLog;
use App\Models\Role;
use App\Repositories\UserRepository;
use Phalcon\Di\Di;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = Di::getDefault()->getShared('userRepository');
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new AppException('Invalid email or password', 401);
        }

        // Modifikasi: Tambahkan pengecekan jika password di database masih berupa teks biasa (plaintext)
        $isPasswordValid = false;
        if (password_verify($password, $user->password)) {
            $isPasswordValid = true;
        } elseif ($password === $user->password) {
            $isPasswordValid = true;
        }

        if (!$isPasswordValid) {
            throw new AppException('Invalid email or password', 401);
        }

        $role = Role::findFirst($user->role_id);

        $token = JwtHelper::encode([
            'sub'   => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
            'role'  => $role?->name ?? 'cashier',
        ]);

        $this->logActivity($user->id, 'User logged in');

        return [
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $role?->name,
            ],
        ];
    }

    public function getCurrentUser(array $authUser): array
    {
        $user = $this->userRepository->findById((int) $authUser['sub']);
        if (!$user) {
            throw new AppException('User not found', 404);
        }

        $role = Role::findFirst($user->role_id);

        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $role?->name,
        ];
    }

    private function logActivity(int $userId, string $activity): void
    {
        $log = new AuditLog();
        $log->user_id = $userId;
        $log->activity = $activity;
        $log->save();
    }
}