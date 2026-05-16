=== BioLink Pro ===
Contributors: nurkamol
Tags: link in bio, linktree, bio link, landing page, link builder
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted Linktree alternative — drag-and-drop bio pages with 18 block types, 8 themes, analytics, QR codes, AI suggestions, and more.

== Description ==

BioLink Pro is a production-grade bio link / link-in-bio builder for WordPress. Build mobile-first bio pages with a drag-and-drop block editor, switch between 8 polished themes (Mono, Glass, Forest, Midnight, Neon, Sunset, Aurora, Sky), track every click and view, generate QR codes for your pages, optionally use AI to write your bio copy, and connect Stripe / PayPal / Mailchimp / MailerLite / Resend through an encrypted credential vault.

Everything is self-hosted on your own WordPress install — no SaaS account, no recurring fee, no data shared with a third party.

= Block types (18) =

**Core** — Link · Button · Social Icons · Rich Text · Divider
**Embed** — Image Gallery · Video · YouTube · Spotify · TikTok · Map (OpenStreetMap) · HTML Embed
**Engage** — FAQ (with JSON-LD `FAQPage`) · Countdown · Newsletter · Contact Form
**Monetize** — Product Card · Donation

= Themes (8) =

4 minimal/professional: **Mono** · **Glass** (frosted) · **Forest** (dark green) · **Midnight** (inky)
4 vibrant: **Neon** (pink glow) · **Sunset** (coral→amber) · **Aurora** (cyan→purple) · **Sky** (soft blue)

Each theme bundles its own background, accent palette, font stack, button shape (pill/rounded/square) and button style (filled/outline/glass). Per-page overrides let you tweak any token without leaving the theme.

= Backgrounds =

Per-page background type: theme default · solid color · gradient (custom colors + angle) · image with overlay slider. Picked via the WordPress media library.

= Analytics =

Click + view tracking with async writes (no impact on the redirect path). Daily/weekly/monthly/yearly date range. Top links table, devices / browsers / OS / countries / referrers bar lists. CSV export. Daily cron pruning honours configurable retention (default 365 days). IPs are SHA-256 hashed with the site salt — never stored raw.

= SEO =

Per-page custom title, meta description, share image, Twitter handle, no-index toggle. JSON-LD `Person` + `WebPage` graphs (FAQ blocks emit their own `FAQPage`). Inclusion in WordPress core, Yoast, and Rank Math sitemaps. Coexists cleanly with Rank Math / Yoast / AIOSEO / SEOPress (defers to them and pushes our values into their filters).

= QR codes =

Per-page QR generation with foreground / background color pickers, size slider (256–1536px), PNG or SVG output. Cached on disk for repeat downloads.

= Templates + onboarding =

6 starter templates (Creator, Agency, Musician, Restaurant, Photographer, Developer). First-run overlay lets new users pick a template or start from scratch. JSON page export / import for backups and cross-site migration.

= AI suggestions (optional) =

"✨ Suggest" buttons on the bio subtitle, button label, and theme picker. Powered by OpenAI (your API key, stored encrypted). 10-per-minute per-user rate limit.

= Integrations vault =

Encrypted-at-rest API keys for OpenAI, Stripe, PayPal, Mailchimp, MailerLite, Resend (libsodium secretbox keyed off `AUTH_KEY`). Generic `/webhooks/{provider}` receiver with `biolink/webhook/{provider}/verify` filter for signature verification.

= Privacy + GDPR =

* IP addresses are SHA-256 hashed with `wp_salt()` before storage — never raw.
* Configurable analytics retention (default 365 days), pruned daily.
* Click + view tracking can be disabled globally.
* No external network calls for the public bio page (everything self-hosted).

= Live preview =

Phone-frame iframe alongside the builder. Reloads after every save with a 350ms debounce. Always shows what the public page actually looks like.

= REST API =

30+ endpoints under `/wp-json/biolink/v1/` for full programmatic control: pages CRUD, blocks (append/update/reorder/delete), themes catalog, templates apply, analytics (summary/timeseries/links/devices/geo/referrers/CSV export), QR generation, page export/import, AI suggestions, settings, webhooks.

