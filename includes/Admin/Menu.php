<?php
/**
 * Top-level admin menu + mount point for the React app.
 *
 * @package BioLinkPro\Admin
 */

declare(strict_types=1);

namespace BioLinkPro\Admin;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class Menu implements Bootable
{
    public const MENU_SLUG     = 'biolink-pro';
    public const SETTINGS_SLUG = 'biolink-pro-settings';

    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    public function registerMenu(): void
    {
        $hook = add_menu_page(
            __('Bio Links', 'biolink-pro'),
            __('Bio Links', 'biolink-pro'),
            'biolink_manage_pages',
            self::MENU_SLUG,
            [$this, 'renderApp'],
            'dashicons-admin-links',
            58
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'biolink-pro'),
            __('Dashboard', 'biolink-pro'),
            'biolink_manage_pages',
            self::MENU_SLUG,
            [$this, 'renderApp']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'biolink-pro'),
            __('Settings', 'biolink-pro'),
            'biolink_manage_pages',
            self::SETTINGS_SLUG,
            [$this, 'renderApp']
        );

        do_action('biolink/admin/menu/registered', $hook);
    }

    /**
     * The React app reads `window.location.hash` for its own routing; every
     * registered submenu just renders the same mount point.
     */
    public function renderApp(): void
    {
        echo '<div id="biolink-pro-app" class="biolink-pro-app-root"></div>';
    }

    /**
     * @return list<string>
     */
    public static function pageHooks(): array
    {
        return [
            'toplevel_page_' . self::MENU_SLUG,
            'bio-links_page_' . self::SETTINGS_SLUG,
        ];
    }

    /**
     * Check the current admin screen against any plugin page.
     */
    public static function isPluginScreen(string $hook): bool
    {
        return str_starts_with($hook, 'toplevel_page_' . self::MENU_SLUG)
            || str_starts_with($hook, 'bio-links_page_' . self::MENU_SLUG)
            || $hook === 'bio-links_page_' . self::SETTINGS_SLUG;
    }
}
