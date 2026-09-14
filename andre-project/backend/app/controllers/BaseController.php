<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\ResponseHelper;
use Phalcon\Di\Injectable;

abstract class BaseController extends Injectable
{
    protected function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function getAuthUser(): array
    {
        // Gunakan getDI() dan try-catch untuk mengamankan pengambilan dependency
        try {
            if ($this->getDI()->has('authUser')) {
                $user = $this->getDI()->getShared('authUser');
                return is_array($user) ? $user : [];
            }
        } catch (\Throwable $e) {
            // Abaikan jika service tidak ditemukan
        }
        
        return [];
    }

    protected function handleException(\Throwable $e)
    {
        error_log("=== CRITICAL ERROR ===");
        error_log("Pesan: " . $e->getMessage());
        error_log("File: " . $e->getFile() . " (Baris: " . $e->getLine() . ")");
        error_log("=========================");

        if ($e instanceof ValidationException) {
            return ResponseHelper::error($e->getMessage(), 422, $e->getErrors());
        }

        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        return ResponseHelper::error($e->getMessage(), $statusCode);
    }

    protected function requireRole(array $allowedRoles): void
    {
        $authUser = $this->getAuthUser();
        $userRole = $authUser['role'] ?? 'cashier';

        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Forbidden: You do not have permission to access this resource'
            ]);
            exit;
        }
    }
}