<?php
/**
 * Public REST endpoints for the newsletter + contact_form blocks.
 *
 * Anonymous POST is allowed; protected by per-IP rate limiting + a per-page nonce
 * + a honey-pot field. Submissions trigger an email to the site admin and append
 * to an option (`biolink_newsletter_list` / `biolink_contact_log`) for later
 * export. Provider integrations (Mailchimp / MailerLite / Resend) land in Phase 7.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class FormsController extends AbstractController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/newsletter/subscribe',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'newsletterSubscribe'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'email'       => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_email'],
                    'page_id'     => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
                    'nonce'       => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'biolink_hp'  => ['type' => 'string', 'required' => false, 'default' => ''],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/contact/submit',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'contactSubmit'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'name'        => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'email'       => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_email'],
                    'message'     => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field'],
                    'page_id'     => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
                    'nonce'       => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    'biolink_hp'  => ['type' => 'string', 'required' => false, 'default' => ''],
                ],
            ]
        );
    }

    public function newsletterSubscribe(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id = (int) $request['page_id'];
        $err     = $this->guardSubmission($request, 'biolink_newsletter_' . $page_id, $page_id);
        if ($err !== null) {
            return $err;
        }

        $email = (string) $request['email'];
        if (! is_email($email)) {
            return $this->error('invalid_email', __('Please enter a valid email address.', 'biolink-pro'), 400);
        }

        $list = get_option('biolink_newsletter_list', []);
        if (! is_array($list)) {
            $list = [];
        }
        $entry = [
            'email'   => $email,
            'page_id' => $page_id,
            'time'    => current_time('mysql', true),
            'ip_hash' => self::hashIp(),
        ];

        // Dedupe on (email, page_id)
        $exists = false;
        foreach ($list as $row) {
            if (is_array($row) && ($row['email'] ?? '') === $email && (int) ($row['page_id'] ?? 0) === $page_id) {
                $exists = true;
                break;
            }
        }
        if (! $exists) {
            $list[] = $entry;
            update_option('biolink_newsletter_list', array_slice($list, -1000), false);

            wp_mail(
                (string) get_option('admin_email'),
                /* translators: %s: site name */
                sprintf(__('[%s] New newsletter subscriber', 'biolink-pro'), wp_specialchars_decode((string) get_bloginfo('name'))),
                sprintf(
                    "Email: %s\nPage: %s\nWhen: %s\n",
                    $email,
                    get_permalink($page_id) ?: '(unknown)',
                    $entry['time']
                )
            );
        }

        /**
         * Fires after a successful newsletter subscription. Hook to forward to
         * Mailchimp / MailerLite / Resend (Phase 7 integrations).
         *
         * @param array<string, mixed> $entry
         */
        do_action('biolink/newsletter/subscribed', $entry);

        return $this->ok(['ok' => true]);
    }

    public function contactSubmit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id = (int) $request['page_id'];
        $err     = $this->guardSubmission($request, 'biolink_contact_' . $page_id, $page_id);
        if ($err !== null) {
            return $err;
        }

        $name    = trim((string) $request['name']);
        $email   = (string) $request['email'];
        $message = trim((string) $request['message']);
        if ($name === '' || $message === '' || ! is_email($email)) {
            return $this->error('invalid_input', __('Please fill in all fields with a valid email.', 'biolink-pro'), 400);
        }

        $sent = wp_mail(
            (string) get_option('admin_email'),
            /* translators: 1: site name, 2: visitor name */
            sprintf(__('[%1$s] New contact form message from %2$s', 'biolink-pro'), wp_specialchars_decode((string) get_bloginfo('name')), $name),
            sprintf("From: %s <%s>\nPage: %s\n\n%s\n", $name, $email, get_permalink($page_id) ?: '(unknown)', $message),
            ['Reply-To: ' . sanitize_email($email)]
        );

        if (! $sent) {
            return $this->error('mail_failed', __('Could not send your message right now.', 'biolink-pro'), 500);
        }

        /**
         * Fires after a successful contact form submission.
         *
         * @param array<string, mixed> $data
         */
        do_action('biolink/contact/submitted', [
            'name'    => $name,
            'email'   => $email,
            'message' => $message,
            'page_id' => $page_id,
        ]);

        return $this->ok(['ok' => true]);
    }

    /**
     * Validate honey-pot, nonce, and per-IP rate limit. Returns null when OK.
     */
    private function guardSubmission(WP_REST_Request $request, string $nonce_action, int $page_id): ?WP_Error
    {
        // Honey pot — bots fill hidden fields
        if (! empty($request['biolink_hp'])) {
            return $this->error('spam_detected', __('Submission blocked.', 'biolink-pro'), 400);
        }
        if (! wp_verify_nonce((string) $request['nonce'], $nonce_action)) {
            return $this->error('bad_nonce', __('Security check failed. Reload the page and try again.', 'biolink-pro'), 403);
        }
        if ($page_id <= 0) {
            return $this->error('bad_page', __('Invalid page reference.', 'biolink-pro'), 400);
        }

        // Lightweight rate limit (per IP, per 5 minutes, 5 submissions max)
        $bucket = 'biolink_form_' . self::clientFingerprint();
        $hits   = (int) get_transient($bucket);
        if ($hits >= 5) {
            return $this->error('rate_limited', __('Too many submissions. Please try again later.', 'biolink-pro'), 429);
        }
        set_transient($bucket, $hits + 1, 5 * MINUTE_IN_SECONDS);
        return null;
    }

    private static function hashIp(): string
    {
        $salt = wp_salt('auth');
        $ip   = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        return hash('sha256', $ip . '|' . $salt);
    }

    private static function clientFingerprint(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : 'unknown';
        return substr(md5($ip), 0, 16);
    }
}
