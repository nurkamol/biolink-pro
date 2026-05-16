# Architecture

## High-level diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         WordPress Host                          │
│                                                                 │
│  ┌────────────────┐    ┌──────────────────┐   ┌──────────────┐  │
│  │  /wp-admin     │    │  /bio/{slug}     │   │  /wp-json/   │  │
│  │  React app     │◄──►│  Frontend SSR    │   │  biolink/v1/ │  │
│  │  (Builder UI)  │    │  (PHP templates) │   │  REST API    │  │
│  └────────┬───────┘    └────────┬─────────┘   └──────┬───────┘  │
│           │                     │                    │          │
│           └──────────┬──────────┴────────┬───────────┘          │
│                      ▼                   ▼                      │
│         ┌─────────────────────┐  ┌─────────────────────┐        │
│         │ Core Domain Layer   │  │ Integration Layer   │        │
│         │ • PageRepository    │  │ • Stripe / PayPal   │        │
│         │ • BlockRegistry     │  │ • OpenAI            │        │
│         │ • ThemeEngine       │  │ • Email providers   │        │
│         │ • AnalyticsService  │  │                     │        │
│         └──────────┬──────────┘  └─────────────────────┘        │
│                    ▼                                            │
│         ┌─────────────────────┐                                 │
│         │ Persistence         │                                 │
│         │ • CPT biolink_page  │                                 │
│         │ • wp_biolink_clicks │  (custom)                       │
│         │ • wp_biolink_links  │  (custom)                       │
│         │ • Options + transient cache                           │
│         └─────────────────────┘                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Module map

| Module | Purpose | Lives in |
|---|---|---|
| **Core** | Bootstrap, autoload, service container, activation/deactivation | `includes/Core/` |
| **Admin** | React app entry, menu pages, asset enqueue | `admin/` + `includes/Admin/` |
| **Frontend** | Bio page renderer, block dispatcher, asset loader | `frontend/` + `includes/Frontend/` |
| **Api** | REST controllers, request validation, permission callbacks | `api/` + `includes/Api/` |
| **Blocks** | Block registry, individual block classes (`LinkBlock`, `VideoBlock`, …) | `blocks/` + `includes/Blocks/` |
| **Database** | Migrations, custom table schemas, query repositories | `database/` + `includes/Database/` |
| **Analytics** | Click tracking, aggregation, dashboards | `analytics/` + `includes/Analytics/` |
| **Themes** | Theme presets, CSS variable injection, custom CSS sanitizer | `themes/` + `includes/Themes/` |
| **Integrations** | Stripe, PayPal, OpenAI, email | `integrations/` + `includes/Integrations/` |
| **Ai** | Bio/CTA/theme suggestions, provider abstraction | `ai/` + `includes/Ai/` |
| **Modules** | Optional opt-in features (a/b testing, automation) | `modules/` |

## Bootstrap flow (`plugin.php`)

1. Guard: PHP 8.2+, WP 6.5+, `defined('ABSPATH')`
2. Define constants: `BIOLINK_VERSION`, `BIOLINK_PATH`, `BIOLINK_URL`, `BIOLINK_DB_VERSION`
3. Composer autoload (`vendor/autoload.php`)
4. Register activation/deactivation/uninstall hooks
5. Instantiate `BioLinkPro\Core\Plugin` singleton on `plugins_loaded`
6. `Plugin::boot()` wires up: CPT registration, REST routes, admin menu, frontend rewrite rules, block registry, asset enqueue

## Custom post type

- **`biolink_page`** — one CPT, one post per bio page. Public, has its own rewrite slug (`/bio/{post_slug}`), supports title, author, revisions.
- All builder data (blocks, theme, settings) stored as post meta JSON under `_biolink_data` to avoid meta-key sprawl.

## Custom tables

| Table | Why custom (not meta/CPT) |
|---|---|
| `wp_biolink_links` | Many-to-one with page, frequent reads, indexed for sort/filter |
| `wp_biolink_clicks` | High write volume, needs date + page indexes, would bloat `postmeta` |
| `wp_biolink_qr` | Generated QR metadata, optional logo refs |

Schema details in `DATABASE.md`.

## Request lifecycle — public bio page

```
GET /bio/{slug}
  → WP rewrite resolves to template
  → BioPageController::render(slug)
      ├── PageRepository::findBySlug(slug)  [transient cache 5 min]
      ├── ThemeEngine::resolve(page.theme)  → emits CSS vars
      ├── PageBuilder::render(page.blocks)
      │     └── for each block → BlockRegistry::get(type)::render(data)
      └── AnalyticsService::trackView(page.id, request)  [async via wp_schedule_single_event]
  → output minimal HTML (no admin assets, no jQuery)
```

## Request lifecycle — link click

```
GET /bio/click/{link_id}?ref=...
  → ClickController::track(link_id)
      ├── rate limit check (IP + link_id, 60s window)
      ├── AnalyticsService::recordClick(link_id, headers)
      └── 302 redirect to destination URL
```

## React admin app

```
admin/
├── src/
│   ├── main.tsx                   ← mount point, react-dom/client
│   ├── App.tsx                    ← routing (react-router or @wordpress/router)
│   ├── pages/
│   │   ├── Dashboard.tsx
│   │   ├── PageBuilder.tsx        ← drag-drop editor (dnd-kit)
│   │   ├── Analytics.tsx
│   │   ├── Themes.tsx
│   │   └── Settings.tsx
│   ├── components/
│   │   ├── blocks/                ← admin-side block editors
│   │   ├── ui/                    ← primitives
│   │   └── builder/               ← canvas, inspector, toolbar
│   ├── api/                       ← REST client (uses @wordpress/api-fetch)
│   ├── store/                     ← state (Context + reducer, or @wordpress/data)
│   └── hooks/
└── webpack.config.js              ← wraps @wordpress/scripts
```

Build output → `assets/admin/` enqueued only on plugin admin pages.

## Frontend output strategy

- **Server-rendered HTML first** — full bio page renders without JS
- **Progressive enhancement** — small vanilla JS bundle for analytics, lazy-loaded embeds, smooth scroll
- **No React on the frontend** — keeps payload < 30 KB compressed
- **CSS** — one inlined critical block + one async stylesheet per theme
- **Images** — `loading="lazy"`, `srcset`, WebP via `wp_get_attachment_image`

## Extensibility surface

Every module exposes filters + actions. Naming convention: `biolink/{module}/{event}`.

Examples:
- `biolink/blocks/register` — add custom block types
- `biolink/themes/register` — register a theme preset
- `biolink/analytics/before_track` — short-circuit tracking
- `biolink/ai/providers` — register a new AI provider
- `biolink/page/render/before` — inject markup before block stream
