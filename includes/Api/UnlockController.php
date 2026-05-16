<?php
/**
 * Public REST endpoint that verifies a passcode and returns the rendered
 * block HTML so the admin's inline modal can swap it in without a reload.
 *
 * POST /biolink/v1/unlock/{page_id}/{uuid}  body: { passcode: string }
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Frontend\PageRenderer;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Frontend\UnlockHandler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class UnlockController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $repository,
        private readonly PageRenderer $renderer
    ) {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/unlock/(?P<page_id>\d+)/(?P<uuid>[A-Za-z0-9_\-]+)',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'unlock'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'page_id'  => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                    'uuid'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                    'passcode' => ['type' => 'string', 'required' => true],
                ],
            ]
        );
    }

    public function unlock(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id   = (int) $request['page_id'];
        $uuid      = (string) $request['uuid'];
        $submitted = is_string($request['passcode'] ?? null) ? (string) $request['passcode'] : '';

        if ($submitted === '') {
            return $this->error('empty_passcode', __('Passcode is required.', 'biolink-pro'), 400);
        }

        $bundle = $this->repository->findById($page_id);
        if ($bundle === null) {
            return $this->error('page_not_found', __('Page not found.', 'biolink-pro'), 404);
        }

        $block = null;
        foreach ((array) ($bundle['data']['blocks'] ?? []) as $candidate) {
            if (is_array($candidate) && ($candidate['uuid'] ?? null) === $uuid) {
                $block = $candidate;
                break;
            }
        }
        if ($block === null) {
            return $this->error('block_not_found', __('Block not found.', 'biolink-pro'), 404);
        }

        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $hash = isset($data['_passcode_hash']) ? (string) $data['_passcode_hash'] : '';
        if ($hash === '') {
            // Not locked anymore. Render it directly.
            return $this->ok([
                'ok'   => true,
                'html' => $this->renderUnlockedBlock($page_id, $block),
            ]);
        }

        if (! wp_check_password($submitted, $hash, 0)) {
            return $this->error('bad_passcode', __('Incorrect passcode.', 'biolink-pro'), 401);
        }

        // Persist the unlock for this session + inject the cookie into the
        // current request so PageRenderer sees this block as unlocked.
        UnlockHandler::rememberUnlockForRequest($page_id, $uuid);

        do_action('biolink/link/unlocked', $uuid, $page_id);

        return $this->ok([
            'ok'   => true,
            'html' => $this->renderUnlockedBlock($page_id, $block),
        ]);
    }

    /**
     * Render a single block via PageRenderer so the returned HTML matches
     * exactly what a normal page load would produce. We wrap the global
     * post temporarily so blocks that call `get_the_ID()` get the right id.
     *
     * @param array<string, mixed> $block
     */
    private function renderUnlockedBlock(int $page_id, array $block): string
    {
        $post = get_post($page_id);
        if (! $post) {
            return '';
        }
        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);

        // renderBlocks wraps everything in <div class="bio-blocks">.
        // For an inline swap we want just the block markup.
        $html = $this->renderer->renderBlocks([$block]);
        $html = preg_replace('#^<div class="bio-blocks">|</div>$#', '', (string) $html);

        wp_reset_postdata();
        if ($previous) {
            $GLOBALS['post'] = $previous;
        }
        return (string) $html;
    }
}
