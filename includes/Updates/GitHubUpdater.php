<?php
/**
 * GitHub Releases → WordPress plugin update bridge.
 *
 * @package BioLinkPro\Updates
 */

declare(strict_types=1);

namespace BioLinkPro\Updates;

use BioLinkPro\Core\Bootable;
use stdClass;

defined('ABSPATH') || exit;

/**
 * Makes WordPress's built-in plugin updater check the BioLink Pro GitHub repo
 * for a newer release and offer one-click updates from the Plugins screen.
 *
 * Approach:
 *   - GET /repos/{user}/{repo}/releases               → list of releases (cached)
 *   - GET /repos/{user}/{repo}/releases/latest        → latest stable release (cached)
 *   - Inject into `update_plugins` transient when current < latest.
 *   - Filter `plugins_api` so "View version X.X.X details" renders our changelog.
 *   - Rename unpacked source dir on `upgrader_source_selection` so the new
 *     install lands at `wp-content/plugins/biolink-pro/`.
 */
final class GitHubUpdater implements Bootable
{
    private const CACHE_TTL_LIST   = HOUR_IN_SECONDS * 12;
    private const CACHE_TTL_LATEST = HOUR_IN_SECONDS * 12;
    private const CACHE_KEY_LIST   = 'biolink_pro_gh_releases';
    private const CACHE_KEY_LATEST = 'biolink_pro_gh_release_latest';

    public function __construct(
        private readonly string $user,
        private readonly string $repo,
        private readonly string $pluginFile,
        private readonly string $currentVersion
    ) {
    }

    public function boot(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
        add_filter('upgrader_source_selection', [$this, 'renameSourceDir'], 10, 4);
        add_action('upgrader_process_complete', [$this, 'clearCacheAfterUpdate'], 10, 2);
    }

    public function pluginBasename(): string
    {
        return plugin_basename($this->pluginFile);
    }

    public function pluginSlug(): string
    {
        return dirname($this->pluginBasename());
    }

    /**
     * Get the latest stable release, cached.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestRelease(bool $force = false): ?array
    {
        if (! $force) {
            $cached = get_transient(self::CACHE_KEY_LATEST);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $url      = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->user, $this->repo);
        $response = wp_remote_get($url, $this->githubRequestArgs());
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            // Cache the miss for 1 hour so we don't hammer the API on failure.
            set_transient(self::CACHE_KEY_LATEST, [], HOUR_IN_SECONDS);
            return null;
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($body) || empty($body['tag_name'])) {
            return null;
        }

        set_transient(self::CACHE_KEY_LATEST, $body, self::CACHE_TTL_LATEST);
        return $body;
    }

    /**
     * Get the last ~20 releases for the changelog page.
     *
     * @return list<array<string, mixed>>
     */
    public function getReleases(bool $force = false): array
    {
        if (! $force) {
            $cached = get_transient(self::CACHE_KEY_LIST);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $url      = sprintf('https://api.github.com/repos/%s/%s/releases?per_page=20', $this->user, $this->repo);
        $response = wp_remote_get($url, $this->githubRequestArgs());
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::CACHE_KEY_LIST, [], HOUR_IN_SECONDS);
            return [];
        }

        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (! is_array($body)) {
            return [];
        }

        // Drop prereleases — release policy: stable-only.
        $stable = array_values(array_filter(
            $body,
            static fn($r): bool => is_array($r) && empty($r['prerelease']) && empty($r['draft'])
        ));

