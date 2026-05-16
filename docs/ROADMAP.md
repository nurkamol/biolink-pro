# Roadmap

All ten foundational phases shipped through v1.0.0. v2.x is the Linktree-class polish + feature track. This doc retains the original phase plan for historical context, then lists what v2 added and what's next.

**Status as of v2.3.1:** all foundational phases ✅. Plugin is mature. Recent work is incremental polish + new features.

---

## Phase 1 — Core bootstrap ✅

**Goal:** Plugin installs, activates cleanly, registers CPT + custom tables.

- [x] `plugin.php` bootstrap (version guards, constants, autoload)
- [x] Composer + `vendor/` (PSR-4 namespace `BioLinkPro\`)
- [x] `Core\Plugin` singleton + service container
- [x] `Core\Activator` / `Core\Deactivator` / `uninstall.php`
- [x] `Database\Migrator` + initial migrations
- [x] CPT `biolink_page` registered
- [x] Custom capabilities registered
- [x] PHPCS + Composer scripts + PHPUnit bootstrap
- [x] `readme.txt` for WP.org

**Exit:** activate plugin on fresh WP install, see CPT in admin menu, tables exist in DB, `WP_DEBUG=true` produces zero notices.

---

## Phase 2 — REST API + admin shell ✅

- [x] `Api\PagesController` with full CRUD + permission callbacks
- [x] `Api\BlocksController` (registry, append, reorder)
- [x] React app skeleton mounted at `Bio Links` admin page
- [x] `@wordpress/scripts` build pipeline + watch mode
- [x] Routing (Dashboard, Pages, Settings)
- [x] REST client wrapper with nonce handling

---

## Phase 3 — Block builder ✅

- [x] `Blocks\BlockRegistry` + `Blocks\AbstractBlock`
- [x] P1 blocks: `link`, `button`, `social_icons`, `image_gallery`, `rich_text`, `divider`, `video`, `youtube`
- [x] React canvas + inspector + toolbar (dnd-kit)
- [x] Frontend rewrite rule `/bio/{slug}` + template
- [x] `Frontend\PageRenderer` dispatches to block renderers
- [x] Mobile-first base stylesheet

---

## Phase 4 — Themes + remaining blocks ✅

- [x] `Themes\ThemeEngine` + 8 built-in presets (Mono, Linen, Velvet, Sunset, Aurora, Neon, Glass, Paper)
- [x] Custom CSS sanitizer
- [x] Theme picker in admin
- [x] P2 blocks: `spotify`, `tiktok`, `contact_form`, `faq`, `countdown`, `newsletter`, `product_card`, `donation`, `html_embed`, `map`
- [x] Font loader (Google Fonts via `@import`)
- [x] Background options (color, gradient, image with overlay)

---

## Phase 5 — Analytics ✅

- [x] `Analytics\Tracker` (async via single-event cron)
- [x] `Api\ClickController` (rate-limited redirect)
- [x] View beacon endpoint
- [x] Aggregation queries + dashboard charts
- [x] Summary, timeseries, geo, devices, top links, referrers views
- [x] CSV export
- [x] Daily prune job

---

## Phase 6 — QR codes + SEO ✅

- [x] `Qr\Generator` (uses `endroid/qr-code` via Composer)
- [x] QR style options (fg/bg color, format, size)
- [x] JSON-LD per page (`Person`, `WebPage`, `FAQPage` from FAQ blocks) via `Seo\StructuredData`
- [x] Sitemap entry via `wp_sitemaps` API
- [x] SEO coexistence layer — detects Yoast / Rank Math / SEOPress and pushes our values into their filters

OG image generator deferred (PHP→image dep chain not worth ~5MB on disk).

---

## Phase 7 — Integrations + monetization ✅

- [x] `Integrations\Stripe\Checkout` — real Stripe Checkout sessions (donation + product card)
- [x] `Integrations\PayPal\Checkout` + `ReturnHandler` — real PayPal Orders v2
- [x] **Dual-provider mode** — Stripe + PayPal on one block, Stripe primary
- [x] `Integrations\Mailchimp/MailerLite/Resend` adapters (no SDKs, `wp_remote_post` only)
- [x] UTM injection at click redirect
- [x] Stripe webhook listener with HMAC SHA-256 signature verification
- [x] Encrypted credential vault (libsodium via `Core\Crypto`)

---

## Phase 8 — AI module ✅

- [x] `Ai\ProviderRegistry` + `OpenAiProvider`
- [x] Endpoints `POST /ai/bio` / `/ai/cta` / `/ai/theme`
- [x] Rate limit via `Database\RateLimiter`
- [x] In-builder ✨ Suggest buttons in PageHeaderEditor, ButtonEditor label, ThemePicker

---

## Phase 9 — Templates + onboarding ✅

- [x] 6 bundled templates (Creator, Agency, Musician, Restaurant, Coach, Personal)
- [x] One-click apply via `POST /templates/{slug}/apply`
- [x] Onboarding wizard (first activation)
- [x] JSON import / export per page

---

## Phase 10 — Premium scaffolding ✅ (partial)

- [x] Update channel via `Updates\GitHubUpdater` (GitHub Releases, no separate update server)
- [x] In-app one-click updater (`POST /changelog/install-update`, v1.2.0)
- [ ] License system / subscription tiers — not pursued (plugin is GPL self-hosted)
- [ ] SaaS multi-tenant mode — deferred
- [ ] A/B testing — v2.4 candidate
- [ ] Custom domain (CNAME) — v2.4 candidate
- [ ] Team workspaces — deferred

---

## v2.x — Linktree-class polish

| Release | Date | Highlights |
|---|---|---|
| **2.0.0** | 2026-05-16 | Full admin redesign. Left sidebar nav, page-scoped routes (`/pages/:id/{links,design,shop,insights}`), sticky phone preview, Linktree-style link rows, card-based Design page, centralized Add modal, per-block `_active` visibility toggle. |
| **2.1.0** | 2026-05-17 | Functional chip row (thumbnail / highlight / schedule), polished SVG icon set, sidebar trimmed to working items. |
| **2.2.0** | 2026-05-17 | Passcode-gated links (`_passcode_hash` via `wp_hash_password`). |
| **2.2.1** | 2026-05-17 | Passcode gate extended to all block types (not just Link). Signed `biolink_unlocked` cookie persists unlocks for 30 days. |
| **2.2.2** | 2026-05-17 | Inline unlock modal — no redirect, content swaps in via `POST /unlock/{page}/{uuid}`. No-JS fallback retained. |
| **2.3.0** | 2026-05-17 | `[biolink id=…]` and `[biolink_block id=… uuid=…]` shortcodes. `ThemeEngine::renderStyleBlock()` gains selector arg so embeds are independently themed. |
| **2.3.1** | 2026-05-17 | Click-to-copy shortcode button in builder top bar. |

---

## v2.4+ candidates (unbooked)

In rough priority order:
- **A/B testing** — block-level variant pool, weighted random selection at render, click attribution per variant
- **Real Shop page UI** — currently a coming-soon stub; product collections + WooCommerce sync
- **Page revisions UI** — auto-snapshot on save + restore from history
- **Link scheduling rollups** — calendar view across all scheduled blocks per page
- **Custom domain mapping (CNAME)** — alias a domain to a specific bio page
- **Per-page custom CSS** field
- **Audience tab** — subscriber list, exports, opt-in/out tracking
- **Passcode-gated link analytics** — record unlocks via `do_action('biolink/link/unlocked')` hook (already fires; storage TBD)
- **Search/filter** on Pages list
- **OG image generator** — server-side render of social cards (deferred from Phase 6)
