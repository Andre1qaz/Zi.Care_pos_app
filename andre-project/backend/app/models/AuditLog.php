<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class AuditLog extends Model
{
    public ?int $id = null;
    public ?int $user_id = null;
    public string $activity;
    public ?string $entity_type = null;
    public ?int $entity_id = null;
    public ?string $metadata = null;

    public function initialize(): void
    {
        $this->setSource('audit_logs');
        $this->belongsTo('user_id', User::class, 'id', ['alias' => 'user']);
    }
}
