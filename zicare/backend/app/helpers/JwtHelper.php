<?php

declare(strict_types=1);

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    private static function secret(): string
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $secret = (string) ($config['jwt']['secret'] ?? '');

        if ($secret === '') {
            throw new \RuntimeException('JWT_SECRET is not configured. Set it in backend/.env.');
        }

        return $secret;
    }

    public static function encode(array $payload): string
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $payload['iat'] = time();
        $payload['exp'] = time() + $config['jwt']['expiry'];

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(self::secret(), 'HS256'));
    }
}
