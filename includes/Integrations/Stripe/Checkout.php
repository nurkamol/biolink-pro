<?php
/**
 * Stripe Checkout Session service.
 *
 * No SDK — calls /v1/checkout/sessions via wp_remote_post.
 * Mode (test/live) auto-detected from the secret key prefix.
 *
 * @package BioLinkPro\Integrations\Stripe
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Stripe;

use BioLinkPro\Core\Crypto;

defined('ABSPATH') || exit;

final class Checkout
{
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function isLiveMode(): bool
    {
        return str_starts_with($this->apiKey(), 'sk_live_');
    }

    /**
     * Create a Checkout Session for a one-time payment.
     *
     * @param array{
     *   amount: int,        // in minor units (cents)
     *   currency: string,
     *   name: string,
     *   page_id: int,
     *   block_uuid?: string,
     *   mode?: 'payment'|'subscription',
     *   image_url?: string
     * } $params
     * @return array{id: string, url: string}|null
     */
    public function createSession(array $params): ?array
    {
        $api_key = $this->apiKey();
        if ($api_key === '') {
            return null;
        }

        $amount   = max(50, (int) ($params['amount'] ?? 0)); // Stripe minimum is 50¢
        $currency = strtolower((string) ($params['currency'] ?? 'usd'));
        $name     = (string) ($params['name'] ?? 'Donation');
        $page_id  = (int) ($params['page_id'] ?? 0);
        $images   = ! empty($params['image_url']) ? [(string) $params['image_url']] : [];

        $page_url    = $page_id > 0 ? (string) get_permalink($page_id) : home_url();
        $success_url = add_query_arg(['biolink_checkout' => 'success'], $page_url);
        $cancel_url  = add_query_arg(['biolink_checkout' => 'cancel'], $page_url);

        // Stripe expects line_items as bracketed form fields.
        $body = [
            'mode'                  => 'payment',
            'success_url'           => $success_url,
            'cancel_url'            => $cancel_url,
            'line_items[0][quantity]'                       => 1,
            'line_items[0][price_data][currency]'           => $currency,
            'line_items[0][price_data][unit_amount]'        => $amount,
            'line_items[0][price_data][product_data][name]' => $name,
            'metadata[biolink_page_id]'                     => (string) $page_id,
            'metadata[biolink_block_uuid]'                  => (string) ($params['block_uuid'] ?? ''),
        ];
        if ($images !== []) {
            $body['line_items[0][price_data][product_data][images][0]'] = $images[0];
        }

        $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return null;
        }
        $payload = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($payload) || empty($payload['url'])) {
            return null;
        }

        return [
            'id'  => (string) ($payload['id'] ?? ''),
            'url' => (string) $payload['url'],
        ];
    }

    /**
     * Verify a Stripe webhook signature header.
     *
     * @param array<string, mixed> $headers
     */
    public function verifyWebhookSignature(string $raw_body, array $headers): bool
    {
        $secret = $this->webhookSecret();
        if ($secret === '') {
            // No webhook secret configured — accept anyway (less secure, common in dev).
            return true;
        }

        $sig_header = '';
        foreach ($headers as $name => $value) {
            if (strcasecmp((string) $name, 'stripe-signature') === 0) {
                $sig_header = is_array($value) ? (string) reset($value) : (string) $value;
                break;
            }
        }
        if ($sig_header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sig_header) as $item) {
            [$k, $v] = array_pad(explode('=', $item, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }
        $timestamp = $parts['t'] ?? '';
        $sig       = $parts['v1'] ?? '';
        if ($timestamp === '' || $sig === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $raw_body, $secret);
        return hash_equals($expected, $sig);
    }

    private function apiKey(): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        $value  = (string) ($stored['stripe_secret'] ?? '');
        return $value === '' ? '' : ( new Crypto() )->decrypt($value);
    }

    private function webhookSecret(): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        $value  = (string) ($stored['stripe_webhook_secret'] ?? '');
        return $value === '' ? '' : ( new Crypto() )->decrypt($value);
    }
}
