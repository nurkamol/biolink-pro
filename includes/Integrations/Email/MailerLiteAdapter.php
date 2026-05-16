<?php
/**
 * MailerLite v2 subscriber adapter.
 *
 * Requires `mailerlite_api_key` (encrypted). Optional `mailerlite_group_id` (plain) to add
 * the subscriber to a specific group.
 *
 * @package BioLinkPro\Integrations\Email
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Email;

defined('ABSPATH') || exit;

final class MailerLiteAdapter extends AbstractEmailAdapter
{
    public function providerLabel(): string
    {
        return 'MailerLite';
    }

    public function isConfigured(): bool
    {
        return $this->readSecret('mailerlite_api_key') !== '';
    }

    protected function subscribe(string $email, array $entry): void
    {
        $api_key  = $this->readSecret('mailerlite_api_key');
        $group_id = $this->readSetting('mailerlite_group_id');

        $payload = ['email' => $email];
        if ($group_id !== '') {
            $payload['groups'] = [$group_id];
        }

        $response = wp_remote_post('https://connect.mailerlite.com/api/subscribers', [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        // MailerLite returns 200/201 for new subscriber, 200 for existing.
        if ($code < 200 || $code >= 300) {
            $body = (string) wp_remote_retrieve_body($response);
            throw new \RuntimeException("MailerLite returned HTTP $code: " . substr($body, 0, 200));
        }
    }
}
