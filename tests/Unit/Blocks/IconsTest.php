<?php
/**
 * @package BioLinkPro\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Blocks;

use BioLinkPro\Blocks\Icons;
use PHPUnit\Framework\TestCase;

final class IconsTest extends TestCase
{
    public function testUtilityNameListNotEmpty(): void
    {
        $names = Icons::utilityNames();
        self::assertNotEmpty($names);
        self::assertContains('link', $names);
        self::assertContains('arrow-right', $names);
    }

    public function testSocialPlatformListIncludesCorePlatforms(): void
    {
        $platforms = Icons::socialPlatforms();
        foreach (['instagram', 'tiktok', 'youtube', 'twitter', 'linkedin', 'github'] as $p) {
            self::assertContains($p, $platforms, "$p missing from social list");
        }
    }

    public function testUtilityReturnsSvgForKnown(): void
    {
        $svg = Icons::utility('link');
        self::assertStringStartsWith('<svg', $svg);
        self::assertStringContainsString('viewBox', $svg);
    }

    public function testUtilityReturnsEmptyForUnknown(): void
    {
        self::assertSame('', Icons::utility('nonexistent-icon'));
    }

    public function testSocialReturnsSvgForKnown(): void
    {
        $svg = Icons::social('github');
        self::assertStringStartsWith('<svg', $svg);
    }
}
