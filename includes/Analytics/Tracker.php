<?php
/**
 * Async writer for click + view events into the custom analytics tables.
 *
 * @package BioLinkPro\Analytics
 */

declare(strict_types=1);

namespace BioLinkPro\Analytics;

defined('ABSPATH') || exit;

final class Tracker
{
    public const HOOK_RECORD_CLICK = 'biolink/cron/record_click';
    public const HOOK_RECORD_VIEW  = 'biolink/cron/record_view';

    /**
     * Record a click. Heavy work (UA parsing, country lookup) runs through
     * `wp_schedule_single_event` so the click redirect stays fast.
     *
     * @param array<string, mixed> $event
     */
    public function recordClick(array $event): void
    {
        wp_schedule_single_event(time(), self::HOOK_RECORD_CLICK, [$event]);
    }

    /**
     * @param array<string, mixed> $event
     */
    public function recordView(array $event): void
    {
        wp_schedule_single_event(time(), self::HOOK_RECORD_VIEW, [$event]);
    }

    /**
     * @param array<string, mixed> $event
     */
    public function persistClick(array $event): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_clicks';
        $variant = (string) ($event['variant_key'] ?? '');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'link_id'       => (int) ($event['link_id'] ?? 0),
                'page_id'       => (int) ($event['page_id'] ?? 0),
                'clicked_at'    => current_time('mysql', true),
                'ip_hash'       => (string) ($event['ip_hash'] ?? ''),
                'country'       => substr((string) ($event['country'] ?? ''), 0, 2) ?: null,
                'device'        => (string) ($event['device'] ?? 'other'),
                'browser'       => substr((string) ($event['browser'] ?? ''), 0, 32) ?: null,
                'os'            => substr((string) ($event['os'] ?? ''), 0, 32) ?: null,
                'referrer_host' => substr((string) ($event['referrer_host'] ?? ''), 0, 191) ?: null,
                'utm_source'    => substr((string) ($event['utm_source'] ?? ''), 0, 64) ?: null,
                'utm_medium'    => substr((string) ($event['utm_medium'] ?? ''), 0, 64) ?: null,
                'utm_campaign'  => substr((string) ($event['utm_campaign'] ?? ''), 0, 64) ?: null,
                'variant_key'   => $variant !== '' ? substr($variant, 0, 32) : null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $event
     */
    public function persistView(array $event): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_views';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'page_id'       => (int) ($event['page_id'] ?? 0),
                'viewed_at'     => current_time('mysql', true),
                'ip_hash'       => (string) ($event['ip_hash'] ?? ''),
                'country'       => substr((string) ($event['country'] ?? ''), 0, 2) ?: null,
                'device'        => (string) ($event['device'] ?? 'other'),
                'browser'       => substr((string) ($event['browser'] ?? ''), 0, 32) ?: null,
                'os'            => substr((string) ($event['os'] ?? ''), 0, 32) ?: null,
                'referrer_host' => substr((string) ($event['referrer_host'] ?? ''), 0, 191) ?: null,
            ]
        );
    }

    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip . '|' . wp_salt('secure_auth'));
    }

    /**
     * Record a passcode-unlock event. Called via the `biolink/link/unlocked`
     * action so PageRenderer / UnlockController / UnlockHandler share the
     * same write path.
     */
    public function persistUnlock(string $uuid, int $page_id): void
    {
        if ($uuid === '' || $page_id <= 0) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_unlocks';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'page_id'     => $page_id,
                'block_uuid'  => $uuid,
                'unlocked_at' => current_time('mysql', true),
                'ip_hash'     => $ip !== '' ? self::hashIp($ip) : null,
            ]
        );
    }

    /**
     * Light-touch UA classifier — good enough for dashboard buckets, not for
     * security decisions. Order matters: bot patterns first.
     */
    public static function classifyUa(string $ua): array
    {
        $u = strtolower($ua);

        $device = 'other';
        if (preg_match('/bot|crawl|spider|slurp|curl|wget|headless/', $u)) {
            $device = 'bot';
        } elseif (preg_match('/ipad|tablet/', $u)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|android|iphone|ipod/', $u)) {
            $device = 'mobile';
        } elseif ($u !== '') {
            $device = 'desktop';
        }

        $browser = 'other';
        foreach (['edg' => 'Edge', 'chrome' => 'Chrome', 'firefox' => 'Firefox', 'safari' => 'Safari', 'opera' => 'Opera'] as $needle => $name) {
            if (str_contains($u, $needle)) {
                $browser = $name;
                break;
            }
        }

        $os = 'other';
        foreach (['windows' => 'Windows', 'mac os' => 'macOS', 'android' => 'Android', 'iphone' => 'iOS', 'ipad' => 'iOS', 'linux' => 'Linux'] as $needle => $name) {
            if (str_contains($u, $needle)) {
                $os = $name;
                break;
            }
        }

        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    public static function referrerHost(string $referrer): string
    {
        if ($referrer === '') {
            return '';
        }
        $host = wp_parse_url($referrer, PHP_URL_HOST);
        return is_string($host) ? strtolower($host) : '';
    }
}
