<?php
/**
 * REST endpoints that initiate Stripe / PayPal checkout flows from a bio page.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Integrations\PayPal\Checkout as PayPalCheckout;
use BioLinkPro\Integrations\Stripe\Checkout as StripeCheckout;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly StripeCheckout $stripe,
        private readonly PayPalCheckout $paypal
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/stripe/checkout',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'stripeCheckout'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page_id'    => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
                    'block_uuid' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'amount'     => ['type' => 'number', 'required' => true],
                    'currency'   => ['type' => 'string', 'default' => 'USD', 'sanitize_callback' => 'sanitize_text_field'],
                    'name'       => ['type' => 'string', 'default' => 'Donation', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/paypal/checkout',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'paypalCheckout'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page_id'     => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
                    'block_uuid'  => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'amount'      => ['type' => 'number', 'required' => true],
                    'currency'    => ['type' => 'string', 'default' => 'USD', 'sanitize_callback' => 'sanitize_text_field'],
                    'description' => ['type' => 'string', 'default' => 'Donation', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/paypal/capture',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'paypalCapture'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'order_id' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ]
        );
    }

    public function stripeCheckout(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        if (! $this->stripe->isConfigured()) {
            return $this->error('stripe_not_configured', __('Stripe is not configured.', 'biolink-pro'), 503);
        }

        $amount = (float) $request['amount'];
        if ($amount < 0.5) {
            return $this->error('amount_too_small', __('Amount is below the minimum.', 'biolink-pro'), 400);
        }

        $session = $this->stripe->createSession([
            'amount'     => (int) round($amount * 100),
            'currency'   => (string) $request['currency'],
            'name'       => (string) $request['name'],
            'page_id'    => (int) $request['page_id'],
            'block_uuid' => (string) $request['block_uuid'],
        ]);

        if ($session === null) {
            return $this->error('stripe_failed', __('Could not create Stripe checkout session.', 'biolink-pro'), 502);
        }

        return $this->ok($session);
    }

    public function paypalCheckout(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        if (! $this->paypal->isConfigured()) {
            return $this->error('paypal_not_configured', __('PayPal is not configured.', 'biolink-pro'), 503);
        }

        $order = $this->paypal->createOrder([
            'amount'      => (float) $request['amount'],
            'currency'    => (string) $request['currency'],
            'description' => (string) $request['description'],
            'page_id'     => (int) $request['page_id'],
            'block_uuid'  => (string) $request['block_uuid'],
        ]);

        if ($order === null) {
            return $this->error('paypal_failed', __('Could not create PayPal order.', 'biolink-pro'), 502);
        }

        return $this->ok($order);
    }

    public function paypalCapture(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $order_id = (string) $request['order_id'];
        $captured = $this->paypal->captureOrder($order_id);
        if ($captured === null) {
            return $this->error('paypal_capture_failed', __('Could not capture PayPal order.', 'biolink-pro'), 502);
        }
        $entry = [
            'order_id' => $order_id,
            'status'   => (string) ($captured['status'] ?? 'unknown'),
            'amount'   => (string) ($captured['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? ''),
            'currency' => (string) ($captured['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] ?? ''),
            'payer'    => (string) ($captured['payer']['email_address'] ?? ''),
            'time'     => current_time('mysql', true),
        ];
        $log = (array) get_option('biolink_paypal_log', []);
        $log[] = $entry;
        update_option('biolink_paypal_log', array_slice($log, -200), false);

        /**
         * Fires on a successful PayPal capture.
         *
         * @param array<string, mixed> $entry
         */
        do_action('biolink/paypal/captured', $entry);

        return $this->ok(['ok' => true, 'status' => $entry['status']]);
    }
}
