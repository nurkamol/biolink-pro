# Changelog

All notable changes to BioLink Pro are documented here.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
