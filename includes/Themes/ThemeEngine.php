<?php
/**
 * Theme registry + CSS emitter.
 *
 * @package BioLinkPro\Themes
 */

declare(strict_types=1);

namespace BioLinkPro\Themes;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

/**
 * Holds the catalog of theme presets and emits per-page CSS custom properties.
 *
 * Bundles 8 starter presets (4 minimal, 4 vibrant). Third parties can register
 * additional presets via the `biolink/themes/register` hook.
 */
final class ThemeEngine implements Bootable
{
    public const DEFAULT_SLUG = 'mono';

    /**
     * @var array<string, Preset>
     */
    private array $presets = [];

    private bool $dispatched = false;

    public function boot(): void
    {
        add_action('init', [$this, 'dispatchRegistration'], 7);
    }

    public function dispatchRegistration(): void
    {
        if ($this->dispatched) {
            return;
        }
        $this->dispatched = true;

        foreach ($this->builtIn() as $preset) {
            $this->register($preset);
        }

        /**
         * Register custom theme presets.
         *
         * @param ThemeEngine $engine
         */
        do_action('biolink/themes/register', $this);
    }

    public function register(Preset $preset): void
    {
        $this->presets[$preset->slug] = $preset;
    }

    /**
     * @return array<string, Preset>
     */
    public function all(): array
    {
        $this->dispatchRegistration();
        return $this->presets;
    }

    public function get(string $slug): Preset
    {
        $this->dispatchRegistration();
        return $this->presets[$slug] ?? $this->presets[self::DEFAULT_SLUG];
    }

    public function has(string $slug): bool
    {
        $this->dispatchRegistration();
        return isset($this->presets[$slug]);
    }

    /**
     * Emit the per-page `<style>` block. Includes:
     *   - theme CSS variables on `.bio-page-{slug}`
     *   - optional per-page background override (from $settings)
     *   - Google Font @import if the preset needs one
     *
     * @param array<string, mixed> $settings Page-level settings (from _biolink_data.settings).
     */
    public function renderStyleBlock(string $themeSlug, array $settings = [], string $selector = 'body.bio-body'): string
    {
        $preset = $this->get($themeSlug);

        $fontImport = '';
        if ($preset->googleFonts !== []) {
            $families = [];
            foreach ($preset->googleFonts as $family => $weights) {
                $families[] = str_replace(' ', '+', $family) . ':wght@' . $weights;
            }
            $href = 'https://fonts.googleapis.com/css2?' . implode('&', array_map(
                static fn(string $f): string => 'family=' . $f,
                $families
            )) . '&display=swap';
            $fontImport = sprintf("@import url('%s');\n", esc_url($href));
        }

        $vars = $preset->toCssVars();

        // Per-page background override
        $bg_override = '';
        $bg_type = (string) ($settings['bg_type'] ?? 'theme');
        if ($bg_type === 'color' && ! empty($settings['bg_color'])) {
            $bg_override = sprintf('--bio-bg:%s;', $this->sanitizeCssColor((string) $settings['bg_color']));
        } elseif ($bg_type === 'gradient') {
            $from  = $this->sanitizeCssColor((string) ($settings['bg_gradient_from'] ?? '#222'));
            $to    = $this->sanitizeCssColor((string) ($settings['bg_gradient_to'] ?? '#444'));
            $angle = (int) ($settings['bg_gradient_angle'] ?? 135);
            $bg_override = sprintf(
                '--bio-bg:linear-gradient(%ddeg, %s 0%%, %s 100%%);',
                max(0, min(360, $angle)),
                $from,
                $to
            );
        } elseif ($bg_type === 'image' && ! empty($settings['bg_image_id'])) {
            $url = wp_get_attachment_image_url((int) $settings['bg_image_id'], 'full');
            if (is_string($url)) {
                $overlay = max(0, min(100, (int) ($settings['bg_overlay'] ?? 0))) / 100;
                $bg_value = $overlay > 0
                    ? sprintf(
                        'linear-gradient(rgba(0,0,0,%.2f), rgba(0,0,0,%.2f)), url(%s) center/cover no-repeat',
                        $overlay,
                        $overlay,
                        esc_url_raw($url)
                    )
                    : sprintf('url(%s) center/cover no-repeat', esc_url_raw($url));
                $bg_override = sprintf('--bio-bg:%s;', $bg_value);
            }
        }

        $shape = sanitize_key((string) ($preset->buttonShape));
        $style = sanitize_key((string) ($preset->buttonStyle));

        // Per-page theme token overrides (theme editing) — narrow override layer
        // applied on top of preset tokens.
        $override_rules = '';
        if (! empty($settings['accent_color']) && is_string($settings['accent_color'])) {
            $c = $this->sanitizeCssColor($settings['accent_color']);
            if ($c !== '#000000' || $settings['accent_color'] === '#000000') {
                $override_rules .= sprintf('--bio-color-accent:%s;', $c);
            }
        }
        if (! empty($settings['accent_text_color']) && is_string($settings['accent_text_color'])) {
            $c = $this->sanitizeCssColor($settings['accent_text_color']);
            $override_rules .= sprintf('--bio-color-accent-text:%s;', $c);
        }
        if (! empty($settings['button_shape']) && in_array($settings['button_shape'], ['pill', 'rounded', 'square'], true)) {
            $shape = (string) $settings['button_shape'];
            $radius = match ($shape) {
                'pill'    => '999px',
                'square'  => '4px',
                'rounded' => '14px',
                default   => '999px',
            };
            $override_rules .= sprintf('--bio-button-radius:%s;', $radius);
        }
        if (! empty($settings['button_style']) && in_array($settings['button_style'], ['filled', 'outline', 'glass'], true)) {
            $style = (string) $settings['button_style'];
        }

        // Declare ALL theme tokens on the scoping selector so they cascade to
        // everything inside. Defaults to body.bio-body for full-page renders.
        // Shortcodes pass a `.bio-embed-{id}` selector so each embedded page
        // is independently themed inside the host page.
        $background_rule = $selector === 'body.bio-body'
            ? sprintf('%s{background:var(--bio-bg);background-attachment:fixed;color:var(--bio-color-text);font-family:var(--bio-font-stack);}', $selector)
            : sprintf('%s{background:var(--bio-bg);color:var(--bio-color-text);font-family:var(--bio-font-stack);}', $selector);

        $style_id = $selector === 'body.bio-body'
            ? 'biolink-theme'
            : 'biolink-theme-' . substr(md5($selector), 0, 8);

        return sprintf(
            "<style id=\"%s\">\n%s%s{%s%s%s--bio-button-shape:%s;--bio-button-style:%s;}\n%s\n</style>",
            esc_attr($style_id),
            $fontImport,
            $selector,
            $vars,
            $bg_override,
            $override_rules,
            esc_attr($shape),
            esc_attr($style),
            $background_rule
        );
    }

