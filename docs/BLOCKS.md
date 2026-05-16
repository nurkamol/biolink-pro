# Block catalog

## Registration model

Every block exists in three layers:

1. **PHP class** — `includes/Blocks/Types/{Type}Block.php` — extends `AbstractBlock`, defines schema + server-side render. Registered via `BlockRegistry::register()` in `Plugin::registerCoreServices()`.
2. **React editor** — `admin/src/blocks/{Type}Editor.tsx` — inline inspector that expands when the block row is selected. Registered in the React catalog at `admin/src/blocks/index.ts` (one entry per block: slug, label, icon, group, defaultData, Editor, preview).
3. **Frontend renderer** — server-rendered HTML emitted by the PHP class; matching CSS in `assets/frontend/biolink.css`; tiny progressive-enhancement JS in `assets/frontend/biolink.js` for blocks that need it (YouTube facade, countdown ticks, TikTok embed, form submit, donation/product checkout).

Third-party registration:

```php
add_action( 'biolink/blocks/register', function ( $registry ) {
    $registry->register( new \MyPlugin\Blocks\PollBlock() );
} );
```

## Built-in blocks (18 total, 4 groups)

| Slug | Group | Label | Notes |
|---|---|---|---|
| `link` | core | Link | label + url + icon + optional UTM; click-tracked via `/click/{id}`. Honors `_thumbnail_id`, `_passcode_hash`. |
| `button` | core | Button | variant (primary/secondary), size, ✨ Suggest in editor |
| `social_icons` | core | Social Icons | multi-platform row; supported platforms in `Blocks\Icons` |
| `rich_text` | core | Rich Text | markdown → sanitized HTML, align (left/center/right) |
| `divider` | core | Divider | style (line/dot/space), color, spacing |
| `image_gallery` | embed | Image Gallery | media library picker, layout (grid/carousel), size |
| `video` | embed | Video | self-hosted MP4/WebM with controls toggle |
| `youtube` | embed | YouTube | facade pattern — real iframe loads only on click (`lite-youtube`-style) |
| `spotify` | embed | Spotify | track/album/playlist, theme + height options |
| `tiktok` | embed | TikTok | official embed script lazy-loaded |
| `map` | embed | Map | lat/lng + zoom + label, static OSM tile |
| `html_embed` | embed | HTML Embed | raw HTML — `unfiltered_html` cap required for non-admins |
| `faq` | engage | FAQ | accordion items, emits `FAQPage` JSON-LD |
| `countdown` | engage | Countdown | target datetime (ISO), expired message, ticks via frontend JS |
| `newsletter` | engage | Newsletter | heading + button text; `POST /newsletter/subscribe` → configured Mailchimp/MailerLite/Resend |
| `contact_form` | engage | Contact Form | name + email + message → email + `biolink/contact/submitted` action |
| `product_card` | monetize | Product Card | name + price + currency + image + provider (link / stripe / paypal / stripe_and_paypal) |
| `donation` | monetize | Donation | heading + suggested amounts + currency + provider (link / stripe / paypal / stripe_and_paypal) |

`monetize` blocks render a hosted-checkout-redirect form (Stripe Checkout / PayPal Orders v2). Dual-provider mode shows the primary (Stripe) button with a secondary "or pay with PayPal" beneath.

## Storage shape

Stored inside `_biolink_data` post meta JSON under `blocks[]`:

```json
{
  "blocks": [
    {
      "uuid": "9c2b…",
      "type": "link",
      "data": { "label": "Portfolio", "url": "https://…", "icon": "globe" }
    }
  ]
}
```

For high-cardinality `link` blocks, `Analytics\LinkSync` mirrors entries into `wp_biolink_links` on save so analytics queries can join on a stable `link_id`. The `block_uuid` column ties the table row back to the JSON entry.

## Per-block extension keys (`_*` prefix)

Leading-underscore keys are stored on individual `block.data` and honored at render time by `Frontend\PageRenderer`. They're stored alongside the block's schema fields and survive `FieldValidator::validate()` since blocks read them before validation strips unknown keys.

| Key | Type | Read by | Notes |
|---|---|---|---|
| `_active` | bool | `PageRenderer` | `false` → block is skipped entirely. Toggled via the on/off switch on each link row in the admin. |
| `_highlight` | bool | `PageRenderer` | wraps the rendered output in `<div class="bio-block bio-block--highlight">`. Honors `prefers-reduced-motion`. |
| `_thumbnail_id` | int | `LinkBlock` | WordPress attachment ID. Renders as 36×36 rounded square next to the label, replaces the icon glyph. |
| `_start_at` / `_end_at` | string | `PageRenderer::isScheduleActive()` | Site-local datetime in `YYYY-MM-DDTHH:MM:SS`. Blocks outside the window are skipped. |
| `_passcode_hash` | string | `PageRenderer` / `LinkBlock` | `wp_hash_password()` output (phpass). Plaintext (`_passcode`) is hashed by `BlocksController::hashPasscode()` on append/update and never persisted. Sending `_passcode: ""` clears the lock. Unlock state tracked in the signed `biolink_unlocked` cookie via `UnlockHandler::isUnlocked()`. |

## Adding a custom block (third-party)

```php
namespace MyPlugin\Blocks;

use BioLinkPro\Blocks\AbstractBlock;

final class TestimonialBlock extends AbstractBlock
{
    public function slug(): string  { return 'testimonial'; }
    public function label(): string { return __('Testimonial', 'my-plugin'); }
    public function icon(): string  { return 'quote'; }

    public function schema(): array
    {
        return [
            'quote'  => ['type' => 'string', 'required' => true, 'max' => 500],
            'author' => ['type' => 'string', 'required' => true, 'max' => 100],
        ];
    }

    public function render(array $data): string
    {
        return sprintf(
            '<blockquote class="bio-testimonial"><p>%s</p><cite>%s</cite></blockquote>',
            esc_html((string) $data['quote']),
            esc_html((string) $data['author'])
        );
    }
}

add_action( 'biolink/blocks/register', function ( $registry ) {
    $registry->register( new \MyPlugin\Blocks\TestimonialBlock() );
} );
```

For the block to show up in the React inserter as well, your admin asset must add an entry to the catalog returned by `getBlockCatalog()` in `admin/src/blocks/index.ts`. Third-party React catalog registration is not yet supported via a hook — file an issue if you need it.
