<?php
/**
 * @package BioLinkPro\Tests\Unit\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Core;

use BioLinkPro\Core\Plugin;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        Plugin::resetForTests();
    }

    public function testInstanceReturnsSameSingleton(): void
    {
        $a = Plugin::instance();
        $b = Plugin::instance();
        self::assertSame($a, $b);
    }

    public function testRegisterAndGetService(): void
    {
        $plugin = Plugin::instance();
        $service = new stdClass();

        $plugin->register('demo', $service);

        self::assertTrue($plugin->has('demo'));
        self::assertSame($service, $plugin->get('demo'));
    }

    public function testGetReturnsNullForUnknownService(): void
    {
        self::assertNull(Plugin::instance()->get('nope'));
    }
}
