<?php

namespace App\Libraries;

/**
 * TOTP (RFC 6238) sederhana tanpa dependensi eksternal — kompatibel dengan
 * Google Authenticator / Authy (SHA1, 6 digit, period 30 detik).
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Buat secret base32 (default 20 byte = 160 bit). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32encode(random_bytes($bytes));
    }

    /**
     * Verifikasi kode TOTP terhadap secret. window=1 menerima kode periode
     * sebelum/berikutnya (toleransi jam yang sedikit beda).
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $key = self::base32decode($secret);
        if ($key === '') {
            return false;
        }
        $counter = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($key, $counter + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /** URI otpauth untuk QR (dipindai aplikasi authenticator). */
    public static function otpauthUri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    /** Kode HOTP 6-digit untuk counter tertentu. */
    private static function hotp(string $key, int $counter): string
    {
        // counter 8-byte big-endian
        $bin  = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $off  = ord($hash[19]) & 0xf;
        $num  = ((ord($hash[$off]) & 0x7f) << 24)
            | ((ord($hash[$off + 1]) & 0xff) << 16)
            | ((ord($hash[$off + 2]) & 0xff) << 8)
            | (ord($hash[$off + 3]) & 0xff);
        return str_pad((string) ($num % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function base32encode(string $data): string
    {
        if ($data === '') {
            return '';
        }
        $bits = '';
        foreach (str_split($data) as $ch) {
            $bits .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }
        return $out;
    }

    public static function base32decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        if ($b32 === '') {
            return '';
        }
        $bits = '';
        foreach (str_split($b32) as $ch) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $ch)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }
        return $out;
    }
}
