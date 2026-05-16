# Roadmap — phased delivery

Each phase is a shippable slice. Don't start phase N+1 until phase N's exit criteria are met.

---

## Phase 1 — Core bootstrap

**Goal:** Plugin installs, activates cleanly, registers CPT + custom tables.

- [ ] `plugin.php` bootstrap (version guards, constants, autoload)
- [ ] Composer + `vendor/` (PSR-4 namespace `BioLinkPro\`)
- [ ] `Core\Plugin` singleton + service container
- [ ] `Core\Activator` / `Core\Deactivator` / `uninstall.php`
- [ ] `Database\Migrator` + initial migrations
- [ ] CPT `biolink_page` registered
- [ ] Custom capabilities registered
- [ ] PHPCS + Composer scripts + PHPUnit bootstrap
- [ ] `readme.txt` for WP.org

**Exit:** activate plugin on fresh WP install, see CPT in admin menu, tables exist in DB, `WP_DEBUG=true` produces zero notices.

---

## Phase 2 — REST API + admin shell

**Goal:** REST endpoints for pages + blocks exist and respond. React admin app loads on its own menu page (no real builder yet).

- [ ] `Api\PagesController` with full CRUD + permission callbacks
- [ ] `Api\BlocksController` (registry, append, reorder)
- [ ] React app skeleton mounted at `Bio Links` admin page
- [ ] `@wordpress/scripts` build pipeline + watch mode
- [ ] Routing (Dashboard, Pages, Settings)
- [ ] REST client wrapper with nonce handling

**Exit:** create/edit/delete a page via REST and via the admin UI table. Lint + tests green.

---

## Phase 3 — Block builder

**Goal:** Drag-drop builder works. All P1 blocks render on the frontend.

- [ ] `Blocks\BlockRegistry` + `Blocks\AbstractBlock`
- [ ] P1 blocks: `link`, `button`, `social_icons`, `image_gallery`, `rich_text`, `divider`, `video`, `youtube`
- [ ] React canvas + inspector + toolbar (dnd-kit)
- [ ] Frontend rewrite rule `/bio/{slug}` + template
- [ ] `Frontend\PageRenderer` dispatches to block renderers
- [ ] Mobile-first base stylesheet

**Exit:** publish a page, visit `/bio/{slug}` on mobile, all P1 blocks render correctly with no JS errors.

---

## Phase 4 — Themes + remaining blocks

**Goal:** Theme presets switchable per page. P2 blocks shipped.

- [ ] `Themes\ThemeEngine` + 9 built-in presets
- [ ] Custom CSS sanitizer
- [ ] Theme picker in admin
- [ ] P2 blocks: `spotify`, `tiktok`, `contact_form`, `faq`, `countdown`, `newsletter`, `product_card`, `donation`, `html_embed`, `map`
- [ ] Font loader (Google Fonts + self-hosted)
- [ ] Background options (gradient, image, video)

**Exit:** switch themes from admin, change reflects on frontend within one cache cycle. All 18 blocks ship.

---

## Phase 5 — Analytics

**Goal:** Click + view tracking captured and visualized.

- [ ] `Analytics\Tracker` (async via single-event cron)
- [ ] `Frontend\ClickController` (rate-limited redirect)
- [ ] View beacon endpoint
- [ ] Aggregation queries + dashboard charts (Recharts or Chart.js)
- [ ] Summary, timeseries, geo, devices, top links views
- [ ] CSV export
- [ ] Daily prune job (rate-limit table + old events per retention setting)

**Exit:** click a link on a published page → record appears in `biolink_clicks` → dashboard shows updated count. Lighthouse mobile score >= 90 on a published page.

---

## Phase 6 — QR codes + SEO

**Goal:** Per-page QR, OG images, schema.

- [ ] `Qr\Generator` (uses `endroid/qr-code` via Composer)
- [ ] QR style options (color, logo embed)
- [ ] OG image generator (HTML → image via wkhtmltoimage or `intervention/image`)
- [ ] JSON-LD per page (`Person`, `Organization`, `WebPage`, `FAQPage` from FAQ blocks)
- [ ] Sitemap entry via `wp_sitemaps` API

**Exit:** scan QR → opens bio page. OG card renders correctly when shared on Twitter/X.

---

## Phase 7 — Integrations + monetization

**Goal:** Stripe, PayPal, email providers, donations live.

- [ ] `Integrations\Stripe` (Checkout sessions for donations + products)
- [ ] `Integrations\PayPal` (Smart Buttons)
- [ ] `Integrations\Email` (Mailchimp, MailerLite, Resend adapters)
- [ ] Affiliate link tracking (UTM injection + redirect)
- [ ] Webhook handler for Stripe events
- [ ] Settings UI for connections

**Exit:** donation block accepts a real test-mode payment end-to-end; subscriber added to newsletter provider list.

---

## Phase 8 — AI module (optional)

**Goal:** AI suggestions wired to OpenAI.

- [ ] `Ai\ProviderRegistry` + OpenAI provider
- [ ] Endpoints for bio, CTA, theme suggestions
- [ ] Rate limit + per-user quota
- [ ] In-builder "✨ Suggest" buttons

**Exit:** click "Suggest bio" → returns 3 options within 5 seconds.

---

## Phase 9 — Templates + onboarding

**Goal:** Quick-start templates + first-run wizard.

- [ ] 9 pre-made templates (Creator, Agency, Musician, etc.)
- [ ] One-click apply
- [ ] Onboarding wizard (first activation)
- [ ] Sample data importer / exporter (JSON)

**Exit:** new user activates plugin → wizard guides them to a published bio page within 3 minutes.

---

## Phase 10 — Premium scaffolding (post-1.0)

Architecture-only; no UI yet:
- License system + update server hook
- Subscription tiers
- SaaS mode (multi-tenant via Multisite)
- A/B testing
- Team workspaces + roles
- Automation workflows
- Custom domain mapping (CNAME)
- White-label settings

---

## Version targets

| Release | Phases | Notes |
|---|---|---|
| 0.1 alpha | 1–2 | Internal only |
| 0.5 beta | 3–4 | Closed beta |
| 0.9 RC | 5–6 | Public beta |
| 1.0 | 7 | WP.org launch |
| 1.1 | 8 | AI optional |
| 1.2 | 9 | Templates + wizard |
| 2.0 | 10 | Premium-ready |
