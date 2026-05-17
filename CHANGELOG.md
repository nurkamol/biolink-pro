# Changelog

All notable changes to BioLink Pro are documented here.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.7.1] - 2026-05-17

### Added
- **Inline-editable slug** in the preview pane's URL bar. The slug portion (after the prefix) is now a click target — click it, the bar swaps to an input pre-selected on the slug, Enter or blur saves via the existing `setSlug()`, Escape cancels. A small pencil cue fades in on row hover so the affordance is discoverable. Reuses the v2.7.0 backend; this is purely a more findable entry point than the Design → Page card.

## [2.7.0] - 2026-05-17

### Added
- **Configurable URL prefix.** New `Settings → General → Page URL prefix` field controls the rewrite slug for every bio page. Default is `bio` (so pages live at `/bio/{slug}`). Change it to `links` and every page now lives at `/links/{slug}`. `BioLinkPagePostType::currentSlug()` reads from saved settings; the CPT re-registers + `flush_rewrite_rules(false)` runs automatically on save via `update_option_biolink_settings`. Reserved slugs (`wp-admin`, `wp-json`, etc.) are rejected.
- **Per-page slug editor** on the Design page. New "Page" card at the top exposes the page title and the URL slug. Slug field shows the `yoursite.com/{prefix}/` prefix on the left so you see exactly what the public URL becomes; input sanitizes to `[a-z0-9-]` on blur and saves via `PagesApi.update({slug})`.
- **`setSlug()` on BuilderContext** — typed mutation for the slug, normalizes input + persists via the existing pages PATCH endpoint.

### Notes
- Changing the URL prefix flushes the rewrite cache, but existing search-engine links to `/bio/{old-slug}` will 404 until indexed under the new prefix. Don't change it on a high-traffic page without a 301 plan.
- The `BioLinkPagePostType::REWRITE_SLUG` constant is now @deprecated. It still equals `'bio'` for backwards compat with any external code referencing it, but the CPT registration uses `currentSlug()`.

## [2.6.1] - 2026-05-17

### Fixed
- **Content area card was silently a no-op in v2.6.0.** The CSS used an attribute selector `body.bio-body[ style*="--bio-content-card:1" ]` to gate the card styling, but `ThemeEngine` emits its variables inside a `<style>` block — never on the body's inline `style=` attribute — so the selector never matched and the card never rendered. `ThemeEngine` now emits the full card rule (background, border-radius, backdrop-filter, margin, shadow, plus a `@media (max-width: 540px)` reset) directly inside the dynamic style block so it actually applies. The same fix works for shortcode embeds.

### Added
- **Wallpaper overlay color picker** — the image overlay was hardcoded to black; now you can pick the tint color (white, brand color, anything). Sits next to the existing Overlay opacity slider in the Wallpaper editor. ThemeEngine generates the gradient with the chosen color at the chosen opacity.

## [2.6.0] - 2026-05-17

### Added
- **Wallpaper position + blur** — Background editor (image type) now exposes `Position` (Cover-center / Cover-top / Cover-bottom / Contain / Tile) and a `Blur` slider (0–30 px). The image renders behind a fixed `body.bio-body::before` pseudo so blur applies cleanly without affecting the foreground content.
- **Content area card** — new "Content area" card on the Design page. Three modes:
  - **Transparent** (default, current behavior)
  - **Solid card** — `.bio-page` gets a configurable color + opacity background, rounded corners, drop shadow. Wallpaper fills the desktop viewport behind it.
  - **Frosted glass** — solid card + `backdrop-filter: blur()` so the wallpaper shows through with a frosted effect.
- **Card max-width slider** (380–960 px) — controls the desktop column width.
- **Card corner radius slider** (0–48 px).
- **Card opacity slider** (10–100%) — fine-tune solid/glass card transparency.

### Changed
- **`ThemeEngine` background rendering refactored.** The `body.bio-body` selector no longer paints the bg directly — that moved to `body.bio-body::before` (a fixed full-viewport pseudo) so the new `bg_blur` filter applies to the image without blurring the content. Shortcode embeds (`.bio-embed-{id}`) keep the inline `background:` rule since blur doesn't make sense scoped to an inline embed.
- Image overlay slider max bumped from 80% to 90% (still keeps the image faintly visible at max).

### DB
- 8 new page settings keys: `bg_position`, `bg_blur`, `content_bg_type`, `content_bg_color`, `content_bg_opacity`, `content_blur`, `content_radius`, `content_max_width`. Stored in the existing `_biolink_data.settings` JSON — no schema bump needed.

