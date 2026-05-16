<?php
/**
 * @package BioLinkPro\Tests\Unit\Themes
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Themes;

use BioLinkPro\Themes\Preset;
use PHPUnit\Framework\TestCase;

final class PresetTest extends TestCase
{
    private function makePreset(string $shape = 'pill'): Preset
    {
        return new Preset(
            slug:             'demo',
            label:            'Demo',
            description:      'A demo preset',
            background:       ['type' => 'color', 'value' => '#000000'],
            textColor:        '#ffffff',
            mutedColor:       '#cccccc',
            accentColor:      '#ff0066',
            accentText:       '#000000',
            surfaceColor:     '#222222',
            borderColor:      '#444444',
            fontStack:        "'Inter', sans-serif",
            headingFontStack: "'Inter', sans-serif",
            buttonShape:      $shape,
            buttonStyle:      'filled',
            shadow:           '0 1px 2px rgba(0,0,0,0.1)',
            swatch:           '#000000',
        );
    }

    public function testToCssVarsEmitsAllTokens(): void
    {
        $css = $this->makePreset()->toCssVars();
        self::assertStringContainsString('--bio-bg:#000000;', $css);
        self::assertStringContainsString('--bio-color-text:#ffffff;', $css);
        self::assertStringContainsString('--bio-color-accent:#ff0066;', $css);
        self::assertStringContainsString('--bio-color-accent-text:#000000;', $css);
        self::assertStringContainsString('--bio-font-stack:', $css);
        self::assertStringContainsString('--bio-button-radius:999px;', $css);
    }

    public function testButtonShapeMapsToRadius(): void
    {
        self::assertStringContainsString('--bio-button-radius:999px;', $this->makePreset('pill')->toCssVars());
        self::assertStringContainsString('--bio-button-radius:14px;', $this->makePreset('rounded')->toCssVars());
        self::assertStringContainsString('--bio-button-radius:4px;', $this->makePreset('square')->toCssVars());
    }

    public function testToApiArrayShape(): void
    {
        $arr = $this->makePreset()->toApiArray();
        self::assertIsArray($arr);
        self::assertSame('demo', $arr['slug']);
        self::assertSame('Demo', $arr['label']);
        self::assertArrayHasKey('background', $arr);
        self::assertArrayHasKey('swatch', $arr);
        self::assertArrayHasKey('tokens', $arr);
        self::assertSame('#ff0066', $arr['tokens']['accent']);
        self::assertSame('pill', $arr['tokens']['buttonShape']);
        self::assertSame('filled', $arr['tokens']['buttonStyle']);
    }
}
