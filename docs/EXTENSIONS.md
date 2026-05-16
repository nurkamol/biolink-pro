# Extensibility — public hooks reference

BioLink Pro exposes a stable hook surface so third-party plugins can add blocks, themes,
integrations, and (Phase 10 scope) licensing / multi-tenant / A-B testing layers.

Hook namespace: `biolink/{module}/{event}`.

## Block registration

```php
add_action( 'biolink/blocks/register', function ( $registry ) {
    $registry->register( new \MyPlugin\Blocks\PollBlock() );
} );
```

## Theme registration

```php
add_action( 'biolink/themes/register', function ( $themes ) {
    $themes->register( new \BioLinkPro\Themes\Preset( ... ) );
} );
```

## Forms — newsletter / contact submissions

```php
add_action( 'biolink/newsletter/subscribed', function ( $entry ) {
    // forward to Mailchimp / MailerLite / Resend
} );

add_action( 'biolink/contact/submitted', function ( $data ) {
    // forward to your CRM
} );
```

## Webhooks — provider event handlers

```php
add_filter( 'biolink/webhook/stripe/verify', function ( $verified, $body, $headers ) {
    // Verify Stripe signature; return WP_Error to reject.
    return $verified;
}, 10, 3 );

add_action( 'biolink/webhook/stripe', function ( $payload, $headers ) {
    // Route by $payload['type']
}, 10, 2 );
```

## Click + view tracking

```php
add_filter( 'biolink/click/before', function ( $allow, $event ) {
    return $allow; // return false to skip recording
}, 10, 2 );
```

## Premium / licensing (Phase 10 scaffolding only)

These hooks are declared but no-op in core. Premium add-ons implement them.

```php
// License gate
add_filter( 'biolink/license/active', '__return_true' );
add_filter( 'biolink/license/features', fn( $features ) => $features + [ 'ab_testing' => true ] );

// Custom domain mapping
add_filter( 'biolink/domain/resolve', function ( $domain ) {
    // map foo.com → bio page slug
    return $domain;
} );

// Multi-tenant / SaaS mode
add_filter( 'biolink/saas/enabled', '__return_false' );

// A/B testing
add_filter( 'biolink/ab/variant', function ( $variant, $page_id, $visitor_hash ) {
    return $variant;
}, 10, 3 );

// Team workspaces
add_filter( 'biolink/workspaces/enabled', '__return_false' );

// Automation workflows
add_action( 'biolink/automation/trigger', function ( $trigger, $context ) {
    // Run workflows matching the trigger.
}, 10, 2 );

// White-label
add_filter( 'biolink/whitelabel/brand', fn( $brand ) => $brand );
```
