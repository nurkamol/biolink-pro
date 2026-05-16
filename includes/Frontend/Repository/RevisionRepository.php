<?php
/**
 * CRUD for the per-page revision snapshots stored in wp_biolink_revisions.
 *
 * One row per save. Last 20 per page are kept.
 *
 * @package BioLinkPro\Frontend\Repository
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend\Repository;

defined('ABSPATH') || exit;

final class RevisionRepository
{
    private const KEEP = 20;

    /**
     * Record a snapshot. Returns the inserted ID or 0 on failure.
     *
     * @param array<string, mixed> $snapshot
     */
    public function record(int $page_id, array $snapshot): int
    {
        if ($page_id <= 0) {
            return 0;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_revisions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ok = $wpdb->insert(
            $table,
            [
                'page_id'  => $page_id,
                'saved_at' => current_time('mysql', true),
                'saved_by' => get_current_user_id(),
                'snapshot' => (string) wp_json_encode($snapshot),
            ]
        );
        if ($ok === false) {
            return 0;
        }
        $new_id = (int) $wpdb->insert_id;
        $this->prune($page_id);
        return $new_id;
    }

    /**
     * Drop oldest revisions beyond the cap.
     */
    private function prune(int $page_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_revisions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE page_id = %d ORDER BY saved_at DESC LIMIT 100 OFFSET %d",
                $page_id,
                self::KEEP
            )
        );
        if (! is_array($ids) || $ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE id IN ({$placeholders})",
                ...array_map('intval', $ids)
            )
        );
    }

    /**
     * @return list<array{id:int, saved_at:string, saved_by:int, author:string}>
     */
    public function listForPage(int $page_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_revisions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, saved_at, saved_by FROM {$table} WHERE page_id = %d ORDER BY saved_at DESC",
                $page_id
            ),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $user = get_user_by('id', (int) $row['saved_by']);
            $out[] = [
                'id'       => (int) $row['id'],
                'saved_at' => (string) $row['saved_at'],
                'saved_by' => (int) $row['saved_by'],
                'author'   => $user ? $user->display_name : __('(unknown)', 'biolink-pro'),
            ];
        }
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $revision_id, int $page_id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_revisions';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT snapshot FROM {$table} WHERE id = %d AND page_id = %d LIMIT 1",
                $revision_id,
                $page_id
            ),
            ARRAY_A
        );
        if (! is_array($row) || empty($row['snapshot'])) {
            return null;
        }
        $decoded = json_decode((string) $row['snapshot'], true);
        return is_array($decoded) ? $decoded : null;
    }
}
