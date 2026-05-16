# Architecture

## High-level diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         WordPress Host                          │
│                                                                 │
│  ┌────────────────┐    ┌──────────────────┐   ┌──────────────┐  │
│  │  /wp-admin     │    │  /bio/{slug}     │   │  /wp-json/   │  │
│  │  React app     │◄──►│  Frontend SSR    │◄──┤  biolink/v1/ │  │
│  │  (Builder UI)  │    │  (PHP templates) │   │  REST API    │  │
│  └────────┬───────┘    └────────┬─────────┘   └──────┬───────┘  │
│           │                     │                    │          │
│           └──────────┬──────────┴────────┬───────────┘          │
│                      ▼                   ▼                      │
│         ┌─────────────────────┐  ┌─────────────────────┐        │
│         │ Core Domain Layer   │  │ Integration Layer   │        │
│         │ • PageRepository    │  │ • Stripe / PayPal   │        │
│         │ • BlockRegistry     │  │ • Mailchimp / etc.  │        │
│         │ • ThemeEngine       │  │ • OpenAI            │        │
│         │ • UnlockHandler     │  │ • GitHubUpdater     │        │
│         │ • Shortcodes        │  │                     │        │
│         └──────────┬──────────┘  └─────────────────────┘        │
│                    ▼                                            │
│         ┌─────────────────────┐                                 │
│         │ Persistence         │                                 │
│         │ • CPT biolink_page  │                                 │
│         │ • wp_biolink_links  │                                 │
│         │ • wp_biolink_clicks │                                 │
│         │ • wp_biolink_views  │                                 │
│         │ • wp_biolink_qr     │                                 │
│         │ • wp_biolink_rate_limit                               │
│         │ • Options + transients                                │
│         └─────────────────────┘                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Module map

| Namespace | Purpose | Source |
|---|---|---|
| `BioLinkPro\Core` | Bootstrap, autoload, service container, activator/deactivator, capabilities, crypto | `includes/Core/` |
| `BioLinkPro\Admin` | wp-admin menu registration, admin asset enqueue, plugin action links | `includes/Admin/` |
| `BioLinkPro\Frontend` | `PageRenderer`, `Assets`, `TemplateLoader`, `UnlockHandler`, `Shortcodes`, CPT registration, page repository | `includes/Frontend/` |
| `BioLinkPro\Api` | 16 REST controllers; all routes under `/wp-json/biolink/v1/` | `includes/Api/` |
| `BioLinkPro\Blocks` | `BlockRegistry`, `AbstractBlock`, 18 block types under `Types/`, schema validator, icon library | `includes/Blocks/` |
| `BioLinkPro\Database` | `Migrator`, `RateLimiter`, table schemas | `includes/Database/` |
| `BioLinkPro\Analytics` | `Tracker`, `Reporter`, `LinkSync` (mirrors block JSON → `wp_biolink_links` table) | `includes/Analytics/` |
| `BioLinkPro\Themes` | `ThemeEngine` + 8 built-in `Preset` instances, scoped style emitter | `includes/Themes/` |
| `BioLinkPro\Integrations` | `Stripe\Checkout`, `PayPal\Checkout` + `ReturnHandler`, `Mailchimp/MailerLite/Resend` adapters | `includes/Integrations/` |
| `BioLinkPro\Ai` | `ProviderRegistry`, `OpenAiProvider`, bio/CTA/theme prompt scaffolding | `includes/Ai/` |
| `BioLinkPro\Qr` | `Generator` wrapping `endroid/qr-code` | `includes/Qr/` |
| `BioLinkPro\Templates` | Bundled template library (6 presets) + apply pipeline | `includes/Templates/` |
| `BioLinkPro\Seo` | `MetaTags`, `StructuredData`, `Sitemap`, Yoast / Rank Math / SEOPress coexistence | `includes/Seo/` |
| `BioLinkPro\Updates` | `GitHubUpdater`, `MarkdownRenderer`, hooks into `pre_set_site_transient_update_plugins` / `plugins_api` / `upgrader_source_selection` | `includes/Updates/` |
| `BioLinkPro\Onboarding` | First-activation wizard state | `includes/Onboarding/` |
| `BioLinkPro\Cron` | `Pruner` (rate-limit + analytics retention) | `includes/Cron/` |

