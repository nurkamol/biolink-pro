<?php
/**
 * REST: list bundled templates + apply one as a new draft page.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Templates\TemplateLibrary;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class TemplatesController extends AbstractController
{
    public function __construct(private readonly TemplateLibrary $library)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/templates',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
            ]
        );
        register_rest_route(
            self::NAMESPACE,
            '/templates/(?P<slug>[a-z0-9_-]+)/apply',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'apply'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'slug' => ['type' => 'string', 'sanitize_callback' => 'sanitize_key'],
                ],
            ]
        );
    }

    public function index(WP_REST_Request $_request): WP_REST_Response
    {
        return $this->ok($this->library->all());
    }

    public function apply(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $post = $this->library->apply((string) $request['slug']);
        if (is_wp_error($post)) {
            return $post;
        }
        return $this->ok([
            'id'    => $post->ID,
            'title' => $post->post_title,
            'slug'  => $post->post_name,
            'url'   => get_permalink($post),
        ], 201);
    }
}
