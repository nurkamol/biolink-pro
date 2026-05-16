<?php
/**
 * Enqueue React admin bundle, scoped to plugin admin screens only.
 *
 * @package BioLinkPro\Admin
 */

declare(strict_types=1);

namespace BioLinkPro\Admin;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class Assets implements Bootable
{
    private const HANDLE = 'biolink-pro-admin';

    public function boot(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        if (! Menu::isPluginScreen($hook)) {
            return;
        }

        // Make the wp.media frame available to block editors (image gallery, video).
        wp_enqueue_media();

        $build_dir  = BIOLINK_PATH . 'assets/admin/';
        $build_url  = BIOLINK_URL . 'assets/admin/';
        $asset_file = $build_dir . 'main.asset.php';

        if (! file_exists($asset_file)) {
            add_action('admin_notices', static function (): void {
                printf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    esc_html__('BioLink Pro admin assets are missing. Run "npm install && npm run build" inside the plugin directory.', 'biolink-pro')
                );
            });
            return;
        }

        /** @var array{dependencies: list<string>, version: string} $asset */
        $asset = require $asset_file;

        wp_enqueue_script(
            self::HANDLE,
            $build_url . 'main.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            self::HANDLE,
            $build_url . 'main.css',
            [],
            $asset['version']
        );

        wp_set_script_translations(self::HANDLE, 'biolink-pro');

        wp_localize_script(
            self::HANDLE,
            'BIOLINK_PRO',
            [
                'restBase'  => esc_url_raw(rest_url('biolink/v1/')),
                'restNonce' => wp_create_nonce('wp_rest'),
                'adminUrl'  => esc_url_raw(admin_url('admin.php?page=' . Menu::MENU_SLUG)),
                'pluginUrl' => esc_url_raw(BIOLINK_URL),
                'version'   => BIOLINK_VERSION,
                'caps'      => [
                    'managePages'        => current_user_can('biolink_manage_pages'),
                    'publishPages'       => current_user_can('biolink_publish_pages'),
                    'manageThemes'       => current_user_can('biolink_manage_themes'),
                    'viewAnalytics'      => current_user_can('biolink_view_analytics'),
                    'manageIntegrations' => current_user_can('biolink_manage_integrations'),
                    'useAi'              => current_user_can('biolink_use_ai'),
                ],
            ]
        );
    }
}
