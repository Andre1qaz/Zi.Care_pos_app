<?php

declare(strict_types=1);

namespace App\Helpers;

class ResponseHelper
{
    public static function json(array $data, int $statusCode = 200)
    {
        $response = new \Phalcon\Http\Response();
        $response->setStatusCode($statusCode);
        $response->setContentType('application/json', 'UTF-8');
        $response->setJsonContent($data);
        return $response;
    }

    public static function success(mixed $data = null, string $message = 'Success', ?array $meta = null): \Phalcon\Http\Response
    {
        $payload = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }
        return self::json($payload);
    }

    public static function error(string $message, int $statusCode = 400, ?array $errors = null): \Phalcon\Http\Response
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return self::json($payload, $statusCode);
    }
}
