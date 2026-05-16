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
}
