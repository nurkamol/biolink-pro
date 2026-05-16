<?php
/**
 * Hooks the generic biolink/webhook/stripe event from WebhookController.
 *
 * @package BioLinkPro\Integrations\Stripe
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Stripe;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class StripeWebhookListener implements Bootable
{
    public function __construct(private readonly Checkout $checkout)
    {
    }

    public function boot(): void
    {
        add_filter('biolink/webhook/stripe/verify', [$this, 'verify'], 10, 3);
        add_action('biolink/webhook/stripe', [$this, 'handle'], 10, 2);
    }

    /**
     * @param true|\WP_Error          $verified
     * @param string                  $body
     * @param array<string, mixed>    $headers
     * @return true|\WP_Error
     */
    public function verify($verified, string $body, array $headers)
    {
        if ($this->checkout->verifyWebhookSignature($body, $headers)) {
            return true;
        }
        return new \WP_Error('stripe_invalid_signature', __('Invalid Stripe signature.', 'biolink-pro'), ['status' => 400]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $_headers
     */
    public function handle(array $payload, array $_headers): void
    {
        $type = (string) ($payload['type'] ?? '');
        if ($type !== 'checkout.session.completed') {
            return;
        }
        $session = $payload['data']['object'] ?? [];
        if (! is_array($session)) {
            return;
        }

        $entry = [
            'session_id'  => (string) ($session['id'] ?? ''),
            'amount'      => (int) ($session['amount_total'] ?? 0),
            'currency'    => (string) ($session['currency'] ?? 'usd'),
            'customer'    => (string) ($session['customer_details']['email'] ?? ''),
            'page_id'     => (int) ($session['metadata']['biolink_page_id'] ?? 0),
            'block_uuid'  => (string) ($session['metadata']['biolink_block_uuid'] ?? ''),
            'time'        => current_time('mysql', true),
        ];

        // Append to options log (capped at last 200 entries).
        $log = (array) get_option('biolink_stripe_log', []);
        $log[] = $entry;
        update_option('biolink_stripe_log', array_slice($log, -200), false);

        /**
         * Fires on a successful Stripe Checkout completion.
         *
         * @param array<string, mixed> $entry
         */
        do_action('biolink/stripe/completed', $entry);
    }
}
