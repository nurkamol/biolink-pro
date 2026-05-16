<?php
/**
 * Mailchimp Marketing API subscribe adapter.
 *
 * Requires `mailchimp_api_key` (encrypted) and `mailchimp_list_id` (plain) in biolink_integrations.
 * API key format includes the datacenter suffix e.g. `abc...-us12`.
 *
 * @package BioLinkPro\Integrations\Email
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Email;

defined('ABSPATH') || exit;

final class MailchimpAdapter extends AbstractEmailAdapter
{
    public function providerLabel(): string
    {
        return 'Mailchimp';
    }

    public function isConfigured(): bool
    {
        return $this->readSecret('mailchimp_api_key') !== '' && $this->readSetting('mailchimp_list_id') !== '';
    }

    protected function subscribe(string $email, array $entry): void
    {
        $api_key = $this->readSecret('mailchimp_api_key');
        $list_id = $this->readSetting('mailchimp_list_id');

        $parts = explode('-', $api_key);
        $dc    = end($parts) ?: 'us1';
        if (! preg_match('/^[a-z]{2}\d+$/', $dc)) {
            throw new \RuntimeException('Mailchimp API key is missing the datacenter suffix (e.g. -us12).');
        }

        $email_hash = md5(strtolower($email));
        $url        = sprintf('https://%s.api.mailchimp.com/3.0/lists/%s/members/%s', $dc, $list_id, $email_hash);

        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode([
                'email_address' => $email,
                'status_if_new' => 'subscribed',
                'status'        => 'subscribed',
            ]),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            $body = (string) wp_remote_retrieve_body($response);
            throw new \RuntimeException("Mailchimp returned HTTP $code: " . substr($body, 0, 200));
        }
    }
}
