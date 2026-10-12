<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AppException;
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
        if ($e instanceof ValidationException) {
            return ResponseHelper::error($e->getMessage(), 422, $e->getErrors());
        }

        // Known/expected application errors expose their message to the caller.
        if ($e instanceof AppException) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }

        // Unexpected errors: log full details, never leak internals to clients.
        error_log('=== CRITICAL ERROR ===');
        error_log('Pesan: ' . $e->getMessage());
        error_log('File: ' . $e->getFile() . ' (Baris: ' . $e->getLine() . ')');
        error_log('=========================');

        $config = require BASE_PATH . '/app/config/config.php';
        $message = ($config['app']['debug'] ?? false) ? $e->getMessage() : 'Internal server error';

        return ResponseHelper::error($message, 500);
    }

    protected function requireRole(array $allowedRoles): void
    {
        $authUser = $this->getAuthUser();
        $userRole = $authUser['role'] ?? null;

        if ($userRole === null || !in_array($userRole, $allowedRoles, true)) {
            // Throw instead of echoing + exit so the response goes through
            // Phalcon's pipeline (keeps CORS headers and status formatting).
            throw new AppException('Forbidden: You do not have permission to access this resource', 403);
        }
    }

    /**
     * Shared validation for a single payment payload.
     *
     * @return array{valid: bool, message?: string}
     */
    protected function validatePaymentData(array $data): array
    {
        if (empty($data['payment_method'])) {
            return ['valid' => false, 'message' => 'Payment method is required'];
        }

        if (!in_array($data['payment_method'], ['cash', 'qris', 'transfer', 'ewallet'], true)) {
            return ['valid' => false, 'message' => 'Invalid payment method'];
        }

        if (!isset($data['amount']) || $data['amount'] <= 0) {
            return ['valid' => false, 'message' => 'Payment amount must be greater than 0'];
        }

        // Non-cash payments must identify the provider and its reference.
        if ($data['payment_method'] !== 'cash') {
            if (empty($data['provider'])) {
                return ['valid' => false, 'message' => 'Provider is required for non-cash payments'];
            }
            if (empty($data['payment_reference'])) {
                return ['valid' => false, 'message' => 'Payment reference is required for non-cash payments'];
            }
        }

        return ['valid' => true];
    }
}