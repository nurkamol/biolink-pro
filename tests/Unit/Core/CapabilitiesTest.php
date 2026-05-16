<?php
/**
 * @package BioLinkPro\Tests\Unit\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Core;

use BioLinkPro\Core\Capabilities;
use PHPUnit\Framework\TestCase;

final class CapabilitiesTest extends TestCase
{
    public function testAllReturnsExpectedCapabilityList(): void
    {
        $expected = [
            'biolink_manage_pages',
            'biolink_publish_pages',
            'biolink_manage_themes',
            'biolink_view_analytics',
            'biolink_manage_integrations',
            'biolink_use_ai',
        ];

        self::assertSame($expected, Capabilities::all());
    }
}
