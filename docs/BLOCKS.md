# Block catalog

## Registration model

Every block exists in three layers:

1. **PHP class** — `includes/Blocks/{Type}Block.php` — extends `AbstractBlock`, defines schema, sanitizer, server-side render.
2. **React editor** — `admin/src/components/blocks/{Type}Editor.tsx` — inspector + canvas preview.
3. **Frontend renderer** — server-rendered HTML emitted by the PHP class; tiny progressive-enhancement JS only when the block needs it.

Registration:

```php
add_action('biolink/blocks/register', function (BlockRegistry $registry) {
    $registry->register(new LinkBlock());
    $registry->register(new VideoBlock());
    // …
});
```

A new block requires:
- `slug` (snake_case, stable — used in stored JSON)
- `label` (translated)
- `icon` (lucide icon name or SVG path)
- `schema()` returning a Zod-equivalent PHP schema (validator/sanitizer pair)
- `render(array $data, RenderContext $ctx): string`
- `assets()` returning admin + frontend asset handles to enqueue when used

## Built-in blocks

| Slug | Label | Notes |
|---|---|---|
| `link` | Link | Label, URL, icon, optional UTM, scheduling window |
| `button` | Button | Variant (primary/secondary/ghost), size, icon |
| `social_icons` | Social Icons | Multi-platform row; supported platforms in `Integrations\Social` |
| `image_gallery` | Image Gallery | 1–N images, layout (grid/carousel), lazy-loaded |
| `video` | Video | Self-hosted MP4 or oEmbed URL |
| `spotify` | Spotify Embed | Track/album/playlist; uses Spotify oEmbed |
| `youtube` | YouTube Embed | lite-youtube facade (no JS until interaction) |
| `tiktok` | TikTok Embed | Official embed script lazy-loaded |
| `contact_form` | Contact Form | Inline form → REST → email/webhook |
| `divider` | Divider | Style (line, dot, space), color |
| `rich_text` | Rich Text | WYSIWYG (Slate or tiptap in admin); sanitized via `wp_kses_post` |
| `faq` | FAQ | Accordion items, JSON-LD `FAQPage` emitted |
| `countdown` | Countdown | Target datetime, expired message, timezone-aware |
| `newsletter` | Newsletter | Provider via `Integrations\Email` (Mailchimp, MailerLite, Resend) |
| `product_card` | Product Card | Image, price, CTA URL |
| `donation` | Donation | Stripe or PayPal; preset amounts + custom |
| `html_embed` | HTML Embed | Admin-only, `unfiltered_html` cap required |
| `map` | Map Embed | Static OpenStreetMap or Google Maps embed (provider-agnostic) |

## Storage shape

Stored inside `_biolink_data` post meta JSON:

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

For high-cardinality types like `link`, the `LinkBlock::onSave()` hook mirrors entries into `wp_biolink_links` so analytics can join on a stable `link_id`. The `block_uuid` in the table ties back to the JSON entry.

## Adding a custom block (third-party)

```php
namespace MyPlugin\Blocks;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\RenderContext;

final class TestimonialBlock extends AbstractBlock
{
    public function slug(): string { return 'testimonial'; }
    public function label(): string { return __('Testimonial', 'my-plugin'); }
    public function icon(): string { return 'quote'; }

    public function schema(): array
    {
        return [
            'quote'  => ['type' => 'string', 'required' => true, 'max' => 500],
            'author' => ['type' => 'string', 'required' => true, 'max' => 100],
            'avatar' => ['type' => 'integer', 'required' => false], // attachment ID
        ];
    }

    public function render(array $data, RenderContext $ctx): string
    {
        return sprintf(
            '<blockquote class="bio-testimonial"><p>%s</p><cite>%s</cite></blockquote>',
            esc_html($data['quote']),
            esc_html($data['author'])
        );
    }
}

add_action('biolink/blocks/register', function ($registry) {
    $registry->register(new \MyPlugin\Blocks\TestimonialBlock());
});
```
