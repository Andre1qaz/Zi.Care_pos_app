<?php

declare(strict_types=1);

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{
    public static function encode(array $payload): string
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $payload['iat'] = time();
        $payload['exp'] = time() + $config['jwt']['expiry'];

        return JWT::encode($payload, $config['jwt']['secret'], 'HS256');
    }

    public static function decode(string $token): object
    {
        $config = require BASE_PATH . '/app/config/config.php';
        return JWT::decode($token, new Key($config['jwt']['secret'], 'HS256'));
    }
}
