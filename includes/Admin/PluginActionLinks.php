<?php
/**
 * Adds quick links beside "Deactivate" on the wp-admin Plugins screen.
 *
 * @package BioLinkPro\Admin
 */

declare(strict_types=1);

namespace BioLinkPro\Admin;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

/**
 * Surfaces three high-traffic destinations from the Plugins row:
 *   - Settings  →  BioLinks → Settings admin page
 *   - Pages     →  BioLinks → Pages admin page (the main dashboard)
 *   - What's New → BioLinks → What's New (release history)
 *
 * Also adds meta-row links to the GitHub source and "View details" modal.
 */
final class PluginActionLinks implements Bootable
{
    public function boot(): void
    {
        add_filter('plugin_action_links_' . BIOLINK_BASENAME, [$this, 'addActionLinks']);
        add_filter('plugin_row_meta', [$this, 'addRowMeta'], 10, 2);
    }

    /**
     * @param array<string, string> $links
     * @return array<string, string>
     */
    public function addActionLinks(array $links): array
    {
        $extra = [
            'pages'     => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=' . Menu::MENU_SLUG)),
                esc_html__('Pages', 'biolink-pro')
            ),
            'settings'  => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=' . Menu::SETTINGS_SLUG)),
                esc_html__('Settings', 'biolink-pro')
            ),
            'changelog' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=' . Menu::CHANGELOG_SLUG)),
                esc_html__("What's New", 'biolink-pro')
            ),
        ];

        // Prepend so they appear before the default Deactivate link.
        return $extra + $links;
    }

    /**
     * @param array<int, string> $meta
     * @param string             $file
     * @return array<int, string>
     */
    public function addRowMeta(array $meta, string $file): array
    {
        if ($file !== BIOLINK_BASENAME) {
            return $meta;
        }

        $meta[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url('https://github.com/nurkamol/biolink-pro'),
            esc_html__('GitHub', 'biolink-pro')
        );

        $meta[] = sprintf(
            '<a href="%s" class="thickbox open-plugin-details-modal">%s</a>',
            esc_url(
                self_admin_url('plugin-install.php?tab=plugin-information&plugin=biolink-pro&TB_iframe=true&width=600&height=550')
            ),
            esc_html__('View details', 'biolink-pro')
        );

        return $meta;
    }
}
