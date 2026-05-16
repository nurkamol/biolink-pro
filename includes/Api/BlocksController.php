<?php
/**
 * REST controller for block registry + per-page block operations.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Blocks\BlockRegistry;
use BioLinkPro\Frontend\Repository\PageRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class BlocksController extends AbstractController
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly PageRepository $repository
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/blocks',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/blocks',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'append'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'id'   => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_key'],
                    'data' => ['type' => 'object', 'required' => false],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/blocks/(?P<uuid>[0-9a-f-]{36})',
            [
                [
                    'methods'             => [WP_REST_Server::EDITABLE, 'PATCH'],
                    'callback'            => [$this, 'update'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => [
                        'id'   => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                        'uuid' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_key'],
                        'data' => ['type' => 'object', 'required' => false],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'destroy'],
                    'permission_callback' => $this->requireCap('biolink_manage_pages'),
                    'args'                => [
                        'id'   => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                        'uuid' => ['type' => 'string'],
                    ],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/blocks/reorder',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'reorder'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'id'    => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'order' => ['type' => 'array', 'required' => true],
                ],
            ]
        );
    }

    public function index(WP_REST_Request $_request): WP_REST_Response
    {
        return $this->ok($this->registry->describe());
    }

    public function append(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $type = (string) $request['type'];
        if ($type === '') {
            return $this->error('block_type_required', __('Block type is required.', 'biolink-pro'), 400);
        }

        $entry = $this->repository->appendBlock(
            $id,
            [
                'type' => $type,
                'data' => is_array($request['data'] ?? null) ? $request['data'] : [],
            ]
        );
        if ($entry === null) {
            return $this->error('append_failed', __('Could not append block.', 'biolink-pro'), 500);
        }
        return $this->ok($entry, 201);
    }

    public function update(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $uuid = (string) $request['uuid'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $patch = [];
        if ($request->has_param('type')) {
            $patch['type'] = (string) $request['type'];
        }
        if ($request->has_param('data') && is_array($request['data'])) {
            $patch['data'] = $request['data'];
        }
        if ($patch === []) {
            return $this->error('no_changes', __('No block fields supplied.', 'biolink-pro'), 400);
        }

        $updated = $this->repository->updateBlock($id, $uuid, $patch);
        if ($updated === null) {
            return $this->error('block_not_found', __('Block not found on this page.', 'biolink-pro'), 404);
        }
        return $this->ok($updated);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $uuid = (string) $request['uuid'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }
        if (! $this->repository->deleteBlock($id, $uuid)) {
            return $this->error('block_not_found', __('Block not found on this page.', 'biolink-pro'), 404);
        }
        return $this->ok(['deleted' => true, 'uuid' => $uuid]);
    }

    public function reorder(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id   = (int) $request['id'];
        $page = $this->repository->findById($id);
        if ($page === null) {
            return $this->error('page_not_found', __('Bio page not found.', 'biolink-pro'), 404);
        }

        $raw_order = $request['order'];
        if (! is_array($raw_order)) {
            return $this->error('bad_order', __('order must be an array of uuids.', 'biolink-pro'), 400);
        }

        $uuids = [];
        foreach ($raw_order as $candidate) {
            if (is_string($candidate) && PageRepository::isValidUuid($candidate)) {
                $uuids[] = $candidate;
            }
        }

        if (! $this->repository->reorderBlocks($id, $uuids)) {
            return $this->error('reorder_failed', __('Could not save block order.', 'biolink-pro'), 500);
        }
        return $this->ok($this->repository->getData($id)['blocks']);
    }
}