### Notes
- On screens narrower than 540 px, the content card drops its border-radius + shadow + horizontal margin so it spans the full viewport (mobile-first stays readable).
- `Frosted glass` mode uses `backdrop-filter`, which Safari < 9 and some Linux Firefox builds don't support — they'll fall back to the solid card automatically.

## [2.5.2] - 2026-05-17

### Fixed
- **Version history + Schedule drawer no longer hide under the WP admin bar.** The drawer overlay was using `z-index: 100` and `inset: 0`, so the header ("Version history") was clipped by the WordPress admin bar at the top of the screen. Drawer overlay now sits at `top: 32px` (46px on small screens) with `z-index: 99998` — admin bar remains visible above, drawer header is fully readable.

## [2.5.1] - 2026-05-17

### Changed
- **Top-level pages now flush-left** instead of centered. Removed `margin: 0 auto` from Dashboard / Pages / Analytics / Audience / Changelog / Settings — the centered max-width container left a visible gap between the sidebar and the page content on wider monitors. Padding (24px 28px 60px) is preserved. Analytics also gained the same padding (it was missing it).

## [2.5.0] - 2026-05-17

### Added
- **Per-variant click breakdown UI** — closes the v2.4 A/B testing loop. `Reporter::variantBreakdown()` + `GET /analytics/pages/{id}/variants` return rows grouped by `(link_id, variant_key)` with click counts. Analytics page now renders a "🧪 A/B test results" card showing per-variant click count + share % when any variant data exists for the selected window.
- **Schedule rollup drawer** — new clock icon in the BuilderShell top bar opens a right-side drawer listing every block with `_start_at` or `_end_at` set. Status dot (active / upcoming / expired / open-ended), sorted by date, derived from the in-memory page blocks — no extra REST call.
- **Page revisions** — every save snapshots the full `_biolink_data` JSON into the new `wp_biolink_revisions` table (last 20 per page, older ones pruned). New `GET /pages/{id}/revisions` + `POST /pages/{id}/revisions/{rev_id}/restore`. History icon in the BuilderShell top bar opens the Revisions drawer with relative timestamps, author names, and a Restore button per row (current revision is read-only).
- **Audience tab + CSV export** — new top-level `/audience` route with All / Newsletter / Contact tabs. Newsletter and contact-form submissions are persisted into the new `wp_biolink_submissions` table via subscribers on `biolink/newsletter/subscribed` + `biolink/contact/submitted` actions. `Export CSV` downloads the current filter as a UTF-8 CSV with up to 10,000 rows. Audience nav entry restored to the sidebar.
- **OG image generator** — `Seo\OgImageGenerator` renders a 1200×630 PNG per bio page using GD (no font dep, uses GD's built-in pixel font; brand-font upload is a v2.6 candidate). Caches to `uploads/biolink-pro/og/{page_id}-{hash}.png`, hash includes headline/handle/subheadline/avatar_id/theme so it regenerates on relevant changes. `Seo\MetaTags` falls back to the generated card when no custom `og_image_id` is set.
- **New `biolink/page/saved` action** — fires after `PageRepository::saveData()` succeeds. Used internally by the revisions subscriber; available to third parties.

### Changed
- **Padding on top-level admin pages** — `Dashboard`, `Pages`, `Analytics`, `Audience`, `Changelog`, `Settings` all get `padding: 24px 28px 60px; margin: 0 auto;` so they breathe away from the sidebar like Links and Design do.

### DB
- `BIOLINK_DB_VERSION` bumped to `4`. New migrations 008 (revisions table) + 009 (submissions table). Both run automatically on next activation or `plugins_loaded` version check.

### Notes
- OG cards are intentionally lightweight (bitmap font). A brand-font upgrade with bundled Inter is in the v2.6+ queue if you want pretty social cards.
- Revisions only snapshot the builder JSON, not the WP post itself (title / slug / status). For those, WP core's built-in revisions still work since the CPT supports `revisions`.

## [2.4.0] - 2026-05-17

### Added
- **A/B testing on link blocks** — disclosure section in `LinkEditor` lets you add N variants (key, label, URL, weight). `LinkBlock::pickVariant()` picks deterministically per visitor (hash of IP + page + uuid) so the same visitor sees the same variant on repeat visits. Variant key is appended to the click URL (`?v=A`) and recorded in a new `variant_key` column on `wp_biolink_clicks`. Equal weights work out of the box; weighted splits supported.
- **Passcode-unlock analytics** — new `wp_biolink_unlocks` table, written from a single `biolink/link/unlocked` action subscriber so both the inline modal (`UnlockController`) and the no-JS template_redirect (`UnlockHandler`) feed the same metric. New REST `GET /analytics/pages/{id}/unlocks` returns per-block counts. Lock chip in admin shows a small purple badge with the lifetime unlock count.
- **Real Shop page UI** — replaces the v2.0 stub. Filtered grid view of `product_card` blocks on the current page with thumbnail, name, price, on/off toggle, inline `ProductCardEditor`, and "+ Add product" that appends a new product_card block directly (skipping the centralized Add modal). Products still live in the same `blocks` array — no schema split.
- **Per-page Custom CSS** — new "Advanced" card on the Design page. Textarea writes to `settings.custom_css`; `ThemeEngine` emits it in a separate `<style id="biolink-theme-…-custom">` block right after the theme block. `</style>` sequences are stripped to prevent escape; users target `.bio-page`, `.bio-block--{type}`, `.bio-header`, etc.
- **Pages list search + status filter** — debounced search input + Published/Draft dropdown at the top of the Pages list, wired through `PagesApi.list({ search, status })`.
- **Migrations 006 + 007** — `biolink_unlocks` table and `variant_key` column on `biolink_clicks`. `BIOLINK_DB_VERSION` bumped to `2`.

### Changed
- **`Reporter::unlockCounts( $page_id )`** returns `{ uuid: count }` map. Light query — no date-range arg since unlock counts are lifetime today.
- **Click rate limit** unchanged (10/IP/link/60s); A/B variant key passes through cleanly so rate limiting still applies per link regardless of variant.

### Notes
- Per-variant click breakdown isn't surfaced in the admin yet — column is being written, dashboard card is a v2.5 candidate. Until then, query directly: `SELECT variant_key, COUNT(*) FROM wp_biolink_clicks WHERE link_id = X GROUP BY variant_key`.
- Custom CSS is trusted input from `biolink_manage_pages` users — same model as the WordPress theme editor. No CSS parser, no per-property whitelist; the `</style>` strip is the only safety net.

## [2.3.1] - 2026-05-17

### Added
- **Copy-shortcode button** in the builder top bar (`</>` icon, sits between Publish and the QR launcher). Click → `[biolink id="N"]` is copied to the clipboard, the icon flips to a green check + "Copied!" tooltip for 1.8s. Tooltip on hover shows the exact shortcode so you can preview before clicking.

## [2.3.0] - 2026-05-17

### Added
- **`[biolink]` shortcode** — embed a published bio page inside any post / page / widget. Accepts `id="123"` or `slug="alvasti"`, plus `header="0"` to hide the avatar block. Output is wrapped in `.bio-embed.bio-embed-{id}` with theme CSS scoped to that selector, so each embed is independently themed and can coexist with the host page's styles.
- **`[biolink_block]` shortcode** — render a single block by uuid. `[biolink_block id="123" uuid="abc-…"]`. Useful for surfacing one product card / donation form / countdown without the whole bio header.
- **`ThemeEngine::renderStyleBlock( $slug, $settings, $selector )`** — third arg defaults to `body.bio-body` (full-page renders) but accepts any selector. Shortcodes pass `.bio-embed-{post_id}` so each embedded page is independently themed. The background rule omits `background-attachment: fixed` for embeds so they don't escape their container.
- **Shortcode-aware asset loading** — `Frontend\Shortcodes` enqueues `assets/frontend/biolink.{css,js}` on any post that contains `[biolink]` or `[biolink_block]` (via `has_shortcode`). Bio-page singulars already enqueue via `Assets`, so this only kicks in for embeds living elsewhere.
- **`.bio-embed` styling** in `assets/frontend/biolink.css` — max-width 560px, centered, subtle elevation, internal gap between blocks.

### Notes
- Locked blocks inside an embed still gate correctly (the placeholder + inline unlock modal both work in shortcode contexts).
- Click tracking, schedule windows, and visibility toggles all work in shortcode renders since they're enforced at `PageRenderer` level.
- Unpublished pages render as nothing on the public site; logged-in editors see an HTML comment explaining why.

## [2.2.2] - 2026-05-17

### Changed
- **Unlock UX is now fully inline.** Clicking a locked block opens a modal overlay on the same page (centered card, light/dark aware, escape/click-outside to dismiss). On correct passcode, the placeholder's `outerHTML` is replaced in place with the rendered block — no page reload, no separate `/?biolink_unlock=…` page. YouTube embeds, image galleries, donation forms etc. appear in-line and animate in.
- The standalone `template_redirect` unlock page from v2.2.0–v2.2.1 stays as a graceful-degradation path for visitors with JavaScript disabled.

### Added
- **`POST /biolink/v1/unlock/{page_id}/{uuid}`** — public REST endpoint. Body `{ passcode }`. Verifies via `wp_check_password`, sets the signed `biolink_unlocked` cookie, renders the unlocked block server-side via `PageRenderer`, returns `{ ok: true, html }`. Wrong passcode → 401.
- **`UnlockHandler::rememberUnlockForRequest()`** — public static so the REST endpoint can persist + inject the cookie token in the current request (so `PageRenderer` sees the unlock state when it renders the response HTML).
- **Modal styling in `assets/frontend/biolink.css`** — `.bio-unlock-overlay` / `.bio-unlock-modal` with fade + pop animations, honors `prefers-reduced-motion`.
- **Re-init pattern for swapped-in blocks.** `initYouTube` / `initCountdowns` / `initForms` / `initCheckout` all accept an optional `root` argument and skip elements already marked `data-bio-init`. After a swap, `enhance(newBlock)` re-initializes only the newly inserted DOM — no double-binding, no duplicate countdown ticks.
- Placeholder anchors now carry `data-biolink-unlock` / `data-biolink-page` / `data-biolink-uuid` so JS knows what to unlock.

## [2.2.1] - 2026-05-17

### Fixed
- **Passcode gate now applies to every block type, not just Link.** v2.2.0 only gated `LinkBlock`, so setting a passcode on a YouTube / Image Gallery / Donation / etc. block still rendered the content publicly. `PageRenderer` now checks `_passcode_hash` on every block and replaces the output with a generic "🔒 {label} — Click to unlock" placeholder card when the visitor hasn't unlocked it.

### Added
- **Signed unlock cookie** — `UnlockHandler` sets `biolink_unlocked` (HTTP-only, SameSite=Lax, 30 days) on successful passcode entry. Value is a comma-separated list of `wp_hash('biolink_unlock|page_id|uuid')` tokens — tamper-resistant, no DB writes. Subsequent visits skip the form for blocks already unlocked.
- **`UnlockHandler::isUnlocked( $page_id, $uuid )`** static helper, used by `PageRenderer` and `LinkBlock` to decide whether the gate should fire.
- **Public CSS for `.bio-block--locked-placeholder`** — dashed border, lock icon, theme-matched. Hovers lift like a normal link card.

### Changed
- **`UnlockHandler` destination logic generalized.** Looks for `url` or `cta_url` on the block; if found, unlock redirects to it (works for Link / Button / Donation / Product Card). Otherwise redirects back to the bio page so the now-unlocked embed/gallery/etc. renders inline.

## [2.2.0] - 2026-05-17

### Added
- **Passcode-gated links** — the Lock chip on every link row is now functional. Setting a passcode wraps the link with `?biolink_unlock={uuid}`; visitors land on a standalone unlock page (centered card, light/dark color-scheme aware) that requires the correct passcode before the 302 to the destination. Passcodes are stored hashed (`wp_hash_password`, phpass) — the plaintext never persists. Editing returns "Locked" without exposing the passcode; admins can update it or remove the lock entirely.
- **`Frontend\UnlockHandler`** — new `Bootable` hooked on `template_redirect`. Reads `?biolink_unlock=UUID` on bio pages, finds the block, renders the passcode form on GET, verifies via `wp_check_password` on POST, fires `do_action('biolink/link/unlocked', $uuid, $page_id)` on success.
- **`BlocksController::hashPasscode()`** — append + update paths inspect incoming `_passcode` plaintext, hash it, store as `_passcode_hash`, strip the plaintext. Sending `_passcode: ""` clears the lock.
- **`LinkBlock`** now renders a lock-icon indicator on locked rows (public page) and routes the href through the unlock URL when `_passcode_hash` is set. Locked links open in `_self` (not `_blank`) so the form lands cleanly. Click-tracking + UTM are bypassed for locked links — analytics on gated links is a v2.3 follow-up.

### Fixed
- **QR code button restored** — the v2.0.0 top-bar rewrite dropped the QR launcher. Added back to `BuilderShell` as an icon button next to View ↗. The `QrDialog` component itself was unchanged, just unreachable.

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
