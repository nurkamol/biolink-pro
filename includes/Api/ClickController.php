<?php
/**
 * Public click-tracker endpoint: GET /click/{link_id} → 302 to the destination.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Analytics\Tracker;
use WP_Error;
use WP_REST_Request;

defined('ABSPATH') || exit;

final class ClickController extends AbstractController
{
    public function __construct(private readonly Tracker $tracker)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/click/(?P<id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'track'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'id'  => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'ref' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ]
        );
    }

    public function track(WP_REST_Request $request): WP_Error|null
    {
        global $wpdb;
        $id    = (int) $request['id'];
        $table = $wpdb->prefix . 'biolink_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT id, page_id, url, utm_params FROM {$table} WHERE id = %d AND is_active = 1 LIMIT 1", $id),
            ARRAY_A
        );
        if (! is_array($row) || empty($row['url'])) {
            return $this->error('link_not_found', __('Link not found.', 'biolink-pro'), 404);
        }

        // Rate-limit clicks: 10 per link per IP per 60s.
        $ip       = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $bucket   = 'biolink_click_' . $id . '_' . md5($ip);
        $hits     = (int) get_transient($bucket);
        $rate_ok  = $hits < 10;
        if ($rate_ok) {
            set_transient($bucket, $hits + 1, 60);
        }

        if ($rate_ok) {
            $ua_raw = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
            $ua     = Tracker::classifyUa($ua_raw);
            // Skip recording bot traffic (still 302-redirect them so functional links work).
            if ($ua['device'] !== 'bot') {
                $event = [
                    'link_id'       => (int) $row['id'],
                    'page_id'       => (int) $row['page_id'],
                    'ip_hash'       => Tracker::hashIp($ip),
                    'device'        => $ua['device'],
                    'browser'       => $ua['browser'],
                    'os'            => $ua['os'],
                    'referrer_host' => Tracker::referrerHost((string) ($_SERVER['HTTP_REFERER'] ?? '')),
                    'utm_source'    => (string) $request->get_param('utm_source'),
                    'utm_medium'    => (string) $request->get_param('utm_medium'),
                    'utm_campaign'  => (string) $request->get_param('utm_campaign'),
                ];
                /**
                 * Short-circuit click recording. Return false to skip.
                 *
                 * @param bool                 $allow
                 * @param array<string, mixed> $event
                 */
                if (apply_filters('biolink/click/before', true, $event)) {
                    $this->tracker->recordClick($event);
                }
            }
        }

        $destination = (string) $row['url'];
        $utm_params  = (string) ($row['utm_params'] ?? '');
        if ($utm_params !== '') {
            $extra = [];
            parse_str(ltrim($utm_params, '?&'), $extra);
            $clean = [];
            foreach ($extra as $k => $v) {
                if (is_string($k) && str_starts_with($k, 'utm_') && is_scalar($v)) {
                    $clean[$k] = (string) $v;
                }
            }
            if ($clean !== []) {
                $destination = add_query_arg($clean, $destination);
            }
        }

        nocache_headers();
        wp_redirect(esc_url_raw($destination), 302, 'BioLink-Pro');
        exit;
    }
}
