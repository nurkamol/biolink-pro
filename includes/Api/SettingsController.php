<?php
/**
 * REST endpoints for the global plugin settings + integration credentials.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Core\Crypto;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class SettingsController extends AbstractController
{
    private const SECRET_KEYS = ['openai_api_key', 'stripe_secret', 'paypal_secret', 'mailchimp_api_key'];

    public function registerRoutes(): void
    {
        $perm = function () {
            if (current_user_can('biolink_manage_integrations') || current_user_can('manage_options')) {
                return true;
            }
            return new \WP_Error(
                'biolink_forbidden',
                __('You do not have permission.', 'biolink-pro'),
                ['status' => rest_authorization_required_code()]
            );
        };

        register_rest_route(
            self::NAMESPACE,
            '/settings',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'read'],
                    'permission_callback' => $perm,
                ],
                [
                    'methods'             => [WP_REST_Server::EDITABLE, 'PATCH'],
                    'callback'            => [$this, 'update'],
                    'permission_callback' => $perm,
                ],
            ]
        );
    }

    public function read(WP_REST_Request $_request): WP_REST_Response
    {
        $general      = (array) get_option('biolink_settings', []);
        $integrations = (array) get_option('biolink_integrations', []);

        // Mask secrets — never echo them in full.
        $integrations_safe = [];
        foreach ($integrations as $key => $value) {
            if (in_array($key, self::SECRET_KEYS, true) && is_string($value) && $value !== '') {
                $integrations_safe[$key] = Crypto::mask($value);
                $integrations_safe[$key . '_set'] = true;
            } elseif (is_string($value) || is_bool($value) || is_int($value)) {
                $integrations_safe[$key] = $value;
            }
        }

        return $this->ok([
            'general'      => array_merge($this->generalDefaults(), $general),
            'integrations' => $integrations_safe,
        ]);
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $body = $request->get_json_params();
        if (! is_array($body)) {
            $body = [];
        }

        if (isset($body['general']) && is_array($body['general'])) {
            $current = (array) get_option('biolink_settings', []);
            $next    = array_merge($current, $this->sanitizeGeneral($body['general']));
            update_option('biolink_settings', $next, false);
        }

        if (isset($body['integrations']) && is_array($body['integrations'])) {
            $current = (array) get_option('biolink_integrations', []);
            $crypto  = new Crypto();
            foreach ($body['integrations'] as $key => $value) {
                $k = sanitize_key((string) $key);
                if ($k === '') {
                    continue;
                }
                if (in_array($k, self::SECRET_KEYS, true)) {
                    if (is_string($value) && $value !== '' && ! str_contains($value, '•')) {
                        $current[$k] = $crypto->encrypt($value);
                    }
                    if ($value === '' || $value === null) {
                        unset($current[$k]);
                    }
                } else {
                    $current[$k] = is_scalar($value) ? (is_bool($value) ? $value : (string) $value) : '';
                }
            }
            update_option('biolink_integrations', $current, false);
        }

        return $this->ok(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generalDefaults(): array
    {
        return [
            'analytics_retention_days' => 365,
            'show_credit'              => true,
            'allow_tracking'           => true,
            'ai_enabled'               => false,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function sanitizeGeneral(array $input): array
    {
        $out = [];
        if (isset($input['analytics_retention_days'])) {
            $out['analytics_retention_days'] = max(7, min(3650, (int) $input['analytics_retention_days']));
        }
        foreach (['show_credit', 'allow_tracking', 'ai_enabled'] as $bool_key) {
            if (array_key_exists($bool_key, $input)) {
                $out[$bool_key] = (bool) $input[$bool_key];
            }
        }
        return $out;
    }
}
