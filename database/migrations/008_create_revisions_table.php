<?php
/**
 * Migration 008 — create biolink_revisions table.
 *
 * Snapshots of `_biolink_data` taken on every page save. Powers the History
 * drawer + Restore action in the builder.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_revisions';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        saved_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        saved_by        BIGINT UNSIGNED NOT NULL DEFAULT 0,
        snapshot        LONGTEXT NOT NULL,
        PRIMARY KEY  (id),
        KEY page_saved (page_id, saved_at)
    ) {$charset_collate};";

    dbDelta($sql);
};
