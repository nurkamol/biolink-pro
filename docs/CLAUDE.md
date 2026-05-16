# BioLink Pro — AI agent / contributor instructions

> Read this file at the start of every session. Update the phase status line below when moving between phases.

**Current release:** v2.3.1 — copy-shortcode button in builder top bar
**Active surface:** mature plugin. All 10 original phases shipped; v2.0–v2.3 added the Linktree-class admin redesign, functional row chips (thumbnail/highlight/schedule/lock), inline passcode unlock modal, `[biolink]` / `[biolink_block]` shortcodes, and the in-app updater.
**Next candidates (no commitment):** A/B testing module, per-page revisions UI, link scheduling rollups, custom domain CNAME, real Shop UI (currently a stub in v2.0+).
**Decision (Phase 1):** Admin React app uses **CSS Modules** (not Tailwind).
**Release channel:** GitHub Releases on `nurkamol/biolink-pro`. Stable tags only (`vX.Y.Z`). Updater = `BioLinkPro\Updates\GitHubUpdater`. See `bin/build-release.sh` for local builds; `.github/workflows/release.yml` runs on tag push. In-app one-click updater lives at `Api\ChangelogController::installUpdate` (`POST /biolink/v1/changelog/install-update`).
**Docs index:** `docs/` (start with `PROMPT.md` then `ARCHITECTURE.md`). `CHANGELOG.md` is the source of truth for what shipped when.

---

## What this plugin is

A self-hosted WordPress alternative to Linktree / Beacons / Carrd. Mobile-first bio pages built with a drag-and-drop block builder. Analytics, themes, QR codes, monetization, optional AI helpers. See `PROMPT.md` for the full brief.

---

## Stack — non-negotiable

```
PHP                     8.2+
WordPress core          6.5+ (latest)
React (admin)           18.x
Build tool              @wordpress/scripts (wp-scripts) — webpack under the hood
Styling                 CSS modules OR Tailwind (decide in Phase 1; pick ONE)
REST API                WordPress core REST API + custom namespaces
Composer                PSR-4 autoload, namespaced under `BioLinkPro\`
i18n                    text domain: `biolink-pro`
DB                      WordPress core (CPT) + custom tables for analytics + links
```

Do **not** add jQuery unless absolutely required. Do not pull in heavyweight admin frameworks (no Vue, no Bootstrap). Keep the frontend output framework-free (vanilla JS, lazy-hydrated).

---

## Coding standards

### PHP
- WordPress Coding Standards (WPCS) enforced via `phpcs.xml`
- PHP 8.2+ features allowed: readonly properties, enums, first-class callable syntax
- Strict types in every file: `declare(strict_types=1);`
- One class per file, namespace mirrors folder path
- No `global $wpdb` inside controllers — inject through a repository class
- Every public method gets a docblock with `@param`, `@return`, `@throws`

### JavaScript / React (admin)
- Functional components, hooks only — no class components
- TypeScript preferred; if JS, use JSDoc types
- One component per file, named export
- Co-locate styles with components (`Button/Button.tsx` + `Button/Button.module.css`)
- State management: React Context + `useReducer` for global; `@wordpress/data` for WP integration

### CSS (frontend)
- Plain CSS or CSS modules; no inline styles except truly dynamic values
- BEM kebab-case class naming: `.bio-link__card--featured`
- CSS custom properties for theme tokens (`--bio-color-primary`, `--bio-space-md`)
- Mobile-first media queries (`min-width` only)

---

## Security — every PR must satisfy

1. **Nonce verification** on every state-changing request (form, AJAX, REST write)
2. **Capability check** on every admin action — define custom caps (`biolink_manage_pages`, `biolink_view_analytics`)
3. **Sanitize on input, escape on output** — never trust `$_GET`/`$_POST`/`$_REQUEST`
4. **Prepared statements** — `$wpdb->prepare()` for every query with variables
5. **REST permission callbacks** — never use `__return_true` outside of public read endpoints
6. **Rate limiting** on public POST endpoints (link clicks, form submissions)
7. **GDPR**: anonymize IPs in analytics, provide data export + erasure hooks

