# Extensibility — hooks reference

BioLink Pro exposes a stable hook surface so third-party plugins can add blocks, themes, integrations, and observe lifecycle events.

Hook namespace: `biolink/{module}/{event}`.

## Lifecycle

```php
add_action( 'biolink/plugin/activated', function () {
    // Plugin just activated.
} );

add_action( 'biolink/plugin/deactivated', function () {
    // Plugin just deactivated.
} );

add_action( 'biolink/plugin/booted', function ( $plugin ) {
    // Service container is fully wired ($plugin instanceof BioLinkPro\Core\Plugin).
} );
```

## Block registration

```php
add_action( 'biolink/blocks/register', function ( $registry ) {
    $registry->register( new \MyPlugin\Blocks\PollBlock() );
} );
```

See `BLOCKS.md` for the full block author contract.

## Theme registration

```php
add_action( 'biolink/themes/register', function ( $themes ) {
    $themes->register( new \BioLinkPro\Themes\Preset(
        slug:  'my-theme',
        label: __( 'My Theme', 'my-plugin' ),
        // …
    ) );
} );
```

## Block render filter

```php
add_filter( 'biolink/block/render', function ( $html, $type, $data, $uuid ) {
    if ( $type === 'link' ) {
        $html .= '<!-- audited -->';
    }
    return $html;
}, 10, 4 );
```

## Page render

```php
add_action( 'biolink/page/render/before', function ( $post, $data ) {
    // Page is about to render. $post = WP_Post, $data = decoded _biolink_data.
}, 10, 2 );

add_filter( 'biolink/page/render', function ( $html, $post, $data ) {
    // Final chance to mutate the full page HTML.
    return $html;
}, 10, 3 );

add_filter( 'biolink/template/bio-page', function ( $template ) {
    // Override the bundled template entirely.
    return MY_PLUGIN_DIR . 'templates/my-bio-page.php';
} );
```

## Click tracking

```php
add_filter( 'biolink/click/before', function ( $allow, $event ) {
    // Return false to skip recording (e.g. logged-in editors).
    return $allow;
}, 10, 2 );
```

## Newsletter / contact form

```php
add_action( 'biolink/newsletter/subscribed', function ( $entry ) {
    // $entry = ['email', 'page_id', 'block_uuid', 'time']
    // Forward to your CRM. (Mailchimp / MailerLite / Resend already wire automatically.)
} );

add_action( 'biolink/contact/submitted', function ( $entry ) {
    // $entry = ['name', 'email', 'message', 'page_id', 'block_uuid', 'time']
} );
```

## Payments

```php
add_action( 'biolink/stripe/completed', function ( $entry ) {
    // Stripe Checkout session completed event arrived via webhook.
    // $entry has session id, amount, currency, customer email, etc.
} );

add_action( 'biolink/paypal/captured', function ( $entry ) {
    // PayPal order captured (either via REST capture or the visitor-return handler).
    // $entry has order_id, status, amount, currency, payer email, time.
} );

add_filter( 'biolink/webhook/stripe/verify', function ( $verified, $body, $headers ) {
    // Customize Stripe signature verification. Return WP_Error to reject.
    return $verified;
}, 10, 3 );

add_action( 'biolink/webhook/stripe', function ( $payload, $headers ) {
    // Generic Stripe webhook routing point.
}, 10, 2 );
```

## Passcode-gated links

```php
add_action( 'biolink/link/unlocked', function ( $uuid, $page_id ) {
    // A visitor entered the correct passcode for this block.
    // Useful for unlock-count metrics or notifying the page owner.
}, 10, 2 );
```

## Cron

```php
add_action( 'biolink/cron/pruned', function ( $retention_days ) {
    // Daily prune just ran. Old click/view events and expired rate-limit
    // buckets have been deleted.
} );
```

## SEO coexistence

```php
add_filter( 'biolink/seo/rival_active', function ( $detected ) {
    // Override our auto-detection of Yoast / Rank Math / SEOPress.
    // When true, BioLink pushes its values into the rival plugin's
    // filters instead of emitting its own <meta> + JSON-LD.
    return $detected;
} );
```

## Admin

```php
add_action( 'biolink/admin/menu/registered', function ( $hook ) {
    // BioLink Pro just registered its top-level admin menu.
    // $hook is the resulting page_hook from add_menu_page.
} );
```

## Post type

```php
add_filter( 'biolink/post_type/args', function ( $args ) {
    // Customize register_post_type args for `biolink_page` before registration.
    return $args;
} );
```

---

## What's not yet exposed

A few things are theoretically extension surfaces but don't have a public hook yet:

- **Custom theme storage** (REST CRUD) — declared in `API.md`, not implemented. Use `biolink/themes/register` at boot for now.
- **React admin block catalog** — third parties can register PHP blocks via `biolink/blocks/register` but the React inserter is built from a static `admin/src/blocks/index.ts` catalog. There's no JS-side `wp.hooks` filter yet to inject editors.
- **A/B testing, custom domain mapping, multi-tenant SaaS mode, team workspaces, automation workflows, white-label** — listed in the original Phase 10 roadmap but not built. v2.x candidates in `ROADMAP.md`.
