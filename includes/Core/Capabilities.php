<?php
/**
 * Custom capabilities registry.
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

use WP_Role;

defined('ABSPATH') || exit;

/**
 * Defines and grants the plugin's custom capabilities.
 *
 * See docs/SECURITY.md → "Custom capabilities" for the canonical mapping.
 */
final class Capabilities
{
    /**
     * Capability → list of default roles to grant on activation.
     *
     * @var array<string, list<string>>
     */
    private const CAP_MAP = [
        'biolink_manage_pages'        => ['administrator', 'editor'],
        'biolink_publish_pages'       => ['administrator', 'editor'],
        'biolink_manage_themes'       => ['administrator'],
        'biolink_view_analytics'      => ['administrator', 'editor'],
        'biolink_manage_integrations' => ['administrator'],
        'biolink_use_ai'              => ['administrator', 'editor'],
    ];

    /**
     * Grant every plugin capability to its default roles. Idempotent.
     */
    public function install(): void
    {
        foreach (self::CAP_MAP as $cap => $roles) {
            foreach ($roles as $role_name) {
                $role = get_role($role_name);
                if ($role instanceof WP_Role && ! $role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    /**
     * Strip every plugin capability from every role. Used on uninstall.
     */
    public function uninstall(): void
    {
        global $wp_roles;

        if (! isset($wp_roles) || ! ($wp_roles instanceof \WP_Roles)) {
            $wp_roles = wp_roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }

        foreach (array_keys(self::CAP_MAP) as $cap) {
            foreach ($wp_roles->roles as $role_name => $_data) {
                $role = get_role($role_name);
                if ($role instanceof WP_Role && $role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::CAP_MAP);
    }
}