    private function sanitizeCssColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $value)) {
            return $value;
        }
        return '#000000';
    }

    /**
     * @return list<Preset>
     */
    private function builtIn(): array
    {
        return [
            // --- Minimal / professional ---
            new Preset(
                slug:             'mono',
                label:            __('Mono', 'biolink-pro'),
                description:      __('Clean white background, ink text, subtle shadows. Classic.', 'biolink-pro'),
                background:       ['type' => 'color', 'value' => '#fafafa'],
                textColor:        '#0f172a',
                mutedColor:       '#64748b',
                accentColor:      '#0f172a',
                accentText:       '#ffffff',
                surfaceColor:     '#ffffff',
                borderColor:      '#e2e8f0',
                fontStack:        "-apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif",
                headingFontStack: "-apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif",
                buttonShape:      'rounded',
                buttonStyle:      'filled',
                shadow:           '0 1px 3px rgba(15,23,42,0.06)',
                swatch:           '#fafafa',
            ),
            new Preset(
                slug:             'glass',
                label:            __('Glass', 'biolink-pro'),
                description:      __('Frosted-glass cards on a soft gradient. Modern.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'linear-gradient(135deg, #e0e7ff 0%, #f5d0fe 100%)'],
                textColor:        '#1e1b4b',
                mutedColor:       '#5b21b6',
                accentColor:      '#6366f1',
                accentText:       '#ffffff',
                surfaceColor:     'rgba(255,255,255,0.55)',
                borderColor:      'rgba(255,255,255,0.6)',
                fontStack:        "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                headingFontStack: "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                buttonShape:      'pill',
                buttonStyle:      'glass',
                shadow:           '0 8px 32px rgba(99,102,241,0.18)',
                swatch:           'linear-gradient(135deg, #e0e7ff, #f5d0fe)',
                googleFonts:      ['Inter' => '400;500;600;700'],
            ),
            new Preset(
                slug:             'forest',
                label:            __('Forest', 'biolink-pro'),
                description:      __('Deep forest green with warm gold accents. Earthy.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'linear-gradient(180deg, #064e3b 0%, #022c22 100%)'],
                textColor:        '#ecfdf5',
                mutedColor:       '#a7f3d0',
                accentColor:      '#fbbf24',
                accentText:       '#064e3b',
                surfaceColor:     'rgba(255,255,255,0.06)',
                borderColor:      'rgba(255,255,255,0.12)',
                fontStack:        "'Inter', -apple-system, sans-serif",
                headingFontStack: "'Playfair Display', Georgia, serif",
                buttonShape:      'rounded',
                buttonStyle:      'filled',
                shadow:           '0 4px 24px rgba(0,0,0,0.3)',
                swatch:           'linear-gradient(180deg, #064e3b, #022c22)',
                googleFonts:      ['Inter' => '400;500;600', 'Playfair+Display' => '600;700'],
            ),
            new Preset(
                slug:             'midnight',
                label:            __('Midnight', 'biolink-pro'),
                description:      __('Inky background, white text, electric accent. Pro look.', 'biolink-pro'),
                background:       ['type' => 'color', 'value' => '#0b1020'],
                textColor:        '#e5e7eb',
                mutedColor:       '#94a3b8',
                accentColor:      '#22d3ee',
                accentText:       '#0b1020',
                surfaceColor:     'rgba(255,255,255,0.04)',
                borderColor:      'rgba(255,255,255,0.1)',
                fontStack:        "'Inter', -apple-system, sans-serif",
                headingFontStack: "'Inter', -apple-system, sans-serif",
                buttonShape:      'rounded',
                buttonStyle:      'outline',
                shadow:           '0 0 0 1px rgba(34,211,238,0.3)',
                swatch:           '#0b1020',
                googleFonts:      ['Inter' => '400;500;600;700'],
            ),

            // --- Vibrant ---
            new Preset(
                slug:             'neon',
                label:            __('Neon', 'biolink-pro'),
                description:      __('Hot pink glow, dark canvas. Creator-mode.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'radial-gradient(ellipse at top, #ec4899 0%, #831843 45%, #18181b 100%)'],
                textColor:        '#fdf2f8',
                mutedColor:       '#f9a8d4',
                accentColor:      '#fbcfe8',
                accentText:       '#831843',
                surfaceColor:     'rgba(0,0,0,0.35)',
                borderColor:      'rgba(252,231,243,0.25)',
                fontStack:        "'Space Grotesk', 'Inter', sans-serif",
                headingFontStack: "'Space Grotesk', 'Inter', sans-serif",
                buttonShape:      'pill',
                buttonStyle:      'filled',
                shadow:           '0 10px 40px rgba(236,72,153,0.4)',
                swatch:           'radial-gradient(ellipse at top, #ec4899, #18181b)',
                googleFonts:      ['Space+Grotesk' => '400;500;600;700'],
            ),
            new Preset(
                slug:             'sunset',
                label:            __('Sunset', 'biolink-pro'),
                description:      __('Warm coral to amber gradient. Inviting.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'linear-gradient(160deg, #ff7e5f 0%, #feb47b 50%, #fbbf24 100%)'],
                textColor:        '#451a03',
                mutedColor:       '#92400e',
                accentColor:      '#7c2d12',
                accentText:       '#fef3c7',
                surfaceColor:     'rgba(255,255,255,0.65)',
                borderColor:      'rgba(124,45,18,0.2)',
                fontStack:        "'Inter', -apple-system, sans-serif",
                headingFontStack: "'Inter', -apple-system, sans-serif",
                buttonShape:      'pill',
                buttonStyle:      'filled',
                shadow:           '0 8px 28px rgba(124,45,18,0.25)',
                swatch:           'linear-gradient(160deg, #ff7e5f, #feb47b, #fbbf24)',
                googleFonts:      ['Inter' => '400;500;600;700'],
            ),
            new Preset(
                slug:             'aurora',
                label:            __('Aurora', 'biolink-pro'),
                description:      __('Cyan-to-purple aurora gradient. Energetic.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'linear-gradient(135deg, #06b6d4 0%, #6366f1 50%, #a855f7 100%)'],
                textColor:        '#ffffff',
                mutedColor:       '#e9d5ff',
                accentColor:      '#fef08a',
                accentText:       '#312e81',
                surfaceColor:     'rgba(255,255,255,0.12)',
                borderColor:      'rgba(255,255,255,0.25)',
                fontStack:        "'Inter', -apple-system, sans-serif",
                headingFontStack: "'Inter', -apple-system, sans-serif",
                buttonShape:      'pill',
                buttonStyle:      'glass',
                shadow:           '0 12px 36px rgba(0,0,0,0.2)',
                swatch:           'linear-gradient(135deg, #06b6d4, #6366f1, #a855f7)',
                googleFonts:      ['Inter' => '400;500;600;700'],
            ),
            new Preset(
                slug:             'sky',
                label:            __('Sky', 'biolink-pro'),
                description:      __('Soft sky-blue gradient with white cards. Friendly.', 'biolink-pro'),
                background:       ['type' => 'gradient', 'value' => 'linear-gradient(180deg, #bae6fd 0%, #e0f2fe 100%)'],
                textColor:        '#0c4a6e',
                mutedColor:       '#0369a1',
                accentColor:      '#0284c7',
                accentText:       '#ffffff',
                surfaceColor:     '#ffffff',
                borderColor:      'rgba(2,132,199,0.18)',
                fontStack:        "'Inter', -apple-system, sans-serif",
                headingFontStack: "'Inter', -apple-system, sans-serif",
                buttonShape:      'rounded',
                buttonStyle:      'filled',
                shadow:           '0 4px 16px rgba(2,132,199,0.18)',
                swatch:           'linear-gradient(180deg, #bae6fd, #e0f2fe)',
                googleFonts:      ['Inter' => '400;500;600;700'],
            ),
        ];
    }
}
