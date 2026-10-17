<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Implementasi TOTP (RFC 6238) yang self-contained — tanpa dependency
 * eksternal — sehingga kompatibel dengan Google Authenticator, Authy,
 * Microsoft Authenticator, dan aplikasi sejenis.
 *
 * Rahasia disimpan sebagai base32 (standar authenticator), kode 6 digit,
 * langkah waktu 30 detik, algoritma HMAC-SHA1.
 */
class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    /**
     * Buat rahasia acak baru (default 20 byte = 160 bit, standar RFC 4226).
     */
    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    /**
     * Hitung kode TOTP untuk sebuah rahasia pada waktu tertentu.
     */
    public function code(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = (int) floor($timestamp / self::PERIOD);

        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $key = $this->base32Decode($secret);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $value, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verifikasi kode dengan toleransi ±$window langkah (default ±1 =
     * ±30 detik) untuk mengakomodasi perbedaan jam.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            $candidate = $this->code($secret, $now + ($i * self::PERIOD));
            if (hash_equals($candidate, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * URI otpauth:// standar untuk provisioning (dapat diubah menjadi QR code
     * oleh aplikasi authenticator).
     */
    public function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);

        return 'otpauth://totp/' . $label . '?' . $params;
    }

    private function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret) ?? '');
        if ($secret === '') {
            return '';
        }

        $binary = '';
        foreach (str_split($secret) as $char) {
            $index = strpos(self::BASE32_ALPHABET, $char);
            if ($index === false) {
                continue;
            }
            $binary .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $decoded .= chr(bindec($byte));
        }

        return $decoded;
    }
}
