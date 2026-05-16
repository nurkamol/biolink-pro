# BioLink Pro

A production WordPress plugin for self-hosted bio link pages — Linktree-class polish, fully under your own domain. Drag-and-drop builder, 18 block types, 8 themes, real Stripe + PayPal payments, signed-cookie passcode gates, AI helpers, in-app updater, and a shortcode you can drop on any post.

**Latest:** [v2.3.1](https://github.com/nurkamol/biolink-pro/releases/latest) · [Changelog](CHANGELOG.md) · GPL-2.0-or-later

---

## What ships

### Builder
- **18 block types** in 4 groups — Core (link, button, social icons, rich text, divider), Embed (image gallery, video, YouTube, Spotify, TikTok, map, HTML), Engage (FAQ, countdown, newsletter, contact form), Monetize (product card, donation)
- **Drag-and-drop reorder** via `dnd-kit`, inline inspector that expands below the row
- **Per-block chip row** on each link: thumbnail (media picker), highlight (pulsing glow on the public page), schedule (datetime-local window), passcode lock
- **6 starter templates** covering Creator, Agency, Musician, Restaurant, Coach, Personal

### Theming
- **8 built-in presets** mixing minimal (Mono, Linen, Velvet) and vibrant (Sunset, Aurora, Neon, Glass, Paper)
- **Card-based Design page** — Theme, Header, Wallpaper, Buttons (style + corner radius + accent + text color), Text, Footer
- **Per-page overrides** for accent color, button shape, button style, background (theme / color / gradient / image + overlay)
- **Live phone-frame preview** in the builder, auto-refreshes 350ms after every save

### Public
- `/bio/{slug}/` rewrite (single post type, theme-isolated CSS)
- **Mobile-first**, Lighthouse 90+, prefers-reduced-motion honored
- **Inline unlock modal** for passcode-protected content — signed cookie persists unlocks for 30 days, no page reload
- **Custom analytics** — click + view beacon, 1st-party only, anonymized IPs
- **SEO coexistence** — detects Yoast / Rank Math / SEOPress and pushes our values into their filters instead of double-emitting

### Integrations (no SDKs — `wp_remote_post` only, ~390 KB zip)
- **Stripe Checkout** — donation + product card → real hosted payments
- **PayPal Orders v2** — same, with `?biolink_paypal=return` handler closing the loop
- **"Stripe + PayPal" dual provider** — Stripe primary button with secondary "or pay with PayPal" beneath
- **Mailchimp / MailerLite / Resend** — newsletter + contact form subscribers forwarded to the configured provider
- **OpenAI** — optional, powers ✨ Suggest buttons for bio / CTA / theme palettes

### Operations
- **In-app updater** — `What's New → Install update` runs `Plugin_Upgrader` against the latest GitHub release zip
- **GitHub-based releases** — workflow builds + uploads `biolink-pro-vX.Y.Z.zip` on every `v*` tag
- **Plugin Check clean** — passes wp.org's scanner (ERROR-level)
- **POT translations** generated (`languages/biolink-pro.pot`)
- **Encrypted integrations vault** — libsodium-encrypted credential storage at rest

### Shortcodes
```text
[biolink id="123"]                    full page by ID
[biolink slug="alvasti"]              full page by slug
[biolink id="123" header="0"]         hide the avatar header
[biolink_block id="123" uuid="abc"]   single block (one card / form / etc.)
```
Each embed is independently themed inside the host page via scoped CSS vars. Locked blocks inside an embed gate correctly (placeholder + same inline unlock modal).

---

## Install

### From a GitHub release zip
1. Download `biolink-pro-vX.Y.Z.zip` from the [Releases](https://github.com/nurkamol/biolink-pro/releases) page.
2. WP admin → Plugins → Add New → Upload Plugin → activate.
3. The plugin registers as an update channel against this repo — future updates appear in the normal WP Plugins update flow, or one-click from *BioLink Pro → What's New*.

### From source (development)
```bash
git clone https://github.com/nurkamol/biolink-pro.git
cd biolink-pro
composer install
npm install
npm run build     # admin React bundle
```
Then symlink (or copy) the folder into `wp-content/plugins/biolink-pro` and activate.

**Requirements:** PHP 8.2+, WordPress 6.5+ (tested up to 6.9).

---

## Develop

```bash
npm run dev        # watch admin React build
npm run build      # production build
composer test      # PHPUnit (60 tests)
composer lint      # PHPCS (WordPress standards)
npm run lint       # ESLint + Stylelint
```

The admin app lives in `admin/src/` (React 18 + TypeScript, CSS Modules, HashRouter, `@wordpress/scripts`).
PHP lives in `includes/` under PSR-4 `BioLinkPro\`.
See `docs/CLAUDE.md` for coding conventions and `docs/ARCHITECTURE.md` for the system map.

### Build a release locally
```bash
bin/build-release.sh v2.3.1   # produces biolink-pro-v2.3.1.zip
```
Tag pushes trigger the same build via `.github/workflows/release.yml`.

---

## Docs

| File | Purpose |
|---|---|
| [`CHANGELOG.md`](CHANGELOG.md) | Versioned change history (current: v2.3.1) |
| [`docs/CLAUDE.md`](docs/CLAUDE.md) | Coding conventions + AI agent / contributor instructions |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Phased delivery plan (v1–v2 shipped, v2.4+ next) |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | System architecture, modules, data flow |
| [`docs/API.md`](docs/API.md) | REST endpoint inventory (~40 endpoints) |
| [`docs/BLOCKS.md`](docs/BLOCKS.md) | Block catalog + registration |
| [`docs/THEMES.md`](docs/THEMES.md) | Theme engine + presets |
| [`docs/DATABASE.md`](docs/DATABASE.md) | Custom tables + schema |
| [`docs/SECURITY.md`](docs/SECURITY.md) | Security checklist |
| [`docs/EXTENSIONS.md`](docs/EXTENSIONS.md) | Hooks + filters for third-party integrators |

---

## License

GPL-2.0-or-later (WordPress plugin standard). Contributions welcome under the same license.
