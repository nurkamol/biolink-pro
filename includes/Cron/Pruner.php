<?php
/**
 * Daily prune job: drops expired rate-limit buckets + analytics events past the
 * configured retention window.
 *
 * @package BioLinkPro\Cron
 */

declare(strict_types=1);

namespace BioLinkPro\Cron;

use BioLinkPro\Analytics\Tracker;
use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class Pruner implements Bootable
{
    public const HOOK_DAILY = 'biolink/cron/prune_daily';

    public function boot(): void
    {
        add_action(self::HOOK_DAILY, [$this, 'run']);
        add_action(Tracker::HOOK_RECORD_CLICK, [$this, 'onClickEvent'], 10, 1);
        add_action(Tracker::HOOK_RECORD_VIEW, [$this, 'onViewEvent'], 10, 1);
        add_action('init', [$this, 'maybeSchedule']);
    }

    public function maybeSchedule(): void
    {
        if (! wp_next_scheduled(self::HOOK_DAILY)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::HOOK_DAILY);
        }
    }

    /**
     * Wire the single-event callbacks emitted by Analytics\Tracker → persistence.
     *
     * @param array<string, mixed> $event
     */
    public function onClickEvent(array $event): void
    {
        ( new \BioLinkPro\Analytics\Tracker() )->persistClick($event);
    }

    /**
     * @param array<string, mixed> $event
     */
    public function onViewEvent(array $event): void
    {
        ( new \BioLinkPro\Analytics\Tracker() )->persistView($event);
    }

    public function run(): void
    {
        global $wpdb;

        // Rate-limit table: drop expired buckets
        $rate = $wpdb->prefix . 'biolink_rate_limit';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM {$rate} WHERE expires_at < %s", current_time('mysql', true)));

        // Analytics retention (default 365 days)
        $settings  = (array) get_option('biolink_settings', []);
        $retention = isset($settings['analytics_retention_days']) ? max(7, (int) $settings['analytics_retention_days']) : 365;
        $cutoff    = gmdate('Y-m-d H:i:s', time() - $retention * DAY_IN_SECONDS);
        $views     = $wpdb->prefix . 'biolink_views';
        $clicks    = $wpdb->prefix . 'biolink_clicks';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM {$views} WHERE viewed_at < %s", $cutoff));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM {$clicks} WHERE clicked_at < %s", $cutoff));

        /**
         * Fires after the daily prune job runs.
         */
        do_action('biolink/cron/pruned', $retention);
    }
}
