<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class AuthController extends BaseController
{
    public function login()
    {
        try {
            $body = $this->getJsonBody();
            $result = $this->di->getShared('authService')->login(
                (string) ($body['email'] ?? ''),
                (string) ($body['password'] ?? ''),
                $this->requestContext($body)
            );
            return ResponseHelper::success($result, 'Login successful');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/verify-2fa
     * Tahap kedua login ketika Two-Factor Authentication aktif.
     */
    public function verifyTwoFactor()
    {
        try {
            $body = $this->getJsonBody();
            $result = $this->di->getShared('authService')->verifyTwoFactor(
                (string) ($body['challenge_token'] ?? ''),
                (string) ($body['code'] ?? ''),
                $this->requestContext($body)
            );
            return ResponseHelper::success($result, 'Verifikasi 2FA berhasil');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/refresh
     * Perpanjang sesi yang masih aktif (dipakai peringatan session timeout).
     */
    public function refresh()
    {
        try {
            $body = $this->getJsonBody();
            $result = $this->di->getShared('authService')->refreshToken(
                $this->getAuthUser(),
                (bool) ($body['remember'] ?? false)
            );
            return ResponseHelper::success($result, 'Sesi diperpanjang');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/forgot-password
     */
    public function forgotPassword()
    {
        try {
            $body = $this->getJsonBody();
            $result = $this->di->getShared('authService')->forgotPassword(
                (string) ($body['email'] ?? ''),
                $this->request->getClientAddress()
            );
            return ResponseHelper::success($result, $result['message'] ?? 'Permintaan diterima');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/reset-password
     */
    public function resetPassword()
    {
        try {
            $body = $this->getJsonBody();
            $this->di->getShared('authService')->resetPassword(
                (string) ($body['token'] ?? ''),
                (string) ($body['password'] ?? '')
            );
            return ResponseHelper::success(null, 'Password berhasil diubah. Silakan masuk kembali.');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/2fa/setup — buat rahasia baru (belum aktif).
     */
    public function setupTwoFactor()
    {
        try {
            return ResponseHelper::success(
                $this->di->getShared('authService')->setupTwoFactor($this->getAuthUser()),
                'Pindai kode QR lalu masukkan kode untuk mengaktifkan'
            );
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/2fa/enable — konfirmasi kode lalu aktifkan.
     */
    public function enableTwoFactor()
    {
        try {
            $body = $this->getJsonBody();
            $this->di->getShared('authService')->enableTwoFactor($this->getAuthUser(), (string) ($body['code'] ?? ''));
            return ResponseHelper::success(['two_factor_enabled' => true], '2FA berhasil diaktifkan');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /auth/2fa/disable — matikan 2FA (butuh kode atau password).
     */
    public function disableTwoFactor()
    {
        try {
            $body = $this->getJsonBody();
            $this->di->getShared('authService')->disableTwoFactor($this->getAuthUser(), (string) ($body['code'] ?? ''));
            return ResponseHelper::success(['two_factor_enabled' => false], '2FA berhasil dimatikan');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /auth/status — publik, tanpa token. Dipakai halaman login untuk
     * memuat status sistem (mode perawatan & versi) sekaligus sebagai
     * loading skeleton.
     */
    public function status()
    {
        $config = require BASE_PATH . '/app/config/config.php';

        return ResponseHelper::success([
            'app_name'    => 'Zi.CARE POS',
            'version'     => $config['app']['version'],
            'environment' => $config['app']['env'],
            'maintenance' => (bool) $config['app']['maintenance'],
            'two_factor_available' => true,
        ]);
    }

    public function logout()
    {
        return ResponseHelper::success(null, 'Logout successful');
    }

    public function me()
    {
        try {
            $user = $this->di->getShared('authService')->getCurrentUser($this->getAuthUser());
            return ResponseHelper::success($user);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @return array{remember: bool, ip: ?string, user_agent: ?string}
     */
    private function requestContext(array $body): array
    {
        return [
            'remember'   => filter_var($body['remember'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'ip'         => $this->request->getClientAddress(),
            'user_agent' => $this->request->getUserAgent(),
        ];
    }
}
