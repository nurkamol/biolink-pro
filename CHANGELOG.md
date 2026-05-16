# Changelog

All notable changes to BioLink Pro are documented here.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2026-05-17

Polish + functionality pass on the v2.0.0 redesign. Sidebar trimmed, action chips wired up, emoji UI replaced with consistent SVG icons.

### Added
- **Thumbnail chip** on every link row — opens the WordPress media picker, stores `_thumbnail_id` on the block, `LinkBlock` renders the image as a 36×36 rounded square next to the label (replaces the utility-icon glyph when present).
- **Highlight chip** — toggles `_highlight: true` on the block. `PageRenderer` wraps highlighted blocks in `<div class="bio-block bio-block--highlight">`, which pulses a soft glow (`@keyframes bio-highlight-pulse`). Honors `prefers-reduced-motion`.
- **Schedule chip** — popover with `datetime-local` inputs for visible-from / visible-until. Stores `_start_at` / `_end_at` on the block. `PageRenderer::isScheduleActive()` skips blocks outside their window (site-local time, matching the admin's input).
- **Lock chip** — popover renders, currently inert with "Coming in v2.2" explainer (passcode-gated links need server-side session handling). UI is in place so v2.2 only needs the gate.
- **Polished SVG icon set** in `components/ui/Icons.tsx` — grid / bars / sparkle / cog / image / star / clock / lock / trash / grip / external / copy / caret / check / plus / close / pencil / search. All monochrome, 1.8 stroke. Replaces every emoji in the chrome (drag handle, trash, view, copy URL, search, modal close, nav caret).

### Changed
- **Sidebar trimmed** to working items only. Removed: Earn, Audience, Tools group (Social planner / IG auto-reply / Link shortener / Post ideas), Setup checklist. Routes for those stubs deleted with `pages/builder/StubPage.tsx`.
- **Status pill** in the top bar shows "Live" / "Draft" with a leading dot indicator instead of lowercase raw status text.
- **Saved indicator** now leads with a check icon.
- **Link row** gains a small uppercase type badge ("Link" / "Button" / etc.) so the block kind is glanceable when a thumbnail replaces the type icon.
- **`LinksPage.module.css`** popover styles, active-chip background, hover states on rows.

### Fixed
- `LinkBlock::render()` now preserves `_thumbnail_id` through `FieldValidator::validate()` (it would otherwise be stripped along with other unknown keys).

## [2.0.0] - 2026-05-16

Linktree-class admin redesign. Major UI overhaul — the old top-bar nav and tabbed PageDetail are gone, replaced by a left sidebar with grouped nav, page-scoped routes (Links / Design / Shop / Insights), and a sticky phone preview pane.

### Added
- **New AppShell** — left sidebar with brand mark, page selector dropdown, grouped nav (My BioLink → Links/Shop/Design; Earn; Audience; Insights; Tools → Social planner / IG auto-reply / Link shortener / Post ideas; Account → What's New / Settings). Coming-soon stub pages for Earn, Audience, and every Tools entry. Setup checklist card at the bottom.
- **Page-scoped routes** — `/pages/:id/{links,design,shop,insights,earn,audience}`. `BuilderShell` wraps each with a shared top bar (page title + status pill + saved indicator + Publish + View ↗) and a sticky right-side phone preview iframe on Links/Design/Shop. Auto-refresh on save (350ms debounce).
- **Linktree-style Links page** — profile card at top (avatar + name + bio + edit pencil), big purple `+ Add` button, secondary row for collections/archive, drag-and-drop link rows with: drag handle, title + URL, on/off toggle, action chip row (thumbnail / highlight / schedule / lock — chip stubs for v2.1 features), delete, and inline inspector when expanded.
- **Card-based Design page** — replaces the old settings rail. Cards: Theme, Header, Wallpaper, Buttons (style + corner radius + accent color + button text color), Text, Footer. Each card collapses; opening one closes the others.
- **Centralized Add modal** — replaces the inline inserter. Big overlay modal with search field, category rail (Suggested / Commerce / Social / Media / Engage / View all), hero tiles (Link / Product / Form / Gallery), and a suggested list with platform-style rows.
- **Per-block visibility toggle** — the on/off switch on each link row writes `_active: false` into the block data; `PageRenderer` skips inactive blocks on the public page.

### Changed
- Design tokens now use a Linktree-flavored cream palette (`--biolink-color-app-bg: #f4f1ea`), pill-shaped buttons, larger radii (`--biolink-radius-lg: 22px`), and a `--biolink-sidebar-width: 240px` / `--biolink-preview-width: 380px` layout grid.
- `Analytics` now accepts an optional `initialPageId` prop so the per-page Insights tab opens scoped to the current bio page.

### Removed
- `pages/PageDetail.tsx` and `components/builder/PageBuilder.tsx` — replaced by `BuilderShell` + `LinksPage` + `DesignPage`.

### Roadmap (v2.1)
- Insights page redesign (card-first like screenshot).
- Shop page real UI (today it's a coming-soon stub).
- Real Audience tab with subscriber export.
- Per-page "Hide footer" toggle and Google Font picker wired through `ThemeEngine`.
- Schedule / lock / thumbnail action chips on link rows.

## [1.2.0] - 2026-05-16

### Added
- **In-app updater** — new `POST /biolink/v1/changelog/install-update` endpoint (cap `update_plugins`) runs WordPress's `Plugin_Upgrader` against the latest GitHub release zip. Re-activates the plugin if it was active before. The What's New page now has an "Install update" button on the update-available banner; success state offers a one-click page reload to pick up the new admin bundle.

### Fixed
- **Restored the 18-block React catalog** (regression). `admin/src/blocks/index.ts` had reverted to the v0.3.0 state and was only registering the 8 P1 block editors. All 10 P2 editors — Donation, ProductCard, ContactForm, Countdown, FAQ, HTML Embed, Map, Newsletter, Spotify, TikTok — were missing from the admin inserter and their inline inspectors did not open when the row was selected. Public rendering was unaffected because the PHP `BlockRegistry` was intact, but the editor showed lowercase slugs with the fallback `□` icon. Catalog now restored with the `group` field (`core | embed | engage | monetize`) for the grouped inserter.
- **`ProductCardData` TypeScript interface** was missing `provider`, `currency`, and `price_value` fields even though the editor JSX referenced them. Added.

## [1.1.2] - 2026-05-16

### Fixed
- **Hotfix**: v1.1.1 shipped without `Integrations\PayPal\Checkout::captureAndLog()` — the file Edit was silently rejected during the v1.1.1 push, so the `ReturnHandler` crashed with `Call to undefined method` (HTTP 500) on every PayPal return. Adds the method back. Smoke-test: `GET /bio/{slug}/?biolink_paypal=return&token=FAKE` now correctly redirects to `?biolink_payment=failed`.

## [1.1.1] - 2026-05-16

### Added — Closed loops
- **PayPal `ReturnHandler`** — `template_redirect` listener on `biolink_page` CPT catches `?biolink_paypal=return&token={order_id}` after approval, calls `Checkout::captureAndLog()`, then `wp_safe_redirect()`s to a clean URL with `?biolink_payment=success|failed`. Cancel returns redirect to `?biolink_payment=cancel`.
- **`Checkout::captureAndLog($order_id)`** — extracted from `CheckoutController::paypalCapture`. Shared between the REST endpoint and the new return handler. Returns the compact entry array on success, null on failure.
- **ProductCard provider support** — new `provider` field (`link | stripe | paypal | stripe_and_paypal`), `price_value` (numeric for checkout), `currency` fields. Same flow as DonationBlock.
- **"Stripe + PayPal" dual-provider option** on both DonationBlock and ProductCardBlock — when both providers are configured AND the block is set to `stripe_and_paypal`, renders Stripe primary button + secondary "or pay with PayPal" button below. Falls back to whichever single provider is configured if one is missing.

### Fixed
- `PageRenderer` now passes `$uuid` to ProductCardBlock (was already passed to LinkBlock + DonationBlock).

### Changed
- `DonationEditor` / `ProductCardEditor` provider dropdown order: **Stripe first** (recommended), then Stripe+PayPal, then PayPal only, then External link.

## [1.1.0] - 2026-05-16

Real integrations — no more hook-only scaffolding. Stripe + PayPal accept real payments; Mailchimp / MailerLite / Resend receive real subscribers.

### Added — Stripe Checkout
- `Integrations\Stripe\Checkout` service — talks to `/v1/checkout/sessions` via `wp_remote_post` (no SDK). Mode (test/live) auto-detected from the `sk_test_` / `sk_live_` prefix.
- `Api\CheckoutController` exposes `POST /biolink/v1/stripe/checkout` — anonymous, accepts `{ page_id, block_uuid, amount, currency, name }`, returns `{ id, url }` pointing at the hosted Checkout page.
- `Integrations\Stripe\StripeWebhookListener` — hooks `biolink/webhook/stripe/verify` (HMAC SHA-256 against `stripe_webhook_secret`) and `biolink/webhook/stripe` for `checkout.session.completed`. Logs to `biolink_stripe_log` (capped 200) and fires `biolink/stripe/completed`.

### Added — PayPal Orders v2
- `Integrations\PayPal\Checkout` — OAuth client_credentials + transient-cached access token, creates orders via `/v2/checkout/orders`. Sandbox/live toggle in Settings.
- `POST /biolink/v1/paypal/checkout` returns approval URL; `POST /biolink/v1/paypal/capture` captures an approved order. `biolink_paypal_log` option + `biolink/paypal/captured` action.

### Added — Email provider adapters
- `Integrations\Email\AbstractEmailAdapter` — common plumbing; hooks `biolink/newsletter/subscribed`.
- `MailchimpAdapter` — PUT `/3.0/lists/{list_id}/members/{email_md5}`, auto-detects datacenter from API key suffix.
- `MailerLiteAdapter` — POST `connect.mailerlite.com/api/subscribers` with optional `groups[]`.
- `ResendAdapter` — POST `/audiences/{id}/contacts`.

### Added — Donation block: real provider flow
- New `provider` field (`link` / `stripe` / `paypal`) on the block schema.
- Provider auto-falls-back to `link` if the chosen provider isn't configured.
- When provider is `stripe` or `paypal`, block renders a `<form>` that POSTs to the checkout endpoint and redirects to the hosted page.
- Amount chips become clickable submit buttons that pre-fill the amount.
- `data-action="checkout"` handler in `biolink.js`.

### Added — Settings UI
- New fields: Stripe webhook secret (encrypted), PayPal sandbox toggle, Mailchimp list_id, MailerLite group_id, Resend audience_id (plain text fields).
- `PLAIN_INTEGRATION_KEYS` allowlist in `SettingsController` for non-secret integration settings.

### Stats
- **36 REST endpoints** under `/wp-json/biolink/v1/` (was 33).
- 60 unit tests still pass.

## [1.0.0] - 2026-05-16

**First stable release** — production-ready: 18 block types, 8 themes, analytics, QR codes, SEO coexistence with Rank Math / Yoast, JSON portability, encrypted integrations vault, AI suggestions, onboarding wizard, accessibility pass.

### Added — Release prep
- **PHPUnit coverage 12 → 60 tests** (156 assertions). New suites: `CryptoTest`, `MarkdownRendererTest`, `FieldValidatorTest`, `GitHubUpdaterTest`, `PresetTest`, `TemplateLibraryTest`, `IconsTest`. Bootstrap expanded with WP function stubs.
- **GitHub Actions PR test workflow** (`.github/workflows/test.yml`) — PHP 8.2 + 8.3 matrix on every PR and push to main; verifies the admin bundle builds.
- **POT translation file** at `languages/biolink-pro.pot` (125 strings, generated via `wp i18n make-pot`).
- **`.wordpress-org/` directory scaffold** with README documenting required banner/icon/screenshot dimensions and an SVG placeholder icon.

### Fixed — Plugin Check (wp.org scanner) ERROR-level findings
- Removed obsolete `.gitkeep` placeholder files.
- Bumped `Tested up to` from 6.6 → 6.9.
- `templates/bio-page.php` phpcs-ignore annotation moved to comment-above-the-line.
- All `$_SERVER` reads now go through `wp_unslash` + `sanitize_text_field`.
- Dropped `suppress_filters` from `uninstall.php`.
- `wp_redirect` (intentional for external links) and `fclose` (CSV streaming) flagged with `phpcs:ignore`.
- `.eslintrc.json`, `.stylelintrc.json`, `.claude/`, `.distignore`, `CHANGELOG.md` added to `.distignore`.

### Known
- `Updates\GitHubUpdater` overrides WordPress's update routine — intentional for our GitHub-distributed release channel. WordPress.org submission would require gating it behind `defined('BIOLINK_PRO_DISABLE_UPDATER')`. Documented in `.wordpress-org/README.md`.

### Stats
- 30 REST endpoints · 18 block types · 8 themes · 6 starter templates
- 60 unit tests, 156 assertions
- Release zip ~3 MB · admin bundle ~570 KB

## [0.6.0] - 2026-05-16

### Added — AI suggestions everywhere
- **`AiSuggestButton`** shared component — "✨ Suggest" pill with loading + error + suggestion list. Already wired into the page subtitle; v0.6.0 adds it on the Button block label (calls `/ai/cta` with the URL as context) and inside `ThemePicker` (calls `/ai/theme`, parses `"<slug>: <reason>"` lines, applies the picked theme on click).
- `AiApi.theme()` client method.

### Added — Integration credentials
- Settings → Integrations now exposes **OpenAI, Stripe, PayPal client ID, PayPal secret, Mailchimp, MailerLite, Resend** fields grouped by category (AI / Payments / Email). Stored encrypted via `Core\Crypto`, returned masked.

### Added — Page portability
- `Api\PortabilityController` — `GET /biolink/v1/pages/{id}/export` (with `?download=1` for direct file) and `POST /biolink/v1/pages/import` (creates a new draft, re-uuids blocks so `wp_biolink_links` doesn't collide).
- Per-row **Export** link in the Pages list; **Import JSON** button in the list header (file picker → POST → navigate to new page).

### Added — Bulk actions
- Multi-select checkboxes with indeterminate header state; **Bulk Duplicate** and **Bulk Delete** with confirmation. Sticky bulk-actions bar with count + Clear.

### Added — "Powered by" credit
- `PageRenderer::renderCredit()` emits a small footer link below the block stream, honoring the existing `biolink_settings.show_credit` toggle (default true).

### Added — Accessibility
- Global `:focus-visible` outline scoped to `.biolink-pro-app-root` so wp-admin's reset doesn't suppress keyboard focus rings.
- `prefers-reduced-motion` media query disables transitions / animations.
- `aria-label` on icon-only buttons; `role="alert"` on error banners; `role="region"` on the bulk-actions bar.

## [0.5.3] - 2026-05-16

### Fixed
- **Release zip is now actually ~3 MB.** v0.5.2's prune step removed ~7 KB. The real culprit was `vendor/endroid/qr-code/assets/noto_sans.otf` (16 MB!) — a font the library bundles for QR labels, which we never use. Release workflow now deletes `endroid/qr-code/assets/*.otf` and `*.ttf` after the rsync stage.

## [0.5.2] - 2026-05-16

### Fixed
- **Release zip trim now actually works.** v0.5.1 added `vendor/*/*/tests/` patterns to `.distignore` but rsync didn't match them. Replaced with a post-rsync `find vendor/ -type d \( -name tests -o ... \) -prune -exec rm -rf` step in `.github/workflows/release.yml`. Also strips `CHANGELOG*`, `UPGRADE*`, `CONTRIBUTING*`, `SECURITY.md`, `README*`, `phpunit.xml*`, `phpstan.neon*`, `psalm.xml*`, `.scrutinizer.yml`, `.travis.yml`, `.php-cs-fixer*`, `Makefile`, and `vendor/bin/`. LICENSE files kept for GPL compliance. Target zip size ~3 MB.

## [0.5.1] - 2026-05-16

### Fixed
- **SEO double-emit with Rank Math / Yoast / AIOSEO / SEOPress.** When one of these SEO suites is active, `Seo\MetaTags::rivalSeoPluginActive()` now returns true and our `<meta>` block is suppressed. Instead, we push the page's title / description / og_image into the rival's own filters (`rank_math/opengraph/facebook/og_title`, `wpseo_opengraph_title`, etc.) so the page's headline + subtitle are used as the share metadata.
- **Sitemap inclusion** — `Seo\Sitemap` now also hooks `wpseo_sitemap_exclude_post_type` (Yoast) and `rank_math/sitemap/post_types` (Rank Math), not just WP core `wp_sitemaps_post_types`.
- **Click tracker now bot-filters** — `Api\ClickController` checks `Tracker::classifyUa()` and skips recording when device is `'bot'`. Bots still get the 302 redirect so functional links keep working. New `biolink/click/before` filter for custom short-circuit logic.

### Added
- **Real QR code dialog** (`QrDialog.tsx`) — preview + download modal with foreground / background color pickers, size slider (256-1536px), PNG/SVG format toggle. Replaces the topbar link that previously opened the raw JSON metadata endpoint.
- **SEO tab in PageDetail** rail — per-page overrides for `custom_title`, `custom_description`, `og_image_id` (with media picker + thumbnail preview), Twitter `@handle`, and `no_index` toggle. Backend was already wired in Phase 6; this exposes it in the admin.
- **`PageRepository::normalizeSeo()`** — sanitizes per-page SEO override fields (`sanitize_text_field` on title/description, `int` cast on `og_image_id`, `bool` cast on `no_index`, regex-validated Twitter handle).
- **`biolink/seo/rival_active` filter** — escape hatch to force our SEO emit even when a rival is detected.

### Changed
- **Release zip trimmed** from ~14 MB → ~3 MB by adding `vendor/*/*/tests/`, `docs/`, `.github/`, CI configs, and `vendor/bin/` to `.distignore`.

## [0.5.0] - 2026-05-16

Feature-complete release covering Phases 5–10 of the original roadmap.

### Added — Phase 5: Analytics
- `Analytics\Tracker` — async event recorder (uses `wp_schedule_single_event` to keep the click redirect fast).
- `Analytics\LinkSync` — mirrors LinkBlock entries into `wp_biolink_links` on every page save so per-link analytics can join on a stable BIGINT id; deactivates removed rows instead of deleting (preserves historical clicks).
- `Api\ClickController` — `GET /biolink/v1/click/{id}?ref=...` rate-limited (10/min/IP), applies UTM, 302 redirects.
- `Api\TrackController` — `POST /biolink/v1/track/view` (rate-limited, bot-filtered).
- `LinkBlock` now routes its href through `/click/{id}` once the link has been synced.
- Frontend `biolink.js` fires a view beacon via `navigator.sendBeacon` on DOMContentLoaded.
- `Analytics\Reporter` — summary, daily timeseries (gap-filled), top links, devices, geo, referrers.
- `Api\AnalyticsController` — `summary | timeseries | links | devices | geo | referrers | export.csv` under `/analytics/pages/{id}/`.
- `Cron\Pruner` — daily job that drops expired rate-limit buckets + analytics events older than `analytics_retention_days` (default 365).
- React Analytics page with summary cards, custom SVG dual-line sparkline, top-links table, device/country/referrer bar lists, range picker (7d/30d/90d/365d), CSV export link.

### Added — Phase 6: QR codes + SEO
- `Qr\Generator` using `endroid/qr-code` 5.x with on-disk cache keyed by a style hash; PNG or SVG output.
- `Api\QrController` — `GET /biolink/v1/pages/{id}/qr?format=png&fg=#000&bg=#FFF&size=512`.
- QR quick-link in the PageDetail topbar.
- `Seo\MetaTags` — title, description, robots, canonical, og:(type|title|description|url|site_name|locale|image), twitter:(card|title|description|image|site). Per-page SEO override fields (`custom_title`, `custom_description`, `og_image_id`, `no_index`, `twitter_site`).
- `Seo\StructuredData` — emits JSON-LD `Person` (with `sameAs` aggregated from social_icons blocks) + `WebPage`. FaqBlock continues to emit its own `FAQPage` graph.
- `Seo\Sitemap` — registers `biolink_page` CPT with `wp_sitemaps`.

### Added — Phase 7: Integrations + settings
- `Core\Crypto` — symmetric encrypt/decrypt for at-rest secrets using `sodium_crypto_secretbox` (key derived from `AUTH_KEY`); base64 fallback when sodium is unavailable.
- `Api\SettingsController` — `GET /settings`, `PATCH /settings`. Secrets stored encrypted and returned masked (last 4 visible).
- `Api\WebhookController` — `POST /biolink/v1/webhooks/{provider}` generic receiver with `biolink/webhook/{provider}/verify` filter + `biolink/webhook/{provider}` action.
- Settings page (React): General tab (analytics retention, credit toggle, tracking toggle, AI toggle) + Integrations tab (OpenAI key, Stripe secret). SecretField shows masked, has Replace + Remove.

### Added — Phase 8: AI
- `Ai\Provider` interface + `Ai\ProviderRegistry` + `Ai\OpenAiProvider` (gpt-4o-mini via `wp_remote_post` to `/v1/chat/completions`, reads encrypted key from settings).
- `Api\AiController` — `POST /ai/{bio|cta|theme}` with per-user 10-per-minute rate limit. Returns `{ suggestions: string[] }`.
- "✨ Suggest" button on the page subtitle in PageHeaderEditor.

### Added — Phase 9: Templates + onboarding
- `Templates\TemplateLibrary` — loads JSON files from `templates/data/` and creates new draft pages from them.
- `Api\TemplatesController` — `GET /templates`, `POST /templates/{slug}/apply`.
- 6 starter templates: `creator`, `agency`, `musician`, `restaurant`, `photographer`, `developer`.
- First-run `OnboardingOverlay` (React) shown until dismissed (per-browser localStorage + server-side `onboarding_complete` setting). Pick a template to start, or "Start from scratch".

### Added — Phase 10 scaffolding
- `docs/EXTENSIONS.md` documents the public hook surface for licensing, multi-tenant SaaS mode, A/B testing, custom-domain mapping, team workspaces, automation workflows, and white-label branding. No core implementation — premium add-ons hook in here.

### Dependencies
- `endroid/qr-code ^5.0` (PHP production dep) — pure-PHP QR code generator.

## [0.4.0] - 2026-05-16

### Added — Phase 4b: P2 blocks (10 new types, 18 total)
- **`SpotifyBlock`** — track/album/playlist iframe embed; height + light/black theme options; parses both `open.spotify.com` URLs and `spotify:` URIs.
- **`TiktokBlock`** — official blockquote markup; embed.js is lazy-loaded only when a TikTok block is present.
- **`FaqBlock`** — accordion of `<details>` items; emits `<script type="application/ld+json">` `FAQPage` structured data for SEO.
- **`CountdownBlock`** — live timer to a target datetime; JS ticker updates every second; shows an "expired" message after the target passes.
- **`ProductCardBlock`** — image + name + description + price + CTA.
- **`HtmlEmbedBlock`** — raw HTML field; sanitized via `wp_kses_post` unless the page author has `unfiltered_html`.
- **`MapBlock`** — OpenStreetMap iframe embed (no API key required); lat/lng + zoom + label.
- **`NewsletterBlock`** — email subscribe form; subscribers stored in `biolink_newsletter_list` option and emailed to site admin.
- **`DonationBlock`** — heading + suggested amounts + payment URL (PayPal.me / Stripe Payment Link). Full Stripe/PayPal SDK integration deferred to Phase 7.
- **`ContactFormBlock`** — name/email/message form that emails the site admin with `Reply-To` set to the visitor.

### Added — Forms backend
- **`Api\FormsController`** — anonymous-POST endpoints (`/newsletter/subscribe`, `/contact/submit`). Guarded by per-page nonce + honey-pot field + 5-per-5-minute IP rate limit.
- IPs hashed (`sha256` with `wp_salt`) before being stored alongside subscriber records — never raw.
- `biolink/newsletter/subscribed` and `biolink/contact/submitted` actions for Phase 7 provider integrations.

### Added — Theme editing
- Per-page theme overrides on top of the chosen preset: `accent_color`, `accent_text_color`, `button_shape` (pill/rounded/square), `button_style` (filled/outline/glass).
- `ThemeEngine::renderStyleBlock` layers per-page overrides on top of preset tokens.
- New "Customize" section inside the Theme tab with color pickers + segmented controls + "Reset to theme defaults" link.
- `BackgroundEditor` now shows a thumbnail preview of the picked background image with a remove button.
- 18-block inserter grouped by category (Core / Embed / Engage / Monetize).

### Added — Frontend JS
- Live countdown ticker.
- TikTok `embed.js` loader (deferred — only injected when a TikTok block is on the page).
- Newsletter + contact form submission handlers with inline status updates.
- `BIOLINK_PRO_PUBLIC` localized object exposes the REST base to public-page JS.

### Fixed
- **Background image was invisible on the public bio page.** `--bio-bg` and other theme tokens were being declared on `.bio-page` but read by `body.bio-body` — CSS custom properties don't inherit upward, so the body always resolved to the fallback. Variables are now declared on `body.bio-body` so the entire bio body picks them up.

## [0.3.0] - 2026-05-16

### Added — Phase 4 cut: themes + polish
- **`Themes\ThemeEngine`** + 8 starter presets — Mono, Glass, Forest, Midnight (minimal/professional) and Neon, Sunset, Aurora, Sky (vibrant). Each preset carries background, accent palette, font stack, button shape (pill/rounded/square), button style (filled/outline/glass), and an SVG-ready swatch.
- **`Themes\Preset`** — immutable value object that serializes to CSS custom properties via `toCssVars()`.
- **Per-page background override** — `_biolink_data.settings.bg_type` accepts `theme | color | gradient | image`, with `bg_color`, `bg_gradient_{from,to,angle}`, `bg_image_id`, `bg_overlay` fields. Renderer emits the override on `.bio-page` so a page can deviate without changing themes.
- **Page header settings** — `avatar_id`, `handle` (renders as `@handle`), `headline`, `subheadline`, `hide_name`. Editable in the new admin Header tab.
- **`Api\ThemesController`** — `GET /biolink/v1/themes` returns the catalog for the admin picker.
- **Frontend theme isolation** — high-specificity reset scoped to `body.bio-body` stops active-theme typography (e.g. Bricks/Bebas Neue) from leaking into bio pages while keeping `wp_head()` available for SEO/analytics plugins.
- **Updated frontend stylesheet** — consumes the new `--bio-*` tokens (button radius, shadow tone, surface color, accent text), adds backdrop blur on glass surfaces, and improves hover lift on links/buttons/social icons.

### Added — Admin builder refresh
- **3-section PageDetail layout** — top bar (back link / title / status / save indicator / Save / Publish / View), left settings rail (Header / Theme / Background tabs), middle block builder, right sticky live preview.
- **`PageHeaderEditor`** — avatar picker via wp.media, display name, handle, subtitle, hide-name toggle.
- **`ThemePicker`** — grid of theme tiles with live swatches, fetched from `/themes`.
- **`BackgroundEditor`** — segmented picker for `theme | color | gradient | image`, with color/gradient/angle controls and an image overlay slider.
- **`LivePreview`** — phone-frame iframe that auto-reloads (350ms debounced) after every page save or block change. Cache-busts via a `_blpreview=` query param.
- **Inline block inspector** — selected block expands its editor below the row instead of taking the right rail (frees the rail for live preview).
- Debounced settings autosave (~500ms) on header + background changes.

### Fixed
- **REST client double rootURL middleware** — `wp-api-fetch` already registers a default rootURL middleware via `wpApiSettings`. Our custom one caused every request to resolve to `/wp-json/pages` (404). Dropped the custom middleware; paths in `client.ts` now include the full `/biolink/v1` namespace.

## [0.2.0] - 2026-05-16

### Added — Phase 3: block builder
- **8 P1 block types** in `BioLinkPro\Blocks\Types\`: `LinkBlock`, `ButtonBlock`, `SocialIconsBlock`, `ImageGalleryBlock`, `RichTextBlock`, `DividerBlock`, `VideoBlock`, `YouTubeBlock`. Each implements `slug() / label() / icon() / schema() / render()`.
- `Blocks\Schema\FieldValidator` — schema-driven sanitizer used by every block's `render()` path (types: `string`, `text`, `url`, `email`, `color`, `int`, `bool`, `enum`, `array`).
- `Blocks\Icons` — inline-SVG dictionary (12 utility icons + 14 social brand marks) so blocks emit icons without pulling an icon font.
- `Frontend\PageRenderer` — orchestrates a page: header (avatar/headline/subheadline) + block stream, with `biolink/page/render/before` and `biolink/page/render` filters.
- `Frontend\TemplateLoader` — hooks `template_include` so `biolink_page` CPT visits render via `templates/bio-page.php` instead of the active theme's `single.php`.
- `Frontend\Assets` — enqueues `assets/frontend/biolink.{css,js}` only on bio pages.
- `templates/bio-page.php` — minimal full-page template (no theme chrome) for fast LCP.
- `assets/frontend/biolink.css` — mobile-first BEM stylesheet covering header + all 8 block types with CSS custom-property tokens.
- `assets/frontend/biolink.js` — tiny progressive-enhancement bundle that swaps the YouTube facade for a real iframe on click.

### Added — Phase 3: React block builder
- 8 typed block editors in `admin/src/blocks/`: `LinkEditor`, `ButtonEditor`, `SocialIconsEditor`, `ImageGalleryEditor`, `RichTextEditor`, `DividerEditor`, `VideoEditor`, `YouTubeEditor`.
- `admin/src/blocks/index.ts` — block catalog with default data + preview formatter per type.
- `admin/src/components/builder/PageBuilder.tsx` — dnd-kit-powered canvas with sortable rows, inserter popover, right-rail inspector, and per-row delete; optimistic updates with rollback through `BlocksApi.append / update / remove / reorder`.
- `admin/src/lib/mediaFrame.ts` — typed wrapper around `wp.media` for `ImageGalleryEditor` + `VideoEditor`. `Admin\Assets` now calls `wp_enqueue_media()` on plugin admin screens.
- Replaces the Phase 2 placeholder inside `PageDetail.tsx`.

### Dependencies
- `@dnd-kit/core`, `@dnd-kit/sortable`, `@dnd-kit/utilities` added for the sortable canvas.

## [0.1.0] - 2026-05-16

### Added — Release infrastructure
- **GitHub-based update channel**: `BioLinkPro\Updates\GitHubUpdater` hooks `pre_set_site_transient_update_plugins`, `plugins_api`, `upgrader_source_selection`, and `upgrader_process_complete` so wp-admin offers one-click updates from `nurkamol/biolink-pro` GitHub Releases.
  - Polls `https://api.github.com/repos/nurkamol/biolink-pro/releases/latest` with 12-hour transient cache; drops prereleases.
  - "View version details" modal on the wp-admin Plugins screen renders the release body as the Changelog tab via `plugins_api`.
  - Source-dir rename on `upgrader_source_selection` keeps installs landing back at `wp-content/plugins/biolink-pro/`.
- `BioLinkPro\Updates\MarkdownRenderer` — tiny safe markdown→HTML converter (headings, lists, links, code) used for rendering GitHub release bodies, run through `wp_kses_post`.
- `BioLinkPro\Api\ChangelogController` — REST endpoints `GET /biolink/v1/changelog` (release list with rendered HTML bodies) and `GET /biolink/v1/changelog/update-status` (current vs latest + download URL).
- **What's New** admin submenu (BioLinks → What's New) with React `Changelog` page: installed-version badge, "Update available" banner with Go-to-Updates + Download-zip actions, scrollable release history, manual "Check for updates" refresh button.
- Submenu deep-link handling in `admin/src/main.tsx` — `?page=biolink-pro-changelog` routes to `#/changelog`, same for Settings.

### Added — Release tooling
- `.distignore` — rsync exclusion patterns for the release zip (drops `tests/`, `docs/`, `admin/src/`, build configs, `node_modules/`, etc.).
- `bin/build-release.sh` — local script that produces `dist/biolink-pro-vX.Y.Z.zip` with `biolink-pro/` as the top-level dir. Reads version from `plugin.php`, runs `composer install --no-dev` + `npm run build`, then re-installs dev deps.
- `.github/workflows/release.yml` — on tag push `v*.*.*`, validates the tag matches both `plugin.php` header `Version:` and `BIOLINK_VERSION`, builds the zip, extracts the matching `CHANGELOG.md` section as the release body, and publishes a GitHub Release with the asset attached.

### Added — Phase 2: REST API + admin shell
- `BioLinkPro\Api\AbstractController` base + `RestRouter` registering namespace `biolink/v1` on `rest_api_init`.
- `BioLinkPro\Api\PagesController` — full CRUD on `biolink_page` (`GET /pages`, `GET /pages/{id}`, `POST /pages`, `PATCH /pages/{id}`, `DELETE /pages/{id}`, plus `/duplicate` and `/publish`), each cap-gated against `biolink_manage_pages` / `biolink_publish_pages`.
- `BioLinkPro\Api\BlocksController` — `GET /blocks` registry endpoint and per-page block ops (`POST /pages/{id}/blocks`, `PATCH|DELETE /pages/{id}/blocks/{uuid}`, `POST /pages/{id}/blocks/reorder`).
- `BioLinkPro\Frontend\Repository\PageRepository` — single read/write surface for the `_biolink_data` JSON meta (normalize, append/update/delete/reorder blocks).
- `BioLinkPro\Blocks\AbstractBlock` + `BlockRegistry` skeleton (registry fires `biolink/blocks/register` on `init`; concrete blocks land in Phase 3).
- `BioLinkPro\Admin\Menu` — top-level "Bio Links" admin menu + Dashboard/Settings submenus that mount the React app.
- `BioLinkPro\Admin\Assets` — scoped enqueue of `assets/admin/main.{js,css}` with `wp_localize_script( 'BIOLINK_PRO', ... )` exposing REST base, nonce, and per-cap flags.
- `@wordpress/scripts` build pipeline: `package.json`, `webpack.config.js` (entry `admin/src/main.tsx` → `assets/admin/`), `tsconfig.json`, `.eslintrc.json`, `.stylelintrc.json`.
- React 18 admin shell with HashRouter routing (Dashboard, Pages, Page Detail, Settings) and CSS Modules for all styling.
- `admin/src/api/client.ts` — typed `PagesApi` + `BlocksApi` wrappers around `@wordpress/api-fetch` with auto nonce + REST root middleware.
- Pages UI: list/create/delete via REST, page-detail view with title edit + publish action.
- Unit tests for `PageRepository` normalization and `BlockRegistry` register/unregister.

### Decisions
- **Styling:** CSS Modules (Phase 1 decision deferred; settled in Phase 2 kickoff).

### Added — Phase 1: Core bootstrap
- `plugin.php` bootstrap with PHP 8.2+ / WP 6.5+ version guards and constants (`BIOLINK_VERSION`, `BIOLINK_DB_VERSION`, `BIOLINK_PATH`, `BIOLINK_URL`, `BIOLINK_BASENAME`).
- Composer setup with PSR-4 autoload (`BioLinkPro\` → `includes/`).
- `BioLinkPro\Core\Plugin` singleton + minimal service container with `Bootable` interface.
- `BioLinkPro\Core\Activator` — environment check, uploads dir creation, migration run, capability install, rewrite flush.
- `BioLinkPro\Core\Deactivator` — clears scheduled cron events, flushes rewrites.
- `uninstall.php` — drops custom tables, removes CPT posts, deletes options + user meta, removes uploads dir, clears cron; honors `biolink_keep_data`.
- `BioLinkPro\Core\Capabilities` — registers six custom capabilities (`biolink_manage_pages`, `biolink_publish_pages`, `biolink_manage_themes`, `biolink_view_analytics`, `biolink_manage_integrations`, `biolink_use_ai`).
- `BioLinkPro\Database\Migrator` + five initial migrations (`biolink_links`, `biolink_clicks`, `biolink_views`, `biolink_qr`, `biolink_rate_limit`).
- `biolink_page` CPT registration with `/bio/{slug}` rewrite and `map_meta_cap` against custom capabilities; `_biolink_data` JSON meta key.
- PHPCS config (`phpcs.xml`) — WordPress + WordPress-Security + PHPCompatibilityWP rulesets.
- PHPUnit config + bootstrap (`phpunit.xml.dist`, `tests/bootstrap.php`) with sample unit tests for `Plugin` and `Capabilities`.
