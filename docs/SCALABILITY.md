# Scalability & performance

## Targets

| Metric | Target |
|---|---|
| Lighthouse Mobile Performance | >= 90 |
| Largest Contentful Paint | < 1.8s on 4G |
| Total bundle (frontend) | < 30 KB JS gzipped |
| Time to first byte (cached) | < 200 ms |
| Concurrent published pages | 100k+ |
| Click writes per second | 500+ |
| Plugin memory footprint | < 8 MB |

## Frontend rules

- **No React on bio pages** — server-render HTML, ship < 30 KB JS
- **Critical CSS inlined** in `<head>` (8 KB budget per theme)
- **Non-critical CSS** loaded with `media="print" onload="this.media='all'"` pattern
- **JS lazy-loaded** per block (only when block exists on page)
- **Images**: `srcset` + WebP + `loading="lazy"` + explicit `width`/`height`
- **Fonts**: `font-display: swap`, `preconnect` to provider, subset where possible
- **Embeds**: facade pattern (lite-youtube etc.) — load real iframe only on interaction

## Caching layers

1. **Page object cache** — transient `biolink_page_{slug}`, 5 min, busted on save
2. **Rendered HTML cache** — optional, full page output cached if no dynamic blocks present
3. **Object cache** — relies on `WP_Object_Cache` (Redis/Memcached recommended in production)
4. **Browser cache** — `Cache-Control: public, max-age=300, s-maxage=3600` on bio pages
5. **CDN** — pages are CDN-friendly (vary only on `Accept-Encoding`)

## DB query hygiene

- All page-load queries traced via `Query Monitor` during dev; budget < 10 queries
- Indexed columns documented in `DATABASE.md`
- Joins kept narrow; analytics queries restricted by date range
- Heavy aggregations run via cron → cached summary table (Phase 5)

## Analytics write path

Click events have the highest write volume. Strategy:

1. Inline write blocks redirect → unacceptable latency at scale
2. Single-event cron (`wp_schedule_single_event(time(), …)`) → batched into background
3. For sites > 1M clicks/month: optional queue adapter (Redis list) — Phase 10

Reads aggregated daily into a `biolink_analytics_daily` rollup table (built in Phase 5):

```sql
CREATE TABLE {$prefix}biolink_analytics_daily (
    page_id   BIGINT UNSIGNED NOT NULL,
    date      DATE NOT NULL,
    views     INT UNSIGNED NOT NULL DEFAULT 0,
    clicks    INT UNSIGNED NOT NULL DEFAULT 0,
    unique_v  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (page_id, date)
);
```

Dashboards query rollup; live counts only for "today".

## Asset pipeline

- `@wordpress/scripts` produces hashed filenames → infinite cache
- Per-block stylesheets registered, enqueued on demand via `Frontend\AssetLoader::need('block-video')`
- Tree-shaken admin bundle; vendor chunk split
- SVG icons inlined (no icon font)

## Cron + background jobs

| Job | Frequency | Purpose |
|---|---|---|
| `biolink/rollup_daily` | daily 02:00 site time | aggregate analytics |
| `biolink/prune_events` | daily 03:00 | delete events beyond retention |
| `biolink/prune_rate_limits` | hourly | clean expired buckets |
| `biolink/refresh_qr_cache` | weekly | regenerate stale QR files |

For sites without reliable `wp-cron`, settings page shows a CLI command to run via system cron:
```
*/5 * * * * php /path/to/wp-cli.phar --path=/path/to/wp cron event run --due-now
```

## Multisite

- Tables created **per site** (network activation iterates sites)
- Options scoped to site (no network options except license, plugin version)
- Network admin sees aggregate usage across sites (Phase 10)

## Shared-hosting fallbacks

- No `proc_open`/`exec` calls anywhere
- Image processing detects available libs (Imagick → GD → fail gracefully)
- QR generation: pure PHP fallback if `gd` unavailable
- AI calls: short timeout + queued retry if first attempt times out
- Detect `disable_functions` on activation; show advisory in admin

## Monitoring hooks

Filters for ops integration:

```php
apply_filters('biolink/metrics/log', $event, $context);
do_action('biolink/error', $exception, $context);
```

Default no-op. Site owner can wire to New Relic, Datadog, Sentry via small mu-plugin.