Full checklist in `SECURITY.md`.

---

## Folder → namespace mapping

```
includes/Core/        → BioLinkPro\Core\
includes/Admin/       → BioLinkPro\Admin\
includes/Frontend/    → BioLinkPro\Frontend\
includes/Api/         → BioLinkPro\Api\
includes/Blocks/      → BioLinkPro\Blocks\
includes/Analytics/   → BioLinkPro\Analytics\
includes/Database/    → BioLinkPro\Database\
includes/Integrations/→ BioLinkPro\Integrations\
includes/Themes/      → BioLinkPro\Themes\
includes/Ai/          → BioLinkPro\Ai\
```

---

## Never do

- Never edit core WordPress files
- Never write SQL without `$wpdb->prepare()`
- Never trust user input — sanitize / validate / escape
- Never use `eval()`, `extract()`, `create_function()`
- Never commit `.env` files or API keys
- Never load assets globally — enqueue only on pages that need them
- Never use `wp_remote_get` without timeout + error handling
- Never use `serialize()` for data that may be queried — use JSON
- Never break backward compat without a migration

---

## Build + dev commands (once Phase 1 lands)

```bash
composer install
npm install
npm run dev        # watch admin React build
npm run build      # production build
composer test      # PHPUnit
composer lint      # PHPCS (WordPress standards)
npm run lint       # ESLint + Stylelint
```

---

## Definition of done (per feature)

- [ ] Code follows WPCS + project conventions
- [ ] Sanitization + escaping verified
- [ ] Nonce + capability checks in place
- [ ] Unit tests for non-trivial logic
- [ ] Translation strings wrapped (`__()`, `_e()`, `esc_html__()`)
- [ ] No PHP notices/warnings on `WP_DEBUG=true`
- [ ] Asset enqueue scoped to relevant pages only
- [ ] Inline docblocks for public methods
- [ ] Entry added to `CHANGELOG.md`

---

## Release workflow (v2.x established pattern)

1. Land changes on `main`, ensure `npm run build` produces a fresh `assets/admin/main.*` bundle and **commit the built files** (the WP plugin install doesn't run npm).
2. PHPUnit + PHP lint must pass: `vendor/bin/phpunit` (60 tests) and `php -l` on touched files.
3. Bump four version markers in lockstep:
   - `plugin.php` header `Version:` + `BIOLINK_VERSION` constant
   - `package.json` `version`
   - `readme.txt` `Stable tag:`
4. Add a `CHANGELOG.md` entry under `## [Unreleased]` → renamed to `## [X.Y.Z] - YYYY-MM-DD` on tag.
5. Add a matching `readme.txt` `== Changelog ==` short entry.
6. Commit (no Claude / Co-Authored-By attribution — user preference).
7. `git tag -a vX.Y.Z -m "…"` then push both `main` and the tag. The GitHub Actions release workflow builds + publishes the `biolink-pro-vX.Y.Z.zip` asset.
8. Verify the release with `gh release view vX.Y.Z`.

---

## Per-block extension keys (admin chip row → block.data._*)

These leading-underscore keys are stored on individual block data and honored at render time by `Frontend\PageRenderer` and `Blocks\Types\LinkBlock`:

| Key | Type | Read by | Notes |
|---|---|---|---|
| `_active` | bool | `PageRenderer` | `false` → block is skipped entirely |
| `_highlight` | bool | `PageRenderer` | wraps output in `bio-block--highlight` (CSS pulse) |
| `_thumbnail_id` | int | `LinkBlock` | WP attachment ID; renders as 36×36 rounded square next to label |
| `_start_at` / `_end_at` | ISO string | `PageRenderer::isScheduleActive()` | site-local time; outside-window blocks are skipped |
| `_passcode_hash` | string | `PageRenderer` / `LinkBlock` | `wp_hash_password`; plaintext (`_passcode`) is hashed by `BlocksController::hashPasscode()` on save, never persisted or returned |

Visibility cookie for passcode unlocks: `biolink_unlocked` (HMAC tokens via `wp_hash`), HttpOnly, SameSite=Lax, 30 days. See `Frontend\UnlockHandler::tokenFor()`.
