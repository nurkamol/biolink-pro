<?php
/**
 * @package BioLinkPro\Tests\Unit\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Core;

use BioLinkPro\Core\Crypto;
use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase
{
    public function testEncryptThenDecryptRoundtrip(): void
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            self::markTestSkipped('libsodium unavailable.');
        }
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-key-12345');
        }

        $crypto    = new Crypto();
        $plaintext = 'sk-test-1234567890abcdef';

        $encrypted = $crypto->encrypt($plaintext);
        self::assertStringStartsWith('biolink-pro:v1:', $encrypted);
        self::assertNotEquals($plaintext, $encrypted);

        $decrypted = $crypto->decrypt($encrypted);
        self::assertSame($plaintext, $decrypted);
    }

    public function testEncryptEmptyStringYieldsEmpty(): void
    {
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-key');
        }
        self::assertSame('', ( new Crypto() )->encrypt(''));
    }

    public function testDecryptEmptyStringYieldsEmpty(): void
    {
        self::assertSame('', ( new Crypto() )->decrypt(''));
    }

    public function testDecryptUnprefixedReturnsAsIs(): void
    {
        // Legacy / unprefixed values are returned unchanged for gradual upgrade.
        self::assertSame('legacy-value', ( new Crypto() )->decrypt('legacy-value'));
    }

    public function testMaskShortValueAllDots(): void
    {
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-key');
        }
        $encrypted = ( new Crypto() )->encrypt('abc');
        $masked    = Crypto::mask($encrypted, 4);
        // 3-char plaintext, visible=4 → entire string is dots
        self::assertSame('•••', $masked);
    }

    public function testMaskShowsLast4(): void
    {
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-key');
        }
        $encrypted = ( new Crypto() )->encrypt('sk-test-1234567890ABCDEF');
        $masked    = Crypto::mask($encrypted, 4);
        self::assertStringEndsWith('CDEF', $masked);
        self::assertGreaterThan(20, strlen($masked));
    }

    public function testTamperedCiphertextDecryptsToEmpty(): void
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            self::markTestSkipped('libsodium unavailable.');
        }
        if (! defined('AUTH_KEY')) {
            define('AUTH_KEY', 'unit-test-key');
        }
        $crypto    = new Crypto();
        $encrypted = $crypto->encrypt('original');
        // Flip one base64 char to corrupt the MAC
        $tampered  = substr($encrypted, 0, -2) . 'AA';
        self::assertSame('', $crypto->decrypt($tampered));
    }
}
