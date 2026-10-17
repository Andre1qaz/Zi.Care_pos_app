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
    public int $failed_login_attempts = 0;
    public ?string $locked_until = null;
    public ?string $last_login_at = null;
    public ?string $last_login_ip = null;
    public ?string $last_login_user_agent = null;
    public int $two_factor_enabled = 0;
    public ?string $two_factor_secret = null;
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
            'id'                     => 'id',
            'name'                   => 'name',
            'email'                  => 'email',
            'password'               => 'password',
            'role_id'                => 'role_id',
            'is_active'              => 'is_active',
            'failed_login_attempts'  => 'failed_login_attempts',
            'locked_until'           => 'locked_until',
            'last_login_at'          => 'last_login_at',
            'last_login_ip'          => 'last_login_ip',
            'last_login_user_agent'  => 'last_login_user_agent',
            'two_factor_enabled'     => 'two_factor_enabled',
            'two_factor_secret'      => 'two_factor_secret',
            'created_at'             => 'created_at',
            'updated_at'             => 'updated_at',
        ];
    }
}
