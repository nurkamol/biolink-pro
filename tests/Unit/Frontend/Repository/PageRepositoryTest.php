<?php
/**
 * @package BioLinkPro\Tests\Unit\Frontend\Repository
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Frontend\Repository;

use BioLinkPro\Frontend\Repository\PageRepository;
use PHPUnit\Framework\TestCase;

final class PageRepositoryTest extends TestCase
{
    public function testNormalizeFillsInDefaults(): void
    {
        $normalized = PageRepository::normalize([]);
        self::assertSame('minimal', $normalized['theme']);
        self::assertSame([], $normalized['settings']);
        self::assertSame([], $normalized['blocks']);
        self::assertSame([], $normalized['seo']);
    }

    public function testNormalizeSanitizesThemeKey(): void
    {
        // sanitize_key lowercases and strips chars outside [a-z0-9_-]; spaces are removed, not replaced.
        $normalized = PageRepository::normalize(['theme' => 'Dark-Mode!']);
        self::assertSame('dark-mode', $normalized['theme']);
    }

    public function testNormalizeBlockAssignsUuidWhenMissing(): void
    {
        if (! function_exists('wp_generate_uuid4')) {
            self::markTestSkipped('wp_generate_uuid4 unavailable without WP stubs.');
        }
        $block = PageRepository::normalizeBlock(['type' => 'link', 'data' => ['url' => 'https://example.test']]);
        self::assertSame('link', $block['type']);
        self::assertTrue(PageRepository::isValidUuid($block['uuid']));
        self::assertSame(['url' => 'https://example.test'], $block['data']);
    }

    public function testNormalizeBlockPreservesValidUuid(): void
    {
        $uuid  = '11111111-2222-3333-4444-555555555555';
        $block = PageRepository::normalizeBlock(['uuid' => $uuid, 'type' => 'button']);
        self::assertSame($uuid, $block['uuid']);
    }

    public function testIsValidUuidRejectsGarbage(): void
    {
        self::assertFalse(PageRepository::isValidUuid('not-a-uuid'));
        self::assertTrue(PageRepository::isValidUuid('aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'));
    }
}