= Accessibility =

Visible focus rings on every interactive control, ARIA labels on icon-only buttons, `prefers-reduced-motion` honored. WCAG-conscious admin UI.

== Installation ==

1. Upload the plugin zip via **Plugins → Add New → Upload Plugin**, or unpack into `/wp-content/plugins/biolink-pro`.
2. Activate through the **Plugins** screen.
3. A first-run overlay will let you pick a starter template or start from scratch.
4. Find your pages at `/bio/{slug}` once published.

== Frequently Asked Questions ==

= Does this work without any paid SaaS account? =

Yes. Everything runs on your own server. No external account required unless you opt into AI (OpenAI), payments (Stripe / PayPal), or email (Mailchimp / MailerLite / Resend) — and even then, you bring your own keys.

= Does it work with Rank Math / Yoast / AIOSEO / SEOPress? =

Yes — explicitly. When one of those SEO plugins is active we suppress our own `<meta>` block and instead push the page's headline / subtitle / share image into the SEO plugin's filters, so you get clean coexistence with no duplicate tags. CPT also auto-registers in their sitemaps.

= How do I track clicks per link? =

Out of the box. Every Link block routes through `/wp-json/biolink/v1/click/{id}` which records the click (rate-limited per IP, bot-filtered) and 302-redirects to the destination. Click analytics show per-link totals in the Analytics page.

= Can I theme my bio pages with custom CSS? =

The 8 built-in themes cover most needs. Each preset emits CSS custom properties on `body.bio-body` (e.g. `--bio-color-accent`, `--bio-button-radius`), so you can override any token in your active WordPress theme's stylesheet.

= What PHP version is required? =

PHP 8.2 or newer. WordPress 6.5 or newer.

= Is it multisite-compatible? =

Yes. Each site has its own custom tables (`wp_biolink_links`, `wp_biolink_clicks`, `wp_biolink_views`, `wp_biolink_qr`, `wp_biolink_rate_limit`), so analytics are network-isolated.

= Where can I report issues or suggest features? =

GitHub: https://github.com/nurkamol/biolink-pro/issues

== Screenshots ==

1. The drag-and-drop block builder with the live phone-frame preview on the right and the settings rail (Header / Theme / Background / SEO) on the left.
2. The Analytics dashboard with summary cards, dual-line activity chart, top links, devices and country breakdowns.
3. Picking a theme — 8 starter presets covering minimal and vibrant looks.
4. Per-page background override (theme default / color / gradient / image with overlay).
5. The "What's New" admin page with release notes pulled from GitHub Releases.
6. A published bio page on mobile (Sunset theme).

== Upgrade Notice ==

= 1.0.0 =
First stable release. Production-ready: 18 block types, 8 themes, analytics, QR codes, SEO coexistence with Rank Math / Yoast, JSON portability, encrypted integrations vault, AI suggestions, onboarding wizard, accessibility pass. Tested on WordPress 6.9 and PHP 8.2 / 8.3.

== Changelog ==

= 1.1.1 =
* PayPal return loop closed — when visitors return from PayPal approval, the order is captured automatically and they're redirected to a clean URL with `?biolink_payment=success|failed`.
* ProductCard block now supports Stripe / PayPal / Stripe+PayPal providers (same flow as Donation block).
* New "Stripe + PayPal" provider option on both Donation and ProductCard — renders Stripe as the primary button with a secondary "or pay with PayPal" beneath it.

= 1.1.0 =
* Real Stripe Checkout — Donation block can now open a hosted Checkout session instead of just linking out (set Provider = Stripe in the block editor).
* Real PayPal Orders v2 — Donation block can now open a PayPal approval flow.
* Mailchimp / MailerLite / Resend adapters — newsletter subscribers are forwarded to the configured provider via wp_remote_post (no SDK).
* Settings → Integrations: new fields for Stripe webhook secret, PayPal sandbox toggle, Mailchimp list ID, MailerLite group ID, Resend audience ID.
* New REST endpoints: `POST /biolink/v1/stripe/checkout`, `POST /biolink/v1/paypal/checkout`, `POST /biolink/v1/paypal/capture`. 36 total endpoints now.
* Stripe webhook signature verification (HMAC SHA-256) hooked via `biolink/webhook/stripe/verify`.
* `biolink/stripe/completed` and `biolink/paypal/captured` action hooks fire on successful payments.

