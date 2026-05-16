<?php
/**
 * Immutable theme preset value object.
 *
 * @package BioLinkPro\Themes
 */

declare(strict_types=1);

namespace BioLinkPro\Themes;

defined('ABSPATH') || exit;

/**
 * Carries the design tokens for one preset.
 *
 * Tokens map 1-to-1 to CSS custom properties on `.bio-page`, so a theme
 * change at runtime is a single `<style>` block swap.
 */
final readonly class Preset
{
    /**
     * @param array{type: string, value: string} $background      type=color|gradient|image; value is CSS bg-image|color
     * @param array<string, string>              $googleFonts     map of family => weights string for Google Fonts loader
     */
    public function __construct(
        public string $slug,
        public string $label,
        public string $description,
        public array $background,
        public string $textColor,
        public string $mutedColor,
        public string $accentColor,
        public string $accentText,
        public string $surfaceColor,
        public string $borderColor,
        public string $fontStack,
        public string $headingFontStack,
        public string $buttonShape,
        public string $buttonStyle,
        public string $shadow,
        public string $swatch,
        public array $googleFonts = []
    ) {
    }

    /**
     * Render the design tokens as CSS custom properties on `.bio-page`.
     */
    public function toCssVars(): string
    {
        $bg = match ($this->background['type']) {
            'gradient' => $this->background['value'],
            'image'    => $this->background['value'],
            default    => $this->background['value'],
        };

        $rules = [
            '--bio-bg'              => $bg,
            '--bio-color-text'      => $this->textColor,
            '--bio-color-muted'     => $this->mutedColor,
            '--bio-color-accent'    => $this->accentColor,
            '--bio-color-accent-text' => $this->accentText,
            '--bio-color-surface'   => $this->surfaceColor,
            '--bio-color-border'    => $this->borderColor,
            '--bio-font-stack'      => $this->fontStack,
            '--bio-heading-stack'   => $this->headingFontStack,
            '--bio-button-radius'   => $this->buttonRadius(),
            '--bio-shadow'          => $this->shadow,
        ];

        $out = '';
        foreach ($rules as $name => $val) {
            $out .= sprintf("%s:%s;", $name, $val);
        }
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): string|array
    {
        return [
            'slug'        => $this->slug,
            'label'       => $this->label,
            'description' => $this->description,
            'background'  => $this->background,
            'swatch'      => $this->swatch,
            'tokens'      => [
                'text'        => $this->textColor,
                'muted'       => $this->mutedColor,
                'accent'      => $this->accentColor,
                'accentText'  => $this->accentText,
                'surface'     => $this->surfaceColor,
                'border'      => $this->borderColor,
                'buttonShape' => $this->buttonShape,
                'buttonStyle' => $this->buttonStyle,
            ],
        ];
    }

    private function buttonRadius(): string
    {
        return match ($this->buttonShape) {
            'pill'    => '999px',
            'square'  => '4px',
            'rounded' => '14px',
            default   => '999px',
        };
    }
}
