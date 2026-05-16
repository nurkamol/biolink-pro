<?php
/**
 * @package BioLinkPro\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Templates;

use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Templates\TemplateLibrary;
use PHPUnit\Framework\TestCase;

final class TemplateLibraryTest extends TestCase
{
    public function testAllReturnsBundledTemplates(): void
    {
        $lib = new TemplateLibrary(new PageRepository());
        $all = $lib->all();
        $slugs = array_column($all, 'slug');
        self::assertContains('creator', $slugs);
        self::assertContains('agency', $slugs);
        self::assertContains('musician', $slugs);
        self::assertContains('restaurant', $slugs);
        self::assertContains('photographer', $slugs);
        self::assertContains('developer', $slugs);
    }

    public function testGetByKnownSlug(): void
    {
        $lib = new TemplateLibrary(new PageRepository());
        $tpl = $lib->get('developer');
        self::assertIsArray($tpl);
        self::assertSame('developer', $tpl['slug']);
        self::assertSame('Developer', $tpl['label']);
        self::assertNotEmpty($tpl['blocks']);
    }

    public function testGetUnknownReturnsNull(): void
    {
        self::assertNull(( new TemplateLibrary(new PageRepository()) )->get('nonexistent'));
    }

    public function testAllTemplatesHaveRequiredKeys(): void
    {
        $lib = new TemplateLibrary(new PageRepository());
        foreach ($lib->all() as $tpl) {
            self::assertArrayHasKey('slug', $tpl, 'slug missing');
            self::assertArrayHasKey('label', $tpl, 'label missing for ' . ($tpl['slug'] ?? '?'));
            self::assertArrayHasKey('theme', $tpl, 'theme missing for ' . $tpl['slug']);
            self::assertArrayHasKey('blocks', $tpl, 'blocks missing for ' . $tpl['slug']);
            self::assertIsArray($tpl['blocks']);
        }
    }
}
