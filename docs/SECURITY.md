# Security checklist

> Every PR must pass this checklist. Reviewers reject changes that skip items here.

## Custom capabilities

Defined on activation by `Core\Capabilities`:

| Capability | Default roles | Controls |
|---|---|---|
| `biolink_manage_pages` | admin, editor | Create/update/delete bio pages |
| `biolink_publish_pages` | admin, editor | Publish or schedule |
| `biolink_manage_themes` | admin | Create/edit custom themes |
| `biolink_view_analytics` | admin, editor | View dashboards + export |
| `biolink_manage_integrations` | admin | Connect Stripe/PayPal/OpenAI/etc |
| `biolink_use_ai` | admin, editor | Call AI helper endpoints |

Never check `manage_options` for page-level actions — use a granular cap above. The admin role gets all caps by default; site owner can reassign via roles plugin.

## Input handling

### Sanitization (on save)

| Data | Sanitizer |
|---|---|
| Plain text | `sanitize_text_field()` |
| Slug | `sanitize_title()` |
| URL | `esc_url_raw()` (then validated against `wp_http_validate_url`) |
| Email | `sanitize_email()` + `is_email()` |
| Rich text | `wp_kses_post()` |
| HTML embed block | `wp_kses()` with custom whitelist + capability gate |
| Hex color | regex `/^#[0-9a-f]{6}$/i` |
| Integer | `absint()` |
| JSON block payload | decoded → schema-validated per block type |

### Escaping (on output)

| Context | Function |
|---|---|
| HTML body | `esc_html()` |
| Attribute | `esc_attr()` |
| URL | `esc_url()` |
| JavaScript value | `wp_json_encode()` then echo inside `<script>` |
| Translated string | `esc_html__()`, `esc_attr__()` |

## CSRF / nonces

- Every form: `wp_nonce_field('biolink_action_name')` + check on receive
- Every REST write: nonce passed via `X-WP-Nonce`, validated automatically when permission callback uses `current_user_can()`
- Every AJAX endpoint: `check_ajax_referer()` first line

## SQL

- All queries use `$wpdb->prepare()` with placeholders
- No `$wpdb->query()` with interpolated variables
- ORDER BY / column names — whitelist against allowed list, never accept raw input
- LIKE patterns wrapped via `$wpdb->esc_like()`

## File operations

- Uploads: use `wp_handle_upload()` + MIME whitelist
- QR images written to `uploads/biolink-pro/qr/` (created with `wp_mkdir_p`)
- Never `file_get_contents($_REQUEST[…])` or `include` user-supplied paths
- Image processing through `wp_get_image_editor()` (handles Imagick/GD safely)

## Outbound HTTP

- Always `wp_remote_get`/`wp_remote_post` (respects WP HTTP API filters)
- Timeout: 10s max for sync, 30s for cron jobs
- Validate response code before parsing body
- API keys read from options table only; never logged
- SSRF guard: reject private/loopback IPs for user-supplied URLs

## Rate limiting

`Database\RateLimiter` provides bucketed counters. Applied to:

| Endpoint | Window | Limit |
|---|---|---|
| `/click/{id}` | 60s | 10 per IP per link |
| `/track/view` | 60s | 30 per IP per page |
| `/ai/*` | 60s | 10 per user |
| Contact form submit | 300s | 5 per IP |

Bot traffic (matching common UA patterns) is silently dropped before counting.

## GDPR

- IP addresses **hashed** (`sha256(ip + site_salt)`) before storage — never raw
- Hashes are per-site (different salts → can't cross-correlate)
- `Personal Data Exporter` hook registered: exports clicks, views, form submissions by email or IP hash on request
- `Personal Data Eraser` hook registered: deletes matching records
- Cookie banner integration: respects consent state via `biolink/consent/allowed` filter
- Retention setting: auto-delete analytics events older than N days (default 365)

## Secrets at rest

- API keys stored in `wp_options` encrypted with `Core\Crypto`
- Encryption uses `sodium_crypto_secretbox` with a key derived from `AUTH_KEY` (already in `wp-config.php`)
- Keys never echoed back to admin UI — show last 4 chars only

## Embed sandbox

- iframes for YouTube/TikTok/Spotify get `sandbox="allow-scripts allow-same-origin allow-popups"`
- `referrerpolicy="strict-origin-when-cross-origin"`
- `loading="lazy"` always

## Disabled in production

These PHP functions/features are explicitly banned:
- `eval()`, `assert()` with string arg, `create_function()`
- `extract()` on user input
- `unserialize()` on untrusted data — use `json_decode()`
- `preg_replace` with `/e` modifier
- `system()`, `exec()`, `shell_exec()`, `passthru()`

## Activation hardening

On activation, `Core\Activator` checks:
- PHP >= 8.2 (deactivate + admin notice if not)
- WP >= 6.5
- `sodium_*` functions available (warn if not — encryption disabled)
- Required directories writable (`uploads/biolink-pro/`)

## Security audit cadence

- Before each minor release: run `phpcs` with `WordPress-Security` ruleset
- Before each major release: dependency audit (`composer audit`, `npm audit`)
- Quarterly: review all REST permission callbacks
