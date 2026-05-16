# REST API

All endpoints are namespaced under `/wp-json/biolink/v1/`.

## Conventions

- Authentication: WordPress nonce (`X-WP-Nonce`) for admin endpoints; application passwords or JWT for headless consumers (Phase 4+).
- Permission callbacks: every write endpoint checks a custom capability (e.g. `biolink_manage_pages`).
- Responses: always `application/json` with `WP_REST_Response`.
- Errors: return `WP_Error` with HTTP status; codes prefixed `biolink_`.
- Rate limiting: public endpoints (clicks, form submits) gated through `Database\RateLimiter`.

---

## Pages

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/pages` | `biolink_manage_pages` | List pages (paginated) |
| GET    | `/pages/{id}` | `biolink_manage_pages` | Get full page (blocks + settings) |
| POST   | `/pages` | `biolink_manage_pages` | Create page |
| PATCH  | `/pages/{id}` | `biolink_manage_pages` | Update page |
| DELETE | `/pages/{id}` | `biolink_manage_pages` | Trash page |
| POST   | `/pages/{id}/duplicate` | `biolink_manage_pages` | Clone page |
| POST   | `/pages/{id}/publish` | `biolink_publish_pages` | Publish or schedule |

## Blocks

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/blocks` | `biolink_manage_pages` | Block registry (types + schema) |
| POST   | `/pages/{id}/blocks` | `biolink_manage_pages` | Append block |
| PATCH  | `/pages/{id}/blocks/{uuid}` | `biolink_manage_pages` | Update block |
| DELETE | `/pages/{id}/blocks/{uuid}` | `biolink_manage_pages` | Remove block |
| POST   | `/pages/{id}/blocks/reorder` | `biolink_manage_pages` | Reorder (array of uuids) |

## Themes

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/themes` | `biolink_manage_pages` | List built-in + custom themes |
| POST   | `/themes` | `biolink_manage_themes` | Create custom theme |
| PATCH  | `/themes/{slug}` | `biolink_manage_themes` | Update theme |
| DELETE | `/themes/{slug}` | `biolink_manage_themes` | Delete custom theme |

## Analytics

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/analytics/pages/{id}/summary` | `biolink_view_analytics` | Views/clicks rollup |
| GET    | `/analytics/pages/{id}/timeseries` | `biolink_view_analytics` | Day/week/month series |
| GET    | `/analytics/pages/{id}/links` | `biolink_view_analytics` | Top links |
| GET    | `/analytics/pages/{id}/geo` | `biolink_view_analytics` | Country breakdown |
| GET    | `/analytics/pages/{id}/devices` | `biolink_view_analytics` | Device/browser/os |
| GET    | `/analytics/export.csv` | `biolink_view_analytics` | CSV download |

Query params: `from`, `to` (ISO date), `granularity` (`day`/`week`/`month`).

## Public tracking (no auth)

| Method | Path | Purpose |
|---|---|---|
| GET    | `/click/{link_id}` | Record click + 302 redirect (rate-limited) |
| POST   | `/track/view` | Beacon view ping from frontend (rate-limited) |

## QR codes

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/pages/{id}/qr` | `biolink_manage_pages` | Get or generate QR (PNG/SVG) |
| POST   | `/pages/{id}/qr` | `biolink_manage_pages` | Update QR style options |

## AI (optional module)

| Method | Path | Cap | Purpose |
|---|---|---|---|
| POST   | `/ai/bio` | `biolink_use_ai` | Suggest bio description |
| POST   | `/ai/cta` | `biolink_use_ai` | Suggest CTA text |
| POST   | `/ai/theme` | `biolink_use_ai` | Suggest theme + palette |

## Templates

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/templates` | `biolink_manage_pages` | List bundled templates |
| POST   | `/templates/{slug}/apply` | `biolink_manage_pages` | Create page from template |

## Integrations

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/integrations` | `manage_options` | List status of providers |
| POST   | `/integrations/{slug}` | `manage_options` | Save credentials |
| DELETE | `/integrations/{slug}` | `manage_options` | Disconnect |

## Settings

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/settings` | `manage_options` | Read global settings |
| PATCH  | `/settings` | `manage_options` | Update global settings |

## Webhooks (Phase 5+)

| Method | Path | Cap | Purpose |
|---|---|---|---|
| GET    | `/webhooks` | `manage_options` | List webhooks |
| POST   | `/webhooks` | `manage_options` | Register URL + events |
| DELETE | `/webhooks/{id}` | `manage_options` | Remove |

Events: `page.published`, `page.updated`, `link.clicked`, `form.submitted`.

---

## Example payload — create page

```http
POST /wp-json/biolink/v1/pages
X-WP-Nonce: <nonce>
Content-Type: application/json

{
  "title": "My Bio",
  "slug": "my-bio",
  "theme": "minimal",
  "settings": {
    "avatar_id": 42,
    "headline": "Designer & Developer",
    "subheadline": "Building things for the web"
  },
  "blocks": [
    {
      "type": "link",
      "data": { "label": "Portfolio", "url": "https://…", "icon": "globe" }
    },
    {
      "type": "social_icons",
      "data": { "items": [{ "platform": "twitter", "url": "https://…" }] }
    }
  ]
}
```

## Example payload — track click

```http
GET /wp-json/biolink/v1/click/123?ref=instagram
→ 302 Location: https://destination.example/?utm_source=biolink
```
