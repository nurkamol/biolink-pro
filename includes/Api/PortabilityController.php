<?php
/**
 * REST: export a page as JSON; import a JSON payload as a new draft page.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class PortabilityController extends AbstractController
{
    public const PAYLOAD_VERSION = 1;

    public function __construct(private readonly PageRepository $repository)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/export',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'export'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'id'       => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'download' => ['type' => 'boolean', 'default' => false],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/import',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'import'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
            ]
        );
    }

    public function export(WP_REST_Request $request)
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $payload = [
            'biolink_export_version' => self::PAYLOAD_VERSION,
            'exported_at'            => current_time('c'),
            'source'                 => home_url(),
            'page'                   => [
                'title'    => $page['post']->post_title,
                'slug'     => $page['post']->post_name,
                'theme'    => $page['data']['theme'] ?? 'mono',
                'settings' => $page['data']['settings'] ?? [],
                'blocks'   => $page['data']['blocks'] ?? [],
                'seo'      => $page['data']['seo'] ?? [],
            ],
        ];

        if ((bool) $request['download']) {
            $filename = sprintf('biolink-%s-%s.json', $page['post']->post_name ?: $id, gmdate('Y-m-d'));
            nocache_headers();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        return $this->ok($payload);
    }

    public function import(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $body = $request->get_json_params();
        if (! is_array($body)) {
            // Fallback: raw body as JSON string (file upload via fetch with text/plain).
            $raw = $request->get_body();
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        if (! is_array($body) || empty($body['page']) || ! is_array($body['page'])) {
            return $this->error('bad_payload', __('Invalid import payload.', 'biolink-pro'), 400);
        }

        $version = (int) ($body['biolink_export_version'] ?? 0);
        if ($version > self::PAYLOAD_VERSION) {
            return $this->error(
                'version_mismatch',
                __('This export was produced by a newer plugin version. Update first.', 'biolink-pro'),
                400
            );
        }

        $src   = $body['page'];
        $title = isset($src['title']) ? (string) $src['title'] : __('Imported bio page', 'biolink-pro');

        $post_id = wp_insert_post([
            'post_type'   => BioLinkPagePostType::POST_TYPE,
            'post_status' => 'draft',
            'post_title'  => sanitize_text_field($title),
            'post_author' => get_current_user_id(),
        ], true);
        if (is_wp_error($post_id)) {
            return $this->error('insert_failed', $post_id->get_error_message(), 500);
        }

        // Re-uuid blocks so they don't collide with whatever's in wp_biolink_links on this site.
        $blocks = [];
        foreach ((array) ($src['blocks'] ?? []) as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }
            $blocks[] = [
                'uuid' => wp_generate_uuid4(),
                'type' => (string) $block['type'],
                'data' => is_array($block['data'] ?? null) ? $block['data'] : [],
            ];
        }

        $this->repository->saveData((int) $post_id, [
            'theme'    => isset($src['theme']) ? (string) $src['theme'] : 'mono',
            'settings' => is_array($src['settings'] ?? null) ? $src['settings'] : [],
            'blocks'   => $blocks,
            'seo'      => is_array($src['seo'] ?? null) ? $src['seo'] : [],
        ]);

        $fresh = $this->repository->findById((int) $post_id);
        return $this->ok([
            'id'    => (int) $post_id,
            'title' => $fresh['post']->post_title,
            'slug'  => $fresh['post']->post_name,
            'url'   => get_permalink($fresh['post']),
        ], 201);
    }
}
