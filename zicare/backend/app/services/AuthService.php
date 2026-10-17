<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Helpers\JwtHelper;
use App\Models\AuditLog;
use App\Models\PasswordResetToken;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Phalcon\Di\Di;

class AuthService
{
    private UserRepository $userRepository;
    private TotpService $totp;
    private array $config;
    private $logger;

    public function __construct()
    {
        $di = Di::getDefault();
        $this->userRepository = $di->getShared('userRepository');
        $this->totp = $di->getShared('totpService');
        $this->config = require BASE_PATH . '/app/config/config.php';
        $this->logger = $di->getShared('logger');
    }

    /**
     * Autentikasi tahap pertama.
     *
     * @param array{remember?: bool, ip?: ?string, user_agent?: ?string} $context
     */
    public function login(string $email, string $password, array $context = []): array
    {
        if (!empty($this->config['app']['maintenance'])) {
            throw new AppException('Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', 503);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            // Jangan ungkap apakah email terdaftar atau tidak.
            throw new AppException('Email atau password salah', 401);
        }

        $this->assertNotLocked($user);

        if (!password_verify($password, $user->password)) {
            $this->registerFailedAttempt($user);
        }

        // Upgrade legacy/weak hashes transparently on successful login.
        if (password_needs_rehash($user->password, PASSWORD_BCRYPT, ['cost' => 12])) {
            $user->password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        // Reset hitungan percobaan gagal setelah kredensial valid.
        $user->failed_login_attempts = 0;
        $user->locked_until = null;

        // Two-Factor Authentication: jangan keluarkan token final dulu.
        if ((int) $user->two_factor_enabled === 1 && !empty($user->two_factor_secret)) {
            $user->save();

            $challengeToken = JwtHelper::encode([
                'sub'  => $user->id,
                'type' => '2fa',
            ], (int) $this->config['security']['two_factor_ttl']);

            $this->logActivity((int) $user->id, 'Password verified, awaiting 2FA code');

            return [
                'requires_2fa'    => true,
                'challenge_token' => $challengeToken,
                'message'         => 'Masukkan kode 6 digit dari aplikasi authenticator Anda.',
            ];
        }

        return $this->finalizeLogin($user, (bool) ($context['remember'] ?? false), $context);
    }

    /**
     * Verifikasi kode TOTP dan terbitkan token final.
     */
    public function verifyTwoFactor(string $challengeToken, string $code, array $context = []): array
    {
        try {
            $decoded = JwtHelper::decode($challengeToken);
        } catch (\Throwable $e) {
            throw new AppException('Sesi verifikasi 2FA tidak valid atau kedaluwarsa', 401);
        }

        $payload = json_decode(json_encode($decoded), true);
        if (($payload['type'] ?? null) !== '2fa' || empty($payload['sub'])) {
            throw new AppException('Sesi verifikasi 2FA tidak valid', 401);
        }

        $user = $this->userRepository->findById((int) $payload['sub']);
        if (!$user || (int) $user->two_factor_enabled !== 1 || empty($user->two_factor_secret)) {
            throw new AppException('Two-factor authentication tidak aktif untuk akun ini', 400);
        }

        if (!$this->totp->verify((string) $user->two_factor_secret, $code)) {
            throw new AppException('Kode 2FA salah atau kedaluwarsa', 401);
        }

        return $this->finalizeLogin($user, (bool) ($context['remember'] ?? false), $context);
    }

    /**
     * Terbitkan token baru untuk memperpanjang sesi yang masih aktif.
     */
    public function refreshToken(array $authUser, bool $remember = false): array
    {
        $user = $this->userRepository->findById((int) ($authUser['sub'] ?? 0));
        if (!$user || (int) $user->is_active !== 1) {
            throw new AppException('Sesi tidak valid', 401);
        }

        $role = Role::findFirst($user->role_id);
        $expiry = $remember
            ? (int) $this->config['security']['remember_expiry']
            : (int) $this->config['jwt']['expiry'];

        $token = JwtHelper::encode($this->tokenPayload($user, $role), $expiry);

        return [
            'token'      => $token,
            'expires_in' => $expiry,
            'user'       => $this->userPayload($user, $role),
        ];
    }

    /**
     * Buat token reset password. Mengembalikan pesan generik agar tidak
     * membocorkan keberadaan akun. Saat APP_DEBUG=true, token dikembalikan
     * agar alur bisa diuji tanpa server email.
     */
    public function forgotPassword(string $email, ?string $ip = null): array
    {
        $generic = [
            'message' => 'Jika email terdaftar, kami telah membuat tautan reset password.',
        ];

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            // Tetap sukses (anti user-enumeration), tapi tidak ada token.
            return $generic;
        }

        // Batalkan token lama yang belum terpakai.
        foreach (PasswordResetToken::find([
            'conditions' => 'user_id = :uid: AND used_at IS NULL',
            'bind'       => ['uid' => $user->id],
        ]) as $old) {
            $old->used_at = date('Y-m-d H:i:s');
            $old->save();
        }

        $rawToken = bin2hex(random_bytes(32));
        $ttl = (int) $this->config['security']['password_reset_ttl'];

        $record = new PasswordResetToken();
        $record->user_id = (int) $user->id;
        $record->token_hash = hash('sha256', $rawToken);
        $record->expires_at = date('Y-m-d H:i:s', time() + $ttl);
        $record->requested_ip = $ip;

        if (!$record->save()) {
            throw new AppException('Gagal membuat token reset password');
        }

        // Tanpa mailer, tautan disimpan di log agar admin bisa meneruskannya.
        $resetUrl = rtrim((string) $this->config['app']['url'], '/') . '/login?reset_token=' . $rawToken;
        $this->logger->info("Password reset requested for {$user->email}: {$resetUrl}");

        $this->logActivity((int) $user->id, 'Requested password reset');

        $result = $generic + ['expires_in' => $ttl];

        // Hanya tampilkan token di mode debug (tanpa server email).
        if (!empty($this->config['app']['debug'])) {
            $result['reset_token'] = $rawToken;
            $result['reset_url'] = $resetUrl;
        }

        return $result;
    }

