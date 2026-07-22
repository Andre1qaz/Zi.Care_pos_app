<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class User extends Model
{
    public ?int $id = null;
    public string $name;
    public string $email;
    public string $password;
    public int $role_id;
    public int $is_active = 1;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function initialize(): void
    {
        $this->setSource('users');
        $this->belongsTo('role_id', Role::class, 'id', ['alias' => 'role']);
    }

    public function columnMap(): array
    {
        return [
            'id'         => 'id',
            'name'       => 'name',
            'email'      => 'email',
            'password'   => 'password',
            'role_id'    => 'role_id',
            'is_active'  => 'is_active',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