All compiled admin output lives in `assets/admin/` (built from `admin/src/`). Public stylesheet + progressive-enhancement JS live in `assets/frontend/`.

## Bootstrap flow (`plugin.php`)

1. Guard: PHP 8.2+, WP 6.5+, `defined('ABSPATH')`
2. Define constants: `BIOLINK_VERSION`, `BIOLINK_PATH`, `BIOLINK_URL`, `BIOLINK_FILE`, `BIOLINK_BASENAME`, `BIOLINK_MIN_PHP`, `BIOLINK_MIN_WP`, `BIOLINK_DB_VERSION`
3. Composer autoload (`vendor/autoload.php`)
4. Register activation / deactivation / uninstall hooks
5. On `plugins_loaded` priority 5 → `BioLinkPro\Core\Plugin::instance()->boot()`
6. `Plugin::boot()` wires up: CPT registration, REST routes, admin menu, frontend rewrite rules, block registry, asset enqueue, shortcodes, unlock handler, all integrations, AI providers, theme engine, GitHubUpdater.

## Custom post type

- **`biolink_page`** — one CPT, one post per bio page. Public, rewrite slug `/bio/{post_slug}`, supports title, author, revisions.
- All builder data (theme, settings, blocks, seo) stored as post meta JSON under `_biolink_data` to avoid meta-key sprawl.

## Custom tables

| Table | Purpose |
|---|---|
| `wp_biolink_links` | Block-JSON-mirrored link entries so analytics can join on a stable `link_id` |
| `wp_biolink_clicks` | Per-click event log (high write volume, time-indexed) |
| `wp_biolink_views` | Per-page-view event log |
| `wp_biolink_qr` | Generated QR metadata, points at cached image in `uploads/biolink-pro/qr/` |
| `wp_biolink_rate_limit` | Per-IP bucket store for click / form / AI throttling |

Schema details in `DATABASE.md`.

## Request lifecycle — public bio page

```
GET /bio/{slug}
  → WP rewrite resolves to `biolink_page` template
  → TemplateLoader::loadBioTemplate() → templates/bio-page.php
  → PageRenderer::renderPage(post)
      ├── PageRepository::findById(post.ID)
      ├── ThemeEngine::renderStyleBlock(theme, settings)  → inline <style>
      ├── PageRenderer::renderHeader(post, settings)
      └── PageRenderer::renderBlocks(blocks)
            └── for each block:
                  – skip if _active === false
                  – skip if outside _start_at / _end_at window
                  – if _passcode_hash present + not unlocked + non-Link → render placeholder
                  – else BlockRegistry::get(type)::render(data, uuid?)
                  – apply biolink/block/render filter
                  – if _highlight → wrap in <div class="bio-block bio-block--highlight">
  → Beacon view ping (POST /track/view) fires from frontend JS
```

## Request lifecycle — link click

```
GET /wp-json/biolink/v1/click/{link_id}?ref=...
  → ClickController::track(link_id)
      ├── rate limit check (IP + link_id, 60s window via Database\RateLimiter)
      ├── Analytics\Tracker::recordClick(link_id, headers)
      │     └── apply biolink/click/before filter (short-circuit hook)
      └── 302 redirect to destination URL (with UTM params from utm_params column)
```

## Request lifecycle — passcode unlock (inline)

```
POST /wp-json/biolink/v1/unlock/{page_id}/{uuid}  body: { passcode }
  → UnlockController::unlock(request)
      ├── PageRepository::findById → locate block by uuid
      ├── wp_check_password(submitted, block._passcode_hash, 0)
      ├── on success: UnlockHandler::rememberUnlockForRequest(page_id, uuid)
      │     → setcookie biolink_unlocked (HMAC token, HttpOnly, SameSite=Lax, 30d)
      │     → also inject into $_COOKIE for the current request
      ├── do_action('biolink/link/unlocked', uuid, page_id)
      └── PageRenderer::renderBlocks([block]) with cookie present → unlocked HTML
  → JSON { ok: true, html }
  → Frontend JS swaps placeholder.outerHTML, calls enhance(newBlock) to re-init facades
```

