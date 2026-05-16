<?php
/**
 * Generic provider webhook receiver. Verifies signatures via per-provider
 * filters and dispatches `biolink/webhook/{provider}` action for handlers.
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

final class WebhookController extends AbstractController
{
    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/webhooks/(?P<provider>[a-z0-9_-]+)',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'receive'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'provider' => ['type' => 'string', 'sanitize_callback' => 'sanitize_key'],
                ],
            ]
        );
    }

    public function receive(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $provider = (string) $request['provider'];
        $body     = $request->get_body();
        $headers  = $request->get_headers();

        /**
         * Verify a webhook signature. Return WP_Error to reject, true to accept.
         *
         * @param true|WP_Error          $verified
         * @param string                 $body
         * @param array<string, mixed>   $headers
         */
        $verified = apply_filters("biolink/webhook/{$provider}/verify", true, $body, $headers);
        if (is_wp_error($verified)) {
            return $verified;
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            $payload = [];
        }

        /**
         * Provider-specific webhook payload.
         *
         * @param array<string, mixed>   $payload
         * @param array<string, mixed>   $headers
         */
        do_action("biolink/webhook/{$provider}", $payload, $headers);

        return $this->ok(['ok' => true]);
    }
}
