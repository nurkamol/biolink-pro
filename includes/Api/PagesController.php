<?php
/**
 * REST controller for `/biolink/v1/pages`.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class PagesController extends AbstractController
{
    public function __construct(private readonly PageRepository $repository)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/pages',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'index'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => [
                        'page'     => ['type' => 'integer', 'default' => 1, 'minimum' => 1, 'sanitize_callback' => 'absint'],
                        'per_page' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint'],
                        'status'   => ['type' => 'string', 'default' => 'any', 'sanitize_callback' => 'sanitize_key'],
                        'search'   => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => $this->writableArgs(true),
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'show'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
                ],
                [
                    'methods'             => [WP_REST_Server::EDITABLE, 'PATCH'],
                    'callback'            => [$this, 'update'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => array_merge(
                        ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
                        $this->writableArgs(false)
                    ),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'destroy'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => [
                        'id'    => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                        'force' => ['type' => 'boolean', 'default' => false],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/duplicate',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'duplicate'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/publish',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'publish'],
                'permission_callback' => $this->requireCap('biolink_publish_pages'),
                'args'                => [
                    'id'      => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'publish' => ['type' => 'string', 'default' => 'now', 'sanitize_callback' => 'sanitize_text_field'],
                ],
            ]
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function writableArgs(bool $for_create): array
    {
        return [
            'title'    => [
                'type'              => 'string',
                'required'          => $for_create,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'slug'     => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_title',
            ],
            'status'   => [
                'type'              => 'string',
                'required'          => false,
                'enum'              => ['draft', 'pending', 'publish', 'private', 'future'],
                'sanitize_callback' => 'sanitize_key',
            ],
            'theme'    => [
                'type'              => 'string',
                'required'          => false,
                'sanitize_callback' => 'sanitize_key',
            ],
            'settings' => [
                'type'     => 'object',
                'required' => false,
            ],
            'blocks'   => [
                'type'     => 'array',
                'required' => false,
            ],
            'seo'      => [
                'type'     => 'object',
                'required' => false,
            ],
        ];
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $query = new \WP_Query([
            'post_type'      => BioLinkPagePostType::POST_TYPE,
            'post_status'    => $request['status'] === 'any' ? ['draft', 'pending', 'publish', 'private', 'future'] : $request['status'],
            's'              => $request['search'],
            'posts_per_page' => (int) $request['per_page'],
            'paged'          => (int) $request['page'],
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $items = array_map([$this, 'shape'], $query->posts);

        $response = $this->ok($items);
        $response->header('X-WP-Total', (string) $query->found_posts);
        $response->header('X-WP-TotalPages', (string) $query->max_num_pages);
        return $response;
    }

    public function show(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page = $this->repository->findById((int) $request['id']);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }
        return $this->ok($this->shape($page['post'], $page['data']));
    }

    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post_arr = [
            'post_type'   => BioLinkPagePostType::POST_TYPE,
            'post_title'  => (string) $request['title'],
            'post_status' => $request['status'] ?? 'draft',
        ];
        if (! empty($request['slug'])) {
            $post_arr['post_name'] = (string) $request['slug'];
        }

        $post_id = wp_insert_post(wp_slash($post_arr), true);
        if (is_wp_error($post_id)) {
            return $this->error('create_failed', $post_id->get_error_message(), 400);
        }

        $this->repository->saveData(
            (int) $post_id,
            [
                'theme'    => isset($request['theme']) ? (string) $request['theme'] : 'minimal',
                'settings' => is_array($request['settings'] ?? null) ? $request['settings'] : [],
                'blocks'   => is_array($request['blocks'] ?? null) ? $request['blocks'] : [],
                'seo'      => is_array($request['seo'] ?? null) ? $request['seo'] : [],
            ]
        );

        $page = $this->repository->findById((int) $post_id);
        return $this->ok($this->shape($page['post'], $page['data']), 201);
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $post_patch = ['ID' => $id];
        if ($request->has_param('title')) {
            $post_patch['post_title'] = (string) $request['title'];
        }
        if ($request->has_param('slug') && ! empty($request['slug'])) {
            $post_patch['post_name'] = (string) $request['slug'];
        }
        if ($request->has_param('status')) {
            $post_patch['post_status'] = (string) $request['status'];
        }
        if (count($post_patch) > 1) {
            $result = wp_update_post(wp_slash($post_patch), true);
            if (is_wp_error($result)) {
                return $this->error('update_failed', $result->get_error_message(), 400);
            }
        }

        $data = $page['data'];
        if ($request->has_param('theme')) {
            $data['theme'] = (string) $request['theme'];
        }
        if ($request->has_param('settings') && is_array($request['settings'])) {
            $data['settings'] = $request['settings'];
        }
        if ($request->has_param('blocks') && is_array($request['blocks'])) {
            $data['blocks'] = $request['blocks'];
        }
        if ($request->has_param('seo') && is_array($request['seo'])) {
            $data['seo'] = $request['seo'];
        }
        $this->repository->saveData($id, $data);

        $fresh = $this->repository->findById($id);
        return $this->ok($this->shape($fresh['post'], $fresh['data']));
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id    = (int) $request['id'];
        $force = (bool) $request['force'];
        $page  = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }
        $deleted = wp_delete_post($id, $force);
        if ($deleted === false || $deleted === null) {
            return $this->error('delete_failed', __('Could not delete bio page.', 'biolink-pro'), 500);
        }
        return $this->ok(['deleted' => true, 'id' => $id, 'force' => $force]);
    }

    public function duplicate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $copy_id = wp_insert_post(
            wp_slash([
                'post_type'   => BioLinkPagePostType::POST_TYPE,
                'post_title'  => $page['post']->post_title . ' ' . __('(copy)', 'biolink-pro'),
                'post_status' => 'draft',
                'post_author' => get_current_user_id(),
            ]),
            true
        );
        if (is_wp_error($copy_id)) {
            return $this->error('duplicate_failed', $copy_id->get_error_message(), 400);
        }

        $data = $page['data'];
        foreach ($data['blocks'] as $i => $block) {
            $data['blocks'][$i]['uuid'] = wp_generate_uuid4();
        }
        $this->repository->saveData((int) $copy_id, $data);

        $fresh = $this->repository->findById((int) $copy_id);
        return $this->ok($this->shape($fresh['post'], $fresh['data']), 201);
    }

    public function publish(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $when   = (string) $request['publish'];
        $patch  = ['ID' => $id];
        if ($when === 'now' || $when === '') {
            $patch['post_status'] = 'publish';
        } else {
            $timestamp = strtotime($when);
            if ($timestamp === false) {
                return $this->error('bad_schedule', __('Invalid publish timestamp.', 'biolink-pro'), 400);
            }
            $patch['post_status']   = 'future';
            $patch['post_date']     = gmdate('Y-m-d H:i:s', $timestamp);
            $patch['post_date_gmt'] = gmdate('Y-m-d H:i:s', $timestamp);
        }

        $result = wp_update_post(wp_slash($patch), true);
        if (is_wp_error($result)) {
            return $this->error('publish_failed', $result->get_error_message(), 400);
        }

        $fresh = $this->repository->findById($id);
        return $this->ok($this->shape($fresh['post'], $fresh['data']));
    }

    /**
     * Convert a WP_Post into the JSON shape returned by the API.
     *
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>
     */
    private function shape(WP_Post $post, ?array $data = null): array
    {
        $data ??= $this->repository->getData($post->ID);
        return [
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'slug'       => $post->post_name,
            'status'     => $post->post_status,
            'author'     => (int) $post->post_author,
            'created'    => mysql_to_rfc3339($post->post_date_gmt),
            'modified'   => mysql_to_rfc3339($post->post_modified_gmt),
            'url'        => get_permalink($post),
            'theme'      => $data['theme'] ?? 'minimal',
            'settings'   => $data['settings'] ?? [],
            'blocks'     => $data['blocks'] ?? [],
            'seo'        => $data['seo'] ?? [],
        ];
    }
}
