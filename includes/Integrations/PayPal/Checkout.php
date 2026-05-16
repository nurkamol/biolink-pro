<?php
/**
 * PayPal Orders v2 service.
 *
 * Server-side flow:
 *   1. POST /v2/checkout/orders → returns an order id + approval link
 *   2. Redirect the customer to the approval link
 *   3. PayPal redirects back to our return_url with ?token={order_id}
 *   4. POST /v2/checkout/orders/{id}/capture from the return handler
 *
 * Auth: OAuth client_credentials, token cached in a transient.
 *
 * @package BioLinkPro\Integrations\PayPal
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\PayPal;

use BioLinkPro\Core\Crypto;

defined('ABSPATH') || exit;

final class Checkout
{
    private const TOKEN_CACHE_KEY = 'biolink_paypal_access_token';

    public function isConfigured(): bool
    {
        return $this->clientId() !== '' && $this->secret() !== '';
    }

    public function isSandbox(): bool
    {
        $integrations = (array) get_option('biolink_integrations', []);
        return ! empty($integrations['paypal_sandbox']);
    }

    private function apiBase(): string
    {
        return $this->isSandbox() ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    /**
     * Create an order and return the approval URL.
     *
     * @param array{
     *   amount: float,
     *   currency?: string,
     *   description?: string,
     *   page_id?: int,
     *   block_uuid?: string
     * } $params
     * @return array{id: string, approve_url: string}|null
     */
    public function createOrder(array $params): ?array
    {
        $token = $this->getAccessToken();
        if ($token === '') {
            return null;
        }

        $amount      = max(1.0, (float) ($params['amount'] ?? 0));
        $currency    = strtoupper((string) ($params['currency'] ?? 'USD'));
        $description = (string) ($params['description'] ?? 'Donation');
        $page_id     = (int) ($params['page_id'] ?? 0);
        $page_url    = $page_id > 0 ? (string) get_permalink($page_id) : home_url();

        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'amount'    => [
                    'currency_code' => $currency,
                    'value'         => number_format($amount, 2, '.', ''),
                ],
                'description' => mb_substr($description, 0, 127),
                'custom_id'   => $page_id . ':' . (string) ($params['block_uuid'] ?? ''),
            ]],
            'application_context' => [
                'return_url' => add_query_arg(['biolink_paypal' => 'return'], $page_url),
                'cancel_url' => add_query_arg(['biolink_paypal' => 'cancel'], $page_url),
                'brand_name' => (string) get_bloginfo('name'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = wp_remote_post($this->apiBase() . '/v2/checkout/orders', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return null;
        }
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($body) || empty($body['id']) || empty($body['links'])) {
            return null;
        }
        $approve = '';
        foreach ($body['links'] as $link) {
            if (is_array($link) && ($link['rel'] ?? '') === 'approve' && ! empty($link['href'])) {
                $approve = (string) $link['href'];
                break;
            }
        }
        if ($approve === '') {
            return null;
        }

        return ['id' => (string) $body['id'], 'approve_url' => $approve];
    }

    /**
     * Capture a previously-approved order.
     *
     * @return array<string, mixed>|null Captured order payload on success
     */
    public function captureOrder(string $order_id): ?array
    {
        $token = $this->getAccessToken();
        if ($token === '' || $order_id === '') {
            return null;
        }

        $response = wp_remote_post(
            $this->apiBase() . '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture',
            [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => '{}',
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return null;
        }
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        return is_array($body) ? $body : null;
    }

    private function getAccessToken(): string
    {
        $cached = get_transient(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        if (! $this->isConfigured()) {
            return '';
        }

        $response = wp_remote_post($this->apiBase() . '/v1/oauth2/token', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->clientId() . ':' . $this->secret()),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => 'grant_type=client_credentials',
        ]);

        if (is_wp_error($response)) {
            return '';
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return '';
        }
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($body) || empty($body['access_token'])) {
            return '';
        }
        $token = (string) $body['access_token'];
        $ttl   = max(60, ((int) ($body['expires_in'] ?? 3600)) - 60);
        set_transient(self::TOKEN_CACHE_KEY, $token, $ttl);
        return $token;
    }

    private function clientId(): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        $value  = (string) ($stored['paypal_client_id'] ?? '');
        return $value === '' ? '' : ( new Crypto() )->decrypt($value);
    }

    private function secret(): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        $value  = (string) ($stored['paypal_secret'] ?? '');
        return $value === '' ? '' : ( new Crypto() )->decrypt($value);
    }
}
