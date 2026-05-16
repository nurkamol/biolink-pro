<?php
/**
 * REST endpoints for AI suggestions.
 *
 * @package BioLinkPro\Ai
 */

declare(strict_types=1);

namespace BioLinkPro\Ai;

use BioLinkPro\Api\AbstractController;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class AiController extends AbstractController
{
    public function __construct(private readonly ProviderRegistry $registry)
    {
    }

    public function registerRoutes(): void
    {
        foreach (['bio', 'cta', 'theme'] as $kind) {
            register_rest_route(
                self::NAMESPACE,
                "/ai/{$kind}",
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => fn(WP_REST_Request $req) => $this->suggest($kind, $req),
                    'permission_callback' => $this->requireCap('biolink_use_ai'),
                    'args'                => [
                        'prompt' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                    ],
                ]
            );
        }
    }

    public function suggest(string $kind, WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        // Per-user 10/min rate limit
        $user_id  = (int) get_current_user_id();
        $bucket   = 'biolink_ai_' . $user_id;
        $hits     = (int) get_transient($bucket);
        if ($hits >= 10) {
            return $this->error('rate_limited', __('Too many AI requests. Try again in a minute.', 'biolink-pro'), 429);
        }
        set_transient($bucket, $hits + 1, 60);

        $provider = $this->registry->active();
        if ($provider === null) {
            return $this->error('ai_not_configured', __('Configure an AI provider in Settings → Integrations first.', 'biolink-pro'), 503);
        }

        $prompt = (string) $request['prompt'];
        $suggestions = $provider->suggest($kind, $prompt);
        if ($suggestions === []) {
            return $this->error('ai_failed', __('AI request did not return any suggestions.', 'biolink-pro'), 502);
        }

        return $this->ok(['suggestions' => $suggestions]);
    }
}
