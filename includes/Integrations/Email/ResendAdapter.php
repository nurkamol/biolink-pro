<?php
/**
 * Resend Audiences subscribe adapter.
 *
 * Requires `resend_api_key` (encrypted) and `resend_audience_id` (plain).
 * See https://resend.com/docs/api-reference/contacts/create-contact
 *
 * @package BioLinkPro\Integrations\Email
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Email;

defined('ABSPATH') || exit;

final class ResendAdapter extends AbstractEmailAdapter
{
    public function providerLabel(): string
    {
        return 'Resend';
    }

    public function isConfigured(): bool
    {
        return $this->readSecret('resend_api_key') !== '' && $this->readSetting('resend_audience_id') !== '';
    }

    protected function subscribe(string $email, array $entry): void
    {
        $api_key     = $this->readSecret('resend_api_key');
        $audience_id = $this->readSetting('resend_audience_id');

        $url = sprintf('https://api.resend.com/audiences/%s/contacts', rawurlencode($audience_id));

        $response = wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode([
                'email'      => $email,
                'unsubscribed' => false,
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300 && $code !== 409) { // 409 = already subscribed
            $body = (string) wp_remote_retrieve_body($response);
            throw new \RuntimeException("Resend returned HTTP $code: " . substr($body, 0, 200));
        }
    }
}
