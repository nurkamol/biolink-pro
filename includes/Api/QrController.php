<?php
/**
 * REST endpoint for generating a page's QR code.
 *
 * GET /pages/{id}/qr?format=png|svg&fg=#000&bg=#fff&size=512
 * Returns JSON { url, mime } pointing at the cached file.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Qr\Generator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class QrController extends AbstractController
{
    public function __construct(private readonly Generator $generator)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/qr',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'show'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'id'     => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'format' => ['type' => 'string', 'default' => 'png', 'sanitize_callback' => 'sanitize_key'],
                    'fg'     => ['type' => 'string', 'default' => '#000000', 'sanitize_callback' => 'sanitize_text_field'],
                    'bg'     => ['type' => 'string', 'default' => '#FFFFFF', 'sanitize_callback' => 'sanitize_text_field'],
                    'size'   => ['type' => 'integer', 'default' => 512, 'sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id = (int) $request['id'];
        if (get_post_type($page_id) !== BioLinkPagePostType::POST_TYPE) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }
        $page_url = (string) get_permalink($page_id);
        if ($page_url === '') {
            return $this->error('no_url', __('Page has no public URL yet.', 'biolink-pro'), 400);
        }

        $result = $this->generator->generate($page_id, $page_url, [
            'format' => (string) $request['format'],
            'fg'     => (string) $request['fg'],
            'bg'     => (string) $request['bg'],
            'size'   => (int) $request['size'],
        ]);
        if ($result === null) {
            return $this->error('qr_failed', __('Could not generate QR code.', 'biolink-pro'), 500);
        }

        return $this->ok($result);
    }
}