        set_transient(self::CACHE_KEY_LIST, $stable, self::CACHE_TTL_LIST);
        return $stable;
    }

    /**
     * Convert a release's `tag_name` (`v0.2.0`) to a comparable version string (`0.2.0`).
     */
    public static function tagToVersion(string $tag): string
    {
        return ltrim($tag, 'vV');
    }

    /**
     * Find the release asset matching `biolink-pro-vX.Y.Z.zip`.
     *
     * @param array<string, mixed> $release
     */
    public function findAssetUrl(array $release): ?string
    {
        if (empty($release['assets']) || ! is_array($release['assets'])) {
            return null;
        }

        $tag      = (string) ($release['tag_name'] ?? '');
        $expected = $this->pluginSlug() . '-' . $tag . '.zip';

        foreach ($release['assets'] as $asset) {
            if (
                is_array($asset)
                && ! empty($asset['name'])
                && ! empty($asset['browser_download_url'])
                && (string) $asset['name'] === $expected
            ) {
                return (string) $asset['browser_download_url'];
            }
        }

        // Fallback: any zip asset that starts with the plugin slug.
        foreach ($release['assets'] as $asset) {
            if (
                is_array($asset)
                && ! empty($asset['name'])
                && ! empty($asset['browser_download_url'])
                && preg_match('/^' . preg_quote($this->pluginSlug(), '/') . '-.*\.zip$/i', (string) $asset['name'])
            ) {
                return (string) $asset['browser_download_url'];
            }
        }

        return null;
    }

    /**
     * Hook: pre_set_site_transient_update_plugins
     *
     * @param object|mixed $transient
     * @return object|mixed
     */
    public function injectUpdate($transient)
    {
        if (! is_object($transient)) {
            return $transient;
        }

        $release = $this->getLatestRelease();
        if ($release === null || empty($release['tag_name'])) {
            return $transient;
        }

        $latest = self::tagToVersion((string) $release['tag_name']);
        if (version_compare($latest, $this->currentVersion, '<=')) {
            // Make sure stale entries are cleared.
            if (isset($transient->response[$this->pluginBasename()])) {
                unset($transient->response[$this->pluginBasename()]);
            }
            return $transient;
        }

        $package = $this->findAssetUrl($release);
        if ($package === null) {
            // No installable zip on this release → don't offer an update.
            return $transient;
        }

        $entry                  = new stdClass();
        $entry->id              = sprintf('github.com/%s/%s', $this->user, $this->repo);
        $entry->slug            = $this->pluginSlug();
        $entry->plugin          = $this->pluginBasename();
        $entry->new_version     = $latest;
        $entry->url             = (string) ($release['html_url'] ?? sprintf('https://github.com/%s/%s', $this->user, $this->repo));
        $entry->package         = $package;
        $entry->icons           = [];
        $entry->banners         = [];
        $entry->banners_rtl     = [];
        $entry->tested          = $this->testedWpVersion();
        $entry->requires_php    = BIOLINK_MIN_PHP;
        $entry->compatibility   = new stdClass();

        if (! isset($transient->response) || ! is_array($transient->response)) {
            $transient->response = [];
        }
        $transient->response[$this->pluginBasename()] = $entry;

        return $transient;
    }

    /**
     * Hook: plugins_api — supplies the "View version details" modal data.
     *
     * @param false|object|array<string, mixed> $result
     * @param string                            $action
     * @param object                            $args
     * @return false|object|array<string, mixed>
     */
    public function pluginInfo($result, string $action, $args)
    {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (! isset($args->slug) || $args->slug !== $this->pluginSlug()) {
            return $result;
        }

        $release = $this->getLatestRelease();
        if ($release === null) {
            return $result;
        }

        $latest    = self::tagToVersion((string) ($release['tag_name'] ?? ''));
        $body_html = MarkdownRenderer::render((string) ($release['body'] ?? ''));
        $package   = $this->findAssetUrl($release);

        $info                = new stdClass();
        $info->name          = 'BioLink Pro';
        $info->slug          = $this->pluginSlug();
        $info->version       = $latest;
        $info->author        = '<a href="https://github.com/' . esc_attr($this->user) . '">Nurkamol Vakhidov</a>';
        $info->homepage      = sprintf('https://github.com/%s/%s', $this->user, $this->repo);
        $info->requires      = BIOLINK_MIN_WP;
        $info->requires_php  = BIOLINK_MIN_PHP;
        $info->tested        = $this->testedWpVersion();
        $info->last_updated  = (string) ($release['published_at'] ?? '');
        $info->download_link = $package ?? '';
        $info->trunk         = $package ?? '';
        $info->sections      = [
            'description' => '<p>' . esc_html__(
                'Self-hosted bio link / link-in-bio builder. Drag-and-drop blocks, themes, analytics, QR codes, monetization.',
                'biolink-pro'
            ) . '</p>',
            'changelog'   => $body_html !== ''
                ? $body_html
                : '<p>' . esc_html__('No release notes available.', 'biolink-pro') . '</p>',
        ];

        return $info;
    }

    /**
     * Hook: upgrader_source_selection — ensure the unpacked directory matches the plugin slug.
     *
     * GitHub-attached release zips already use `biolink-pro/`, but auto-generated
     * source archives use `biolink-pro-X.X.X/`. Rename to the canonical slug so
     * WordPress installs us back to the same directory.
     *
     * @param string|\WP_Error $source
     * @param string           $remote_source
     * @param object|null      $upgrader
     * @param array<mixed>     $hook_extra
     */
    public function renameSourceDir($source, $remote_source, $upgrader = null, $hook_extra = [])
    {
        global $wp_filesystem;

        if (! is_string($source) || ! $wp_filesystem) {
            return $source;
        }

        // Only act on our own plugin updates.
        $is_ours = false;
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->pluginBasename()) {
            $is_ours = true;
        }
        if (! $is_ours && str_contains(basename($source), $this->pluginSlug())) {
            $is_ours = true;
        }
        if (! $is_ours) {
            return $source;
        }

        $desired = trailingslashit($remote_source) . $this->pluginSlug();
        $current = untrailingslashit($source);

        if ($current === untrailingslashit($desired)) {
            return $source;
        }

        if ($wp_filesystem->move($current, $desired) === true) {
            return trailingslashit($desired);
        }

        return $source;
    }

    /**
     * Hook: upgrader_process_complete — bust caches after any plugin install/update completes.
     *
     * @param object       $_upgrader
     * @param array<mixed> $hook_extra
     */
    public function clearCacheAfterUpdate($_upgrader, $hook_extra): void
    {
        if (! is_array($hook_extra)) {
            return;
        }

        $touched_us = false;
        if (($hook_extra['action'] ?? '') === 'update' && ($hook_extra['type'] ?? '') === 'plugin') {
            $plugins = $hook_extra['plugins'] ?? [];
            if (is_array($plugins) && in_array($this->pluginBasename(), $plugins, true)) {
                $touched_us = true;
            }
        }

        if ($touched_us) {
            $this->flushCache();
        }
    }

    public function flushCache(): void
    {
        delete_transient(self::CACHE_KEY_LIST);
        delete_transient(self::CACHE_KEY_LATEST);
    }

    /**
     * "Tested up to" WordPress version. We can't auto-derive this, so use core's reported version
     * as a sensible default — the maintainer should bump it deliberately when shipping.
     */
    private function testedWpVersion(): string
    {
        global $wp_version;
        return is_string($wp_version) ? $wp_version : BIOLINK_MIN_WP;
    }

    /**
     * @return array<string, mixed>
     */
    private function githubRequestArgs(): array
    {
        return [
            'timeout' => 10,
            'headers' => [
                'Accept'               => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent'           => 'BioLink-Pro-WP-Updater/' . $this->currentVersion,
            ],
        ];
    }
}
