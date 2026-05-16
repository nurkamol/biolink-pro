# Changelog

All notable changes to BioLink Pro are documented here.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