    /**
     * Selesaikan reset password dengan token mentah.
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $this->assertPasswordStrength($newPassword);

        $hash = hash('sha256', $token);
        $record = PasswordResetToken::findFirst([
            'conditions' => 'token_hash = :hash: AND used_at IS NULL AND expires_at > :now:',
            'bind'       => ['hash' => $hash, 'now' => date('Y-m-d H:i:s')],
        ]);

        if (!$record) {
            throw new AppException('Tautan reset tidak valid atau sudah kedaluwarsa', 422);
        }

        $user = $this->userRepository->findById((int) $record->user_id);
        if (!$user) {
            throw new AppException('Akun tidak ditemukan', 404);
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        $record->used_at = date('Y-m-d H:i:s');
        $record->save();

        $this->logActivity((int) $user->id, 'Password reset completed');
    }

    // ---------------------------------------------------------------- 2FA

    public function setupTwoFactor(array $authUser): array
    {
        $user = $this->requireUser($authUser);

        $secret = $this->totp->generateSecret();
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = 0;
        $user->save();

        $issuer = (string) $this->config['security']['totp_issuer'];

        return [
            'secret'    => $secret,
            'otpauth_url' => $this->totp->provisioningUri($secret, (string) $user->email, $issuer),
            'issuer'    => $issuer,
        ];
    }

    public function enableTwoFactor(array $authUser, string $code): void
    {
        $user = $this->requireUser($authUser);

        if (empty($user->two_factor_secret)) {
            throw new AppException('Jalankan proses setup 2FA terlebih dahulu', 400);
        }

        if (!$this->totp->verify((string) $user->two_factor_secret, $code)) {
            throw new AppException('Kode 2FA salah atau kedaluwarsa', 422);
        }

        $user->two_factor_enabled = 1;
        $user->save();
        $this->logActivity((int) $user->id, 'Enabled two-factor authentication');
    }

    public function disableTwoFactor(array $authUser, string $code): void
    {
        $user = $this->requireUser($authUser);

        $ok = !empty($user->two_factor_secret)
            ? $this->totp->verify((string) $user->two_factor_secret, $code)
            : false;

        if ($ok === false && !password_verify($code, $user->password)) {
            throw new AppException('Kode 2FA atau password salah', 422);
        }

        $user->two_factor_enabled = 0;
        $user->two_factor_secret = null;
        $user->save();
        $this->logActivity((int) $user->id, 'Disabled two-factor authentication');
    }

    public function getCurrentUser(array $authUser): array
    {
        $user = $this->requireUser($authUser);
        $role = Role::findFirst($user->role_id);

        return $this->userPayload($user, $role) + [
            'two_factor_enabled' => (int) $user->two_factor_enabled === 1,
            'last_login_at'      => $user->last_login_at,
        ];
    }

    // ------------------------------------------------------------ internals

    /**
     * @param array{remember?: bool, ip?: ?string, user_agent?: ?string} $context
     */
    private function finalizeLogin(User $user, bool $remember, array $context): array
    {
        $role = Role::findFirst($user->role_id);

        $expiry = $remember
            ? (int) $this->config['security']['remember_expiry']
            : (int) $this->config['jwt']['expiry'];

        $token = JwtHelper::encode($this->tokenPayload($user, $role), $expiry);

        // Info login sebelumnya (sebelum diperbarui) untuk ditampilkan ke user.
        $previousLogin = [
            'at'         => $user->last_login_at,
            'ip'         => $user->last_login_ip,
            'user_agent' => $user->last_login_user_agent,
            'first_time' => $user->last_login_at === null,
        ];

        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $context['ip'] ?? null;
        $user->last_login_user_agent = isset($context['user_agent'])
            ? mb_substr((string) $context['user_agent'], 0, 255)
            : null;
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        $this->logActivity((int) $user->id, 'User logged in');

        return [
            'token'       => $token,
            'expires_in'  => $expiry,
            'remember'    => $remember,
            'user'        => $this->userPayload($user, $role),
            'last_login'  => $previousLogin,
        ];
    }

