<?php
/**
 * Symmetric encrypt/decrypt for at-rest secrets (API keys, webhook signatures).
 *
 * Uses libsodium when available; falls back to base64 (with a clear "encrypted"
 * marker so we never confuse plain values with encrypted ones).
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

defined('ABSPATH') || exit;

final class Crypto
{
    private const PREFIX_SODIUM = 'biolink-pro:v1:';
    private const PREFIX_PLAIN  = 'biolink-pro:plain:';

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }
        if (function_exists('sodium_crypto_secretbox')) {
            $key   = $this->deriveKey();
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $box   = sodium_crypto_secretbox($plaintext, $nonce, $key);
            return self::PREFIX_SODIUM . base64_encode($nonce . $box);
        }
        return self::PREFIX_PLAIN . base64_encode($plaintext);
    }

    public function decrypt(string $stored): string
    {
        if ($stored === '') {
            return '';
        }
        if (str_starts_with($stored, self::PREFIX_SODIUM) && function_exists('sodium_crypto_secretbox_open')) {
            $payload = base64_decode(substr($stored, strlen(self::PREFIX_SODIUM)), true);
            if (! is_string($payload) || strlen($payload) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                return '';
            }
            $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $box   = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $out   = sodium_crypto_secretbox_open($box, $nonce, $this->deriveKey());
            return is_string($out) ? $out : '';
        }
        if (str_starts_with($stored, self::PREFIX_PLAIN)) {
            $out = base64_decode(substr($stored, strlen(self::PREFIX_PLAIN)), true);
            return is_string($out) ? $out : '';
        }
        // Legacy / unprefixed values are returned as-is to allow gradual upgrade.
        return $stored;
    }

    public static function mask(string $stored, int $visible = 4): string
    {
        $decrypted = ( new self() )->decrypt($stored);
        if ($decrypted === '') {
            return '';
        }
        $len = mb_strlen($decrypted);
        if ($len <= $visible) {
            return str_repeat('•', $len);
        }
        return str_repeat('•', $len - $visible) . mb_substr($decrypted, -$visible);
    }

    private function deriveKey(): string
    {
        // 32 bytes of key derived from AUTH_KEY constant.
        $material = defined('AUTH_KEY') ? (string) AUTH_KEY : 'biolink-pro-fallback-key';
        return substr(hash('sha256', 'biolink-pro:' . $material, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
