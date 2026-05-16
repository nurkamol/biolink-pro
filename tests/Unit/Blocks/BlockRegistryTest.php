<?php
/**
 * @package BioLinkPro\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Blocks;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\BlockRegistry;
use PHPUnit\Framework\TestCase;

final class BlockRegistryTest extends TestCase
{
    public function testRegisterAndRetrieve(): void
    {
        $registry = new BlockRegistry();
        $block    = $this->makeBlock('demo');
        $registry->register($block);

        self::assertTrue($registry->has('demo'));
        self::assertSame($block, $registry->get('demo'));
        self::assertNull($registry->get('missing'));
    }

    public function testUnregisterRemovesBlock(): void
    {
        $registry = new BlockRegistry();
        $registry->register($this->makeBlock('demo'));
        $registry->unregister('demo');
        self::assertFalse($registry->has('demo'));
    }

    public function testEmptySlugIsRejected(): void
    {
        $registry = new BlockRegistry();
        $registry->register($this->makeBlock(''));
        self::assertSame([], $registry->all());
    }

    private function makeBlock(string $slug): AbstractBlock
    {
        return new class ($slug) extends AbstractBlock {
            public function __construct(private readonly string $slug)
            {
            }
            public function slug(): string
            {
                return $this->slug;
            }
            public function label(): string
            {
                return 'Demo';
            }
            public function icon(): string
            {
                return 'demo';
            }
            public function schema(): array
            {
                return [];
            }
            public function render(array $data): string
            {
                return '';
            }
        };
    }
}
