<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::findFirst([
            'conditions' => 'email = :email: AND is_active = 1',
            'bind'       => ['email' => $email],
        ]) ?: null;
    }

    public function findById(int $id): ?User
    {
        return User::findFirst($id) ?: null;
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $users = User::find([
            'limit'  => $limit,
            'offset' => $offset,
            'order'  => 'id DESC',
        ]);

        return $users->toArray();
    }
}
