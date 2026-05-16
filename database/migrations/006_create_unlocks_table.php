<?php
/**
 * Migration 006 — create biolink_unlocks table.
 *
 * Records passcode-unlock events for analytics. One row per successful
 * unlock; powers per-block unlock counts in the admin.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_unlocks';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        block_uuid      CHAR(36) NOT NULL,
        unlocked_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_hash         CHAR(64) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY page_block (page_id, block_uuid),
        KEY unlocked_at (unlocked_at)
    ) {$charset_collate};";

    dbDelta($sql);
};
