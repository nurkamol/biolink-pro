<?php
/**
 * REST endpoints for listing + restoring page revisions.
 *
 * GET  /pages/{id}/revisions
 * POST /pages/{id}/revisions/{rev_id}/restore
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Frontend\Repository\RevisionRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class RevisionsController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly RevisionRepository $revisions
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/revisions',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/pages/(?P<id>\d+)/revisions/(?P<rev_id>\d+)/restore',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'restore'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'id'     => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'rev_id' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        return $this->ok($this->revisions->listForPage((int) $request['id']));
    }

    public function restore(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id = (int) $request['id'];
        $rev_id  = (int) $request['rev_id'];

        $snapshot = $this->revisions->get($rev_id, $page_id);
        if ($snapshot === null) {
            return $this->error('revision_not_found', __('Revision not found.', 'biolink-pro'), 404);
        }

        if (! $this->pages->saveData($page_id, $snapshot)) {
            return $this->error('restore_failed', __('Could not restore revision.', 'biolink-pro'), 500);
        }

        // Return the page bundle post-restore so the admin can swap state in.
        $bundle = $this->pages->findById($page_id);
        return $this->ok([
            'restored' => true,
            'rev_id'   => $rev_id,
            'page'     => $bundle ? $bundle['data'] : null,
        ]);
    }
}
