# Database schema

## Strategy

- **CPT (`biolink_page`)** — one post per bio page. Built-in WP fields cover title, status, author, slug, dates.
- **Post meta `_biolink_data`** — JSON blob holding builder state (blocks, theme settings, SEO). Single key avoids meta-row sprawl.
- **Custom tables** — for high-cardinality + frequently-queried data (links, clicks, QR codes). Indexed for the access patterns described in `ARCHITECTURE.md`.

`BIOLINK_DB_VERSION` constant tracks schema version. `Database\Migrator` runs `dbDelta()` on activation and on version mismatch.

---

## Tables

### `{$prefix}biolink_links`
Individual link entries on a bio page. Separated from post meta so per-link analytics can join cleanly.

```sql
CREATE TABLE {$prefix}biolink_links (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id         BIGINT UNSIGNED NOT NULL,           -- FK → wp_posts.ID
    block_uuid      CHAR(36) NOT NULL,                  -- ties to block in JSON
    label           VARCHAR(255) NOT NULL,
    url             TEXT NOT NULL,
    icon            VARCHAR(64) DEFAULT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    start_at        DATETIME DEFAULT NULL,              -- schedule visibility
    end_at          DATETIME DEFAULT NULL,
    utm_params      VARCHAR(500) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY page_id (page_id),
    KEY block_uuid (block_uuid),
    KEY active_schedule (is_active, start_at, end_at)
) {$charset_collate};
```

### `{$prefix}biolink_clicks`
Per-click event log. Highest write volume — keep narrow, index for the dashboard queries.

```sql
CREATE TABLE {$prefix}biolink_clicks (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    link_id         BIGINT UNSIGNED NOT NULL,           -- FK → biolink_links.id
    page_id         BIGINT UNSIGNED NOT NULL,           -- denormalized for fast page rollups
    clicked_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_hash         CHAR(64) DEFAULT NULL,              -- sha256(IP + salt) — GDPR
    country         CHAR(2) DEFAULT NULL,               -- ISO 3166-1 alpha-2
    device          ENUM('desktop','mobile','tablet','bot','other') DEFAULT 'other',
    browser         VARCHAR(32) DEFAULT NULL,
    os              VARCHAR(32) DEFAULT NULL,
    referrer_host   VARCHAR(191) DEFAULT NULL,
    utm_source      VARCHAR(64) DEFAULT NULL,
    utm_medium      VARCHAR(64) DEFAULT NULL,
    utm_campaign    VARCHAR(64) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY link_id_clicked_at (link_id, clicked_at),
    KEY page_id_clicked_at (page_id, clicked_at),
    KEY clicked_at (clicked_at)                         -- for time-range scans
) {$charset_collate};
```

### `{$prefix}biolink_views`
Per-page-view event log. Same shape as clicks, no `link_id`. Separate table so click queries stay fast.

```sql
CREATE TABLE {$prefix}biolink_views (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id         BIGINT UNSIGNED NOT NULL,
    viewed_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_hash         CHAR(64) DEFAULT NULL,
    country         CHAR(2) DEFAULT NULL,
    device          ENUM('desktop','mobile','tablet','bot','other') DEFAULT 'other',
    browser         VARCHAR(32) DEFAULT NULL,
    os              VARCHAR(32) DEFAULT NULL,
    referrer_host   VARCHAR(191) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY page_id_viewed_at (page_id, viewed_at),
    KEY viewed_at (viewed_at)
) {$charset_collate};
```

### `{$prefix}biolink_qr`
QR code metadata. The image itself is generated on demand and cached in `uploads/biolink-pro/qr/`.

```sql
CREATE TABLE {$prefix}biolink_qr (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_id         BIGINT UNSIGNED NOT NULL,
    style_hash      CHAR(40) NOT NULL,                  -- sha1(serialized options)
    fg_color        CHAR(7) NOT NULL DEFAULT '#000000',
    bg_color        CHAR(7) NOT NULL DEFAULT '#FFFFFF',
    logo_attachment BIGINT UNSIGNED DEFAULT NULL,
    format          ENUM('png','svg') NOT NULL DEFAULT 'png',
    file_path       VARCHAR(255) DEFAULT NULL,
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY page_style (page_id, style_hash)
) {$charset_collate};
```

### `{$prefix}biolink_rate_limit`
Lightweight bucket store for per-IP rate limiting. Pruned by daily cron.

```sql
CREATE TABLE {$prefix}biolink_rate_limit (
    bucket_key      VARCHAR(128) NOT NULL,              -- e.g. "click:42:1.2.3.4"
    count           INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at      DATETIME NOT NULL,
    PRIMARY KEY (bucket_key),
    KEY expires_at (expires_at)
) {$charset_collate};
```

---

## Options keys

| Key | Type | Purpose |
|---|---|---|
| `biolink_db_version` | string | Schema version, drives migrations |
| `biolink_settings` | array | Global settings JSON |
| `biolink_integrations` | array | Provider credentials (encrypted at rest) |
| `biolink_onboarding_complete` | bool | Show wizard until true |

---

## Migration strategy

- `includes/Database/Migrator.php` is the single entry point
- Versioned migrations in `database/migrations/` named `001_create_links_table.php`, etc
- On `register_activation_hook` → run all pending migrations
- On every `plugins_loaded` → if `BIOLINK_DB_VERSION > get_option('biolink_db_version')` → run pending
- Each migration is idempotent and uses `dbDelta()` for schema changes
- Down-migrations not supported by default — encourage forward-only

---

## Uninstall cleanup (`uninstall.php`)

When user deletes the plugin:

1. Check `BIOLINK_KEEP_DATA` option — if `true`, abort (user opted in)
2. Drop all custom tables
3. Delete all `biolink_page` CPT entries + meta
4. Delete all options prefixed `biolink_`
5. Remove `uploads/biolink-pro/` directory
6. Clear scheduled cron events