A no-JS fallback path exists via `?biolink_unlock={uuid}` on the bio page URL, handled by `UnlockHandler::maybeUnlock()` on `template_redirect`.

## Request lifecycle — shortcode render

```
[biolink id="123"] inside a regular post
  → wp parses post_content, calls Shortcodes::renderPage(atts)
      ├── PageRepository::findById(123)
      ├── ThemeEngine::renderStyleBlock(theme, settings, '.bio-embed-123')
      │     → CSS vars scoped to the embed wrapper, not body
      ├── temporarily set $GLOBALS['post'] so get_the_ID() inside blocks works
      ├── PageRenderer::renderHeader + renderBlocks
      └── wrap in <div class="bio-embed bio-embed-123 bio-page bio-theme-{slug}">…</div>
  → assets/frontend/biolink.{css,js} enqueued via has_shortcode() check
```

## React admin app (`admin/src/`)

```
admin/src/
├── main.tsx                           ← mount + submenu → hash routing
├── App.tsx                            ← HashRouter + AppShell + routes
├── styles/globals.css                 ← design tokens (Linktree-flavored cream)
├── api/client.ts                      ← @wordpress/api-fetch wrapper + typed APIs
├── components/
│   ├── ui/
│   │   ├── AppShell.tsx               ← left sidebar + page selector + nav
│   │   ├── AddBlockModal.tsx          ← centralized inserter (category rail)
│   │   ├── ComingSoon.tsx
│   │   └── Icons.tsx                  ← shared SVG icon set (no icon library)
│   ├── builder/
│   │   ├── LivePreview.tsx            ← phone-frame iframe
│   │   ├── PageHeaderEditor.tsx
│   │   ├── BackgroundEditor.tsx
│   │   ├── SeoEditor.tsx
│   │   ├── ThemePicker.tsx
│   │   └── QrDialog.tsx
│   ├── ai/
│   │   └── AiSuggestButton.tsx        ← ✨ Suggest control reused across editors
│   └── onboarding/
│       └── OnboardingOverlay.tsx
├── blocks/
│   ├── index.ts                       ← 18-block React catalog (BlockMeta[])
│   └── {Type}Editor.tsx               ← 18 inline inspectors
└── pages/
    ├── Dashboard.tsx
    ├── Pages.tsx                      ← multi-page list
    ├── Analytics.tsx                  ← account-wide analytics
    ├── Changelog.tsx                  ← What's New + in-app updater
    ├── Settings.tsx
    └── builder/
        ├── BuilderShell.tsx           ← page-scoped routes wrapper, top bar, phone preview
        ├── BuilderContext.tsx
        ├── LinksPage.tsx              ← Linktree-style link rows + chip row
        ├── DesignPage.tsx             ← card-based theme/header/wallpaper/buttons/text editor
        ├── ShopPage.tsx               ← v2.0+ stub
        └── InsightsPage.tsx           ← per-page analytics (wraps Analytics with initialPageId)
```

Build output → `assets/admin/main.{js,css}` enqueued on plugin admin pages only.

## Frontend output strategy

- **Server-rendered HTML first** — full bio page renders without JS
- **Progressive enhancement** — small vanilla `assets/frontend/biolink.js` (~10 KB) for YouTube facades, countdowns, TikTok script lazy-load, newsletter/contact form submit, Stripe/PayPal checkout redirect, view beacon, inline unlock modal
- **No React on the frontend** — keeps payload tiny
- **CSS** — single `assets/frontend/biolink.css` with theme variables emitted inline per page
- **Images** — `loading="lazy"`, `srcset` via WordPress core
- **Idempotent re-init** — `data-bio-init` flag on each enhanced element so the unlock-swap path can call `enhance(newBlock)` without double-binding

## Extensibility surface

Every module exposes filters + actions under the `biolink/{module}/{event}` naming convention. See `EXTENSIONS.md` for the full reference.
