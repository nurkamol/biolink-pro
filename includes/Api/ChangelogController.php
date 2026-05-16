<?php
/**
 * REST controller exposing GitHub release history + update status to the React admin.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Updates\GitHubUpdater;
use BioLinkPro\Updates\MarkdownRenderer;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class ChangelogController extends AbstractController
{
    public function __construct(private readonly GitHubUpdater $updater)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/changelog',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'force' => ['type' => 'boolean', 'default' => false],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/changelog/update-status',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'status'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
                'args'                => [
                    'force' => ['type' => 'boolean', 'default' => false],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/changelog/install-update',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'installUpdate'],
                'permission_callback' => static function (): bool {
                    return current_user_can('update_plugins');
                },
            ]
        );
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $force    = (bool) $request['force'];
        $releases = $this->updater->getReleases($force);

        $items = [];
        foreach ($releases as $release) {
            if (! is_array($release) || empty($release['tag_name'])) {
                continue;
            }
            $tag     = (string) $release['tag_name'];
            $version = GitHubUpdater::tagToVersion($tag);
            $items[] = [
                'tag'         => $tag,
                'version'     => $version,
                'name'        => (string) ($release['name'] ?? $tag),
                'date'        => (string) ($release['published_at'] ?? ''),
                'body_html'   => MarkdownRenderer::render((string) ($release['body'] ?? '')),
                'html_url'    => (string) ($release['html_url'] ?? ''),
                'is_current'  => version_compare($version, BIOLINK_VERSION, '=='),
                'is_newer'    => version_compare($version, BIOLINK_VERSION, '>'),
                'download_url' => $this->updater->findAssetUrl($release),
            ];
        }

        return $this->ok($items);
    }

    public function status(WP_REST_Request $request): WP_REST_Response
    {
        $force   = (bool) $request['force'];
        $release = $this->updater->getLatestRelease($force);

        $payload = [
            'current'          => BIOLINK_VERSION,
            'latest'           => null,
            'update_available' => false,
            'release_url'      => null,
            'download_url'     => null,
            'published_at'     => null,
        ];

        if ($release !== null && ! empty($release['tag_name'])) {
            $latest                       = GitHubUpdater::tagToVersion((string) $release['tag_name']);
            $payload['latest']            = $latest;
            $payload['update_available']  = version_compare($latest, BIOLINK_VERSION, '>');
            $payload['release_url']       = (string) ($release['html_url'] ?? '');
            $payload['download_url']      = $this->updater->findAssetUrl($release);
            $payload['published_at']      = (string) ($release['published_at'] ?? '');
        }

        return $this->ok($payload);
    }

    /**
     * Run an in-place upgrade of the BioLink Pro plugin using the latest GitHub release.
     *
     * Requires the `update_plugins` capability. Uses Core's Plugin_Upgrader so it
     * goes through the normal extraction + activation flow, including our
     * `upgrader_source_selection` filter that rewrites the GitHub-style folder name.
     */
    public function installUpdate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        unset($request);

        $release = $this->updater->getLatestRelease(true);
        if ($release === null || empty($release['tag_name'])) {
            return $this->error('no_release', __('No GitHub release found.', 'biolink-pro'), 502);
        }

        $latest = GitHubUpdater::tagToVersion((string) $release['tag_name']);
        if (version_compare($latest, BIOLINK_VERSION, '<=')) {
            return $this->ok([
                'status'  => 'already_latest',
                'current' => BIOLINK_VERSION,
                'latest'  => $latest,
                'message' => __('You are already on the latest version.', 'biolink-pro'),
            ]);
        }

        if ($this->updater->findAssetUrl($release) === null) {
            return $this->error(
                'no_asset',
                __('Latest release does not have a downloadable plugin zip.', 'biolink-pro'),
                502
            );
        }

        if (! function_exists('plugins_api')) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $was_active = is_plugin_active(BIOLINK_BASENAME);

        // Prime the update transient so any downstream filters see our entry.
        delete_site_transient('update_plugins');
        wp_update_plugins();

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->upgrade(BIOLINK_BASENAME);

        if ($result instanceof WP_Error) {
            return $this->error('upgrade_failed', $result->get_error_message(), 500);
        }
        if ($result === false) {
            $errors  = $skin->get_errors();
            $message = $errors instanceof WP_Error && $errors->has_errors()
                ? $errors->get_error_message()
                : __('Update failed. See WordPress logs for details.', 'biolink-pro');
            return $this->error('upgrade_failed', $message, 500);
        }

        if ($was_active && ! is_plugin_active(BIOLINK_BASENAME)) {
            // The upgrader briefly deactivates, then re-activates the plugin.
            // If we're still inactive, try once more so the user isn't left dark.
            $activated = activate_plugin(BIOLINK_BASENAME);
            if ($activated instanceof WP_Error) {
                return $this->error('activate_failed', $activated->get_error_message(), 500);
            }
        }

        return $this->ok([
            'status'   => 'updated',
            'current'  => $latest,
            'previous' => BIOLINK_VERSION,
            'message'  => sprintf(
                /* translators: %s: version string */
                __('Updated to %s. Reload the page to load the new admin bundle.', 'biolink-pro'),
                $latest
            ),
        ]);
    }
}