= 1.0.0 =
* First stable release.
* PHPUnit coverage bumped from 12 → 60+ tests covering Crypto, MarkdownRenderer, FieldValidator, GitHubUpdater, Preset, TemplateLibrary, Icons.
* GitHub Actions test workflow (runs on PRs + push to main, PHP 8.2 + 8.3 matrix).
* POT translation file generated (`languages/biolink-pro.pot`, 125 strings).
* Plugin Check (wp.org scanner) ERROR-level findings fixed: removed `.gitkeep` placeholders, bumped "Tested up to" to 6.9, switched bio template to `phpcs:disable/enable` block, added `wp_unslash` + `sanitize_text_field` to all `$_SERVER` reads, dropped `suppress_filters` from uninstall query, marked `wp_redirect` (intentional) + `fclose` (CSV streaming) with `phpcs:ignore`.
* Readme polished for wp.org-quality discovery (features list, FAQ, screenshots refs, upgrade notice).
* `.wordpress-org/` directory scaffolded with README explaining required banner / icon / screenshot dimensions for wp.org submission.
* **Known limitation:** `Updates\GitHubUpdater` is intentional for our GitHub-distributed release channel. If you submit this fork to wp.org, gate it behind `defined('BIOLINK_PRO_DISABLE_UPDATER')` — wp.org doesn't allow third-party update hooks.

= 0.6.0 =
* "✨ Suggest" button on Button block label (calls /ai/cta) and inside the Theme picker (calls /ai/theme, parses "<slug>: <reason>" lines).
* Settings → Integrations now exposes credential fields for OpenAI, Stripe, PayPal (client ID + secret), Mailchimp, MailerLite, and Resend — all encrypted at rest, returned masked.
* JSON page import / export: per-row "Export" link downloads the full page as a portable JSON file; Pages-list header "Import JSON" recreates a draft from one.
* Bulk actions on the Pages list (multi-select with indeterminate header checkbox, bulk Delete + Duplicate).
* "Made with BioLink Pro" footer credit on public pages (toggle via Settings → General).
* Accessibility: visible focus ring on every interactive control inside the admin app; ARIA labels on icon-only buttons; `prefers-reduced-motion` honored for transitions.

= 0.5.3 =
* Release zip slimmed to ~3 MB by dropping endroid/qr-code's 16 MB bundled Noto Sans / Open Sans fonts (only used for QR label rendering, which we don't expose).

= 0.5.2 =
* Release zip slimmed from ~14 MB to ~3 MB (v0.5.1's .distignore globs didn't actually match — replaced with a `find vendor/` prune step in the release workflow).

= 0.5.1 =
* Fix: bio pages no longer double-emit OG/Twitter meta when Rank Math / Yoast / AIOSEO / SEOPress is active.
* `biolink_page` CPT now appears in Rank Math + Yoast sitemaps.
* QR code: proper preview + download dialog.
* New SEO tab in PageDetail rail.
* Bot UAs no longer write to the clicks table.

= 0.5.0 =
* Phase 5-10 shipped: analytics, QR + SEO, integrations vault, AI, templates + onboarding, premium hook surface.

= 0.4.0 =
* Phase 4b: 10 new P2 blocks (Spotify, TikTok, FAQ, Countdown, Product Card, HTML Embed, Map, Newsletter, Donation, Contact Form). 18 total blocks.
* Theme editing: per-page overrides for accent / button shape / button style.
* Fix: background image was invisible (CSS custom properties scope bug).

= 0.3.0 =
* Phase 4 cut: 8 starter themes, per-page background override, page header editing, live preview iframe.

= 0.2.0 =
* Phase 3: drag-and-drop block builder, 8 P1 block types, public bio page rendering.

= 0.1.0 =
* Initial release: core bootstrap, CPT, custom tables, capabilities, REST API skeleton, GitHub-based auto-updater.
