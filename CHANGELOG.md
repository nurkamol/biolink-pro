# Changelog

All notable changes to BioLink Pro are documented here.
This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
