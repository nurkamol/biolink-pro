<?php
/**
 * Deactivation hook handler.
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

defined('ABSPATH') || exit;

/**
 * Runs when the plugin is deactivated (not deleted).
 *
 * Data is preserved — uninstall.php handles destructive cleanup.
 */
final class Deactivator
{
    public static function deactivate(): void
    {
        self::clearScheduledEvents();
        flush_rewrite_rules(false);

        /**
         * Fires at the end of plugin deactivation.
         */
        do_action('biolink/plugin/deactivated');
    }

    private static function clearScheduledEvents(): void
    {
        $hooks = [
            'biolink/cron/prune_rate_limit',
            'biolink/cron/prune_analytics',
        ];

        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            while ($timestamp !== false) {
                wp_unschedule_event($timestamp, $hook);
                $timestamp = wp_next_scheduled($hook);
            }
        }
    }
}
