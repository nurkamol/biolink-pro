<?php
/**
 * Read-side aggregation for the analytics dashboard.
 *
 * @package BioLinkPro\Analytics
 */

declare(strict_types=1);

namespace BioLinkPro\Analytics;

defined('ABSPATH') || exit;

final class Reporter
{
    /**
     * @return array{views:int, clicks:int, unique_visitors:int, ctr:float}
     */
    public function summary(int $page_id, string $from, string $to): array
    {
        global $wpdb;
        $views_t  = $wpdb->prefix . 'biolink_views';
        $clicks_t = $wpdb->prefix . 'biolink_clicks';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$views_t} WHERE page_id = %d AND viewed_at BETWEEN %s AND %s",
            $page_id,
            $from,
            $to
        ));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $clicks = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$clicks_t} WHERE page_id = %d AND clicked_at BETWEEN %s AND %s",
            $page_id,
            $from,
            $to
        ));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $unique = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_hash) FROM {$views_t} WHERE page_id = %d AND viewed_at BETWEEN %s AND %s AND ip_hash IS NOT NULL AND ip_hash != ''",
            $page_id,
            $from,
            $to
        ));

        $ctr = $views > 0 ? ($clicks / $views) : 0.0;
        return [
            'views'           => $views,
            'clicks'          => $clicks,
            'unique_visitors' => $unique,
            'ctr'             => round($ctr, 4),
        ];
    }

    /**
     * @return list<array{date:string, views:int, clicks:int}>
     */
    public function timeseries(int $page_id, string $from, string $to): array
    {
        global $wpdb;
        $views_t  = $wpdb->prefix . 'biolink_views';
        $clicks_t = $wpdb->prefix . 'biolink_clicks';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $views = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(viewed_at) AS d, COUNT(*) AS c FROM {$views_t} WHERE page_id = %d AND viewed_at BETWEEN %s AND %s GROUP BY DATE(viewed_at)",
            $page_id,
            $from,
            $to
        ), ARRAY_A) ?: [];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $clicks = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(clicked_at) AS d, COUNT(*) AS c FROM {$clicks_t} WHERE page_id = %d AND clicked_at BETWEEN %s AND %s GROUP BY DATE(clicked_at)",
            $page_id,
            $from,
            $to
        ), ARRAY_A) ?: [];

        $by_day = [];
        foreach ($views as $row) {
            $by_day[$row['d']] = ['date' => $row['d'], 'views' => (int) $row['c'], 'clicks' => 0];
        }
        foreach ($clicks as $row) {
            $d = $row['d'];
            if (! isset($by_day[$d])) {
                $by_day[$d] = ['date' => $d, 'views' => 0, 'clicks' => 0];
            }
            $by_day[$d]['clicks'] = (int) $row['c'];
        }

        // Fill in every day in the range so the chart isn't gappy.
        $start = strtotime($from);
        $end   = strtotime($to);
        if ($start !== false && $end !== false) {
            for ($t = $start; $t <= $end; $t += DAY_IN_SECONDS) {
                $d = gmdate('Y-m-d', $t);
                if (! isset($by_day[$d])) {
                    $by_day[$d] = ['date' => $d, 'views' => 0, 'clicks' => 0];
                }
            }
        }
        ksort($by_day);
        return array_values($by_day);
    }

    /**
     * @return list<array{link_id:int, label:string, url:string, clicks:int}>
     */
    public function topLinks(int $page_id, string $from, string $to, int $limit = 10): array
    {
        global $wpdb;
        $links_t  = $wpdb->prefix . 'biolink_links';
        $clicks_t = $wpdb->prefix . 'biolink_clicks';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT l.id AS link_id, l.label, l.url, COUNT(c.id) AS clicks
             FROM {$links_t} l
             LEFT JOIN {$clicks_t} c ON c.link_id = l.id AND c.clicked_at BETWEEN %s AND %s
             WHERE l.page_id = %d
             GROUP BY l.id
             ORDER BY clicks DESC, l.sort_order ASC
             LIMIT %d",
            $from,
            $to,
            $page_id,
            $limit
        ), ARRAY_A) ?: [];

        return array_map(static fn(array $r): array => [
            'link_id' => (int) $r['link_id'],
            'label'   => (string) $r['label'],
            'url'     => (string) $r['url'],
            'clicks'  => (int) $r['clicks'],
        ], $rows);
    }

    /**
     * @return list<array{bucket:string, count:int}>
     */
    public function devices(int $page_id, string $from, string $to): array
    {
        return $this->groupedCount('biolink_views', 'device', 'viewed_at', $page_id, $from, $to);
    }

    /**
     * @return list<array{bucket:string, count:int}>
     */
    public function geo(int $page_id, string $from, string $to): array
    {
        return $this->groupedCount('biolink_views', 'country', 'viewed_at', $page_id, $from, $to);
    }

    /**
     * @return list<array{bucket:string, count:int}>
     */
    public function referrers(int $page_id, string $from, string $to): array
    {
        return $this->groupedCount('biolink_views', 'referrer_host', 'viewed_at', $page_id, $from, $to);
    }

    /**
     * Per-block unlock counts for a page, keyed by block uuid.
     *
     * @return array<string, int>
     */
    public function unlockCounts(int $page_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_unlocks';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT block_uuid, COUNT(*) AS c FROM {$table} WHERE page_id = %d GROUP BY block_uuid",
                $page_id
            ),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $out[(string) $row['block_uuid']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * @return list<array{bucket:string, count:int}>
     */
    private function groupedCount(string $table_suffix, string $column, string $date_col, int $page_id, string $from, string $to): array
    {
        global $wpdb;
        $table = $wpdb->prefix . $table_suffix;
        // Whitelist column name — never inject user input here.
        $allowed = ['device', 'browser', 'os', 'country', 'referrer_host'];
        if (! in_array($column, $allowed, true)) {
            return [];
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT COALESCE(NULLIF({$column}, ''), 'unknown') AS bucket, COUNT(*) AS c
             FROM {$table}
             WHERE page_id = %d AND {$date_col} BETWEEN %s AND %s
             GROUP BY {$column}
             ORDER BY c DESC
             LIMIT 20",
            $page_id,
            $from,
            $to
        ), ARRAY_A) ?: [];

        return array_map(static fn(array $r): array => [
            'bucket' => (string) $r['bucket'],
            'count'  => (int) $r['c'],
        ], $rows);
    }
}
