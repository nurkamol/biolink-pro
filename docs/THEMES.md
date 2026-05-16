# Theme engine

## Concept

A **theme** is a named set of CSS custom property values + an optional stylesheet override. Themes are stored as PHP arrays (built-ins) or DB rows (user-created). Rendering injects `--bio-*` variables into a `<style>` tag on the bio page root.

## Built-in presets

| Slug | Description |
|---|---|
| `minimal` | Clean white, subtle shadows, system font |
| `dark` | OLED dark, high contrast, rounded corners |
| `neon` | Black bg, neon pink/cyan accents, glow effects |
| `creator` | Warm gradient, large avatar, bold sans |
| `glassmorphism` | Frosted glass cards over gradient bg |
| `professional` | Muted blues, serif headings, structured |
| `retro` | 80s gradient, monospace, scanline overlay |
| `gradient` | Animated gradient bg, white text, rounded |
| `monochrome` | Black-on-white, no color, max readability |

## Token catalog

Every theme defines these tokens (any can be overridden by user):

```css
:root {
  /* Surfaces */
  --bio-color-bg: #ffffff;
  --bio-color-surface: #f8f8f8;
  --bio-color-surface-elevated: #ffffff;

  /* Text */
  --bio-color-text: #111111;
  --bio-color-text-muted: #666666;
  --bio-color-link: #0066ff;

  /* Brand */
  --bio-color-primary: #0066ff;
  --bio-color-primary-contrast: #ffffff;
  --bio-color-accent: #ff3366;

  /* Borders + shadow */
  --bio-color-border: rgba(0,0,0,0.08);
  --bio-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --bio-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
  --bio-shadow-lg: 0 12px 32px rgba(0,0,0,0.12);

  /* Radius */
  --bio-radius-sm: 6px;
  --bio-radius-md: 12px;
  --bio-radius-lg: 20px;
  --bio-radius-full: 9999px;

  /* Spacing scale (4px base) */
  --bio-space-1: 4px;
  --bio-space-2: 8px;
  --bio-space-3: 12px;
  --bio-space-4: 16px;
  --bio-space-6: 24px;
  --bio-space-8: 32px;
  --bio-space-12: 48px;
  --bio-space-16: 64px;

  /* Typography */
  --bio-font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, system-ui, sans-serif;
  --bio-font-serif: ui-serif, Georgia, serif;
  --bio-font-mono: ui-monospace, "SF Mono", Menlo, monospace;
  --bio-font-display: var(--bio-font-sans);
  --bio-text-base: 16px;
  --bio-text-scale: 1.2;

  /* Motion */
  --bio-motion-fast: 120ms;
  --bio-motion-normal: 200ms;
  --bio-motion-slow: 400ms;
  --bio-easing: cubic-bezier(0.4, 0, 0.2, 1);

  /* Layout */
  --bio-container-width: 480px;
  --bio-page-padding: var(--bio-space-6);
}
```

## Theme definition shape (PHP)

```php
return [
    'slug'        => 'neon',
    'label'       => __('Neon', 'biolink-pro'),
    'preview'     => BIOLINK_URL . 'themes/previews/neon.jpg',
    'tokens'      => [
        '--bio-color-bg'      => '#0a0a0a',
        '--bio-color-text'    => '#ffffff',
        '--bio-color-primary' => '#ff10f0',
        '--bio-color-accent'  => '#00fff0',
        '--bio-radius-md'     => '4px',
        '--bio-font-display'  => '"VT323", monospace',
    ],
    'stylesheet'  => 'themes/neon/neon.css',  // optional extra CSS
    'fonts'       => [
        ['family' => 'VT323', 'source' => 'google', 'weights' => [400]],
    ],
    'backgrounds' => [
        'type' => 'gradient-animated',
        'value' => 'linear-gradient(45deg, #ff10f0, #00fff0)',
    ],
];
```

## Custom CSS

User-provided CSS is allowed but sanitized through `Themes\CssSanitizer`:
- Rejects `@import`, `expression()`, `javascript:` URLs
- Strips comments containing CDATA or HTML
- Whitelists property names against a known-safe list
- Scopes all rules to `.bio-page` to prevent admin/global bleed

## Background options

| Type | Value |
|---|---|
| `solid` | `#hex` |
| `gradient` | `linear-gradient(…)` |
| `gradient-animated` | gradient + CSS keyframes |
| `image` | attachment ID (uses `srcset` + WebP) |
| `video` | attachment ID (muted, autoplay, `playsinline`, lazy) |

## Performance rules

- Theme CSS is **inlined** in `<head>` for above-the-fold paint (<8 KB compressed budget)
- Custom fonts use `font-display: swap` and preconnect
- Animated backgrounds respect `prefers-reduced-motion`
- Background videos load only after `requestIdleCallback`
