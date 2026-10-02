<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Role extends Model
{
    public ?int $id = null;
    public string $name;
    public ?string $description = null;

    public function initialize(): void
    {
        $this->setSource('roles');
        $this->hasMany('id', User::class, 'role_id', ['alias' => 'users']);
    }
}
