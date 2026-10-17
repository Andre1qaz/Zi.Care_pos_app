<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

/**
 * Token reset password. Hanya hash (hash('sha256', token)) yang disimpan,
 * sehingga bocornya isi tabel tidak langsung memberi akses reset.
 */
class PasswordResetToken extends Model
{
    public ?int $id = null;
    public int $user_id;
    public string $token_hash;
    public string $expires_at;
    public ?string $used_at = null;
    public ?string $requested_ip = null;
    public ?string $created_at = null;

    public function initialize(): void
    {
        $this->setSource('password_reset_tokens');
        $this->belongsTo('user_id', User::class, 'id', ['alias' => 'user']);
    }
}
