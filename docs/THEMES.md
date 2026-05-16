# Theme engine

## Concept

A **theme** is a `BioLinkPro\Themes\Preset` value object — an immutable bundle of CSS custom property values plus a background descriptor. `ThemeEngine::renderStyleBlock()` injects them as a `<style>` tag scoped to a selector (default `body.bio-body`, or `.bio-embed-{id}` for shortcode embeds).

Themes are PHP-defined and registered at boot. User-created themes via REST is on the roadmap (`/themes` POST endpoint declared but not built).

## Built-in presets

8 presets, defined in `ThemeEngine::registerBuiltins()`:

| Slug | Vibe |
|---|---|
| `mono` | Clean white / black, system sans, default starter |
| `glass` | Frosted glass cards over soft gradient |
| `forest` | Earthy green, serif headings |
| `midnight` | OLED dark, high contrast |
| `neon` | Black bg, neon accent, mono font |
| `sunset` | Warm orange→pink gradient |
| `aurora` | Cool purple→teal gradient |
| `sky` | Soft pastel blue |

Each is wired in PHP via `new Preset(slug, label, description, background, textColor, mutedColor, accentColor, accentText, surfaceColor, borderColor, fontStack, headingFontStack, buttonShape, buttonStyle, shadow, swatch, googleFonts?)`.

## Token catalog (what `Preset::toCssVars()` emits)

Every preset emits these custom properties:

```css
--bio-bg                  /* color | linear-gradient(…) | url(…) */
--bio-color-text          /* body copy */
--bio-color-muted         /* secondary text */
--bio-color-accent        /* primary action color */
--bio-color-accent-text   /* foreground on the accent surface */
--bio-color-surface       /* card / link background */
--bio-color-border        /* hairline borders */
--bio-font-stack          /* CSS font-family for body */
--bio-heading-stack       /* CSS font-family for headings */
--bio-button-radius       /* derived from buttonShape: 999 / 14 / 4 px */
--bio-shadow              /* card shadow */
--bio-button-shape        /* pill | rounded | square (raw token) */
--bio-button-style        /* filled | outline | glass (raw token) */
```

Per-page settings can override `--bio-color-accent`, `--bio-color-accent-text`, `--bio-button-radius`, and the background — applied as a narrow override layer on the same selector. See `Themes\ThemeEngine::renderStyleBlock()`.

## Per-page background override

Stored in `block.settings`:

| `bg_type` | Extra fields |
|---|---|
| `theme` | none — uses preset's background |
| `color` | `bg_color` (hex) |
| `gradient` | `bg_gradient_from`, `bg_gradient_to`, `bg_gradient_angle` (deg) |
| `image` | `bg_image_id` (attachment), `bg_overlay` (0–100, dark scrim %) |

`ThemeEngine::renderStyleBlock()` reads these and emits a `--bio-bg:…` override rule alongside the preset tokens.

## Scoped rendering (shortcodes)

```php
$style = $themes->renderStyleBlock(
    themeSlug: 'sunset',
    settings:  $page_settings,
    selector:  '.bio-embed-42'
);
```

Wrap your rendered markup in a matching `.bio-embed-42` container. Background rule omits `background-attachment: fixed` for embeds so they don't escape the wrapper. Used by `Frontend\Shortcodes` for `[biolink]` / `[biolink_block]` to keep multiple embeds independently themed inside the same host page.

## Google Fonts

Presets that need a Google Font set `googleFonts: ['Family Name' => '300;400;700']`. `renderStyleBlock` emits an `@import url('https://fonts.googleapis.com/css2?…&display=swap')` at the top of the inline style block.

## Performance notes

- Theme CSS is **inlined** in `<head>` for first paint (no extra request).
- The body / wrapper sets `background-attachment: fixed` for fullpage renders so the background doesn't jump on scroll; embeds skip this.
- `prefers-reduced-motion` honored for the highlight pulse animation in `assets/frontend/biolink.css`.

## Custom themes (not yet implemented)

A custom theme REST surface (`POST /themes`, `PATCH /themes/{slug}`, `DELETE`) is declared in `API.md` but the controller is not built. Filing under v2.4+ candidates in `ROADMAP.md`. For now, extend `ThemeEngine::registerBuiltins()` or hook `biolink/themes/register`:

```php
add_action( 'biolink/themes/register', function ( $themes ) {
    $themes->register( new \BioLinkPro\Themes\Preset(
        slug:        'my-theme',
        label:       __( 'My Theme', 'my-plugin' ),
        // …16 more positional or named args
    ) );
} );
```
