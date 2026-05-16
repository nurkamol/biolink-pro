<?php
/**
 * Public page-view beacon: POST /track/view { page_id }
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Analytics\Tracker;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class TrackController extends AbstractController
{
    public function __construct(private readonly Tracker $tracker)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/track/view',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'view'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page_id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    public function view(WP_REST_Request $request): WP_REST_Response
    {
        $page_id = (int) $request['page_id'];
        if ($page_id <= 0 || get_post_type($page_id) !== BioLinkPagePostType::POST_TYPE) {
            return $this->ok(['ok' => false]);
        }

        $ip      = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $bucket  = 'biolink_view_' . $page_id . '_' . md5($ip);
        $hits    = (int) get_transient($bucket);
        if ($hits >= 30) {
            return $this->ok(['ok' => true, 'rate_limited' => true]);
        }
        set_transient($bucket, $hits + 1, 60);

        $ua_raw = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $ua     = Tracker::classifyUa($ua_raw);
        if ($ua['device'] === 'bot') {
            return $this->ok(['ok' => true, 'bot' => true]);
        }

        $this->tracker->recordView([
            'page_id'       => $page_id,
            'ip_hash'       => Tracker::hashIp($ip),
            'device'        => $ua['device'],
            'browser'       => $ua['browser'],
            'os'            => $ua['os'],
            'referrer_host' => Tracker::referrerHost((string) ($_SERVER['HTTP_REFERER'] ?? '')),
        ]);

        return $this->ok(['ok' => true]);
    }
}