    private function tokenPayload(User $user, ?Role $role): array
    {
        return [
            'sub'   => $user->id,
            'email' => $user->email,
            'name'  => $user->name,
            'role'  => $role?->name ?? 'cashier',
        ];
    }

    private function userPayload(User $user, ?Role $role): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $role?->name,
        ];
    }

    private function requireUser(array $authUser): User
    {
        $user = $this->userRepository->findById((int) ($authUser['sub'] ?? 0));
        if (!$user) {
            throw new AppException('User not found', 404);
        }
        return $user;
    }

    private function assertNotLocked(User $user): void
    {
        if (empty($user->locked_until)) {
            return;
        }

        $lockedUntil = strtotime((string) $user->locked_until);
        if ($lockedUntil !== false && $lockedUntil > time()) {
            $remaining = $lockedUntil - time();
            $minutes = (int) ceil($remaining / 60);
            throw new AppException(
                "Akun terkunci sementara karena terlalu banyak percobaan gagal. Coba lagi dalam {$minutes} menit.",
                423
            );
        }
    }

    private function registerFailedAttempt(User $user): void
    {
        $max = max(1, (int) $this->config['security']['max_login_attempts']);
        $attempts = (int) $user->failed_login_attempts + 1;
        $user->failed_login_attempts = $attempts;

        if ($attempts >= $max) {
            $lockMinutes = max(1, (int) $this->config['security']['lockout_minutes']);
            $user->locked_until = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
            $user->save();
            $this->logActivity((int) $user->id, 'Account locked after too many failed logins');

            throw new AppException(
                "Akun dikunci selama {$lockMinutes} menit karena terlalu banyak percobaan gagal.",
                423
            );
        }

        $user->save();

        $remaining = $max - $attempts;
        throw new AppException(
            "Email atau password salah. Percobaan tersisa: {$remaining}.",
            401,
            ['attempts_remaining' => $remaining]
        );
    }

    private function assertPasswordStrength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new AppException('Password minimal 8 karakter', 422);
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            throw new AppException('Password harus memuat huruf dan angka', 422);
        }
    }

    private function logActivity(int $userId, string $activity): void
    {
        try {
            $log = new AuditLog();
            $log->user_id = $userId;
            $log->activity = $activity;
            $log->save();
        } catch (\Throwable $e) {
            $this->logger->error('Audit logging failed: ' . $e->getMessage());
        }
    }
}
