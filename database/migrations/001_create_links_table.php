<?php
/**
 * Migration 001 — create biolink_links table.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_links';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        block_uuid      CHAR(36) NOT NULL,
        label           VARCHAR(255) NOT NULL,
        url             TEXT NOT NULL,
        icon            VARCHAR(64) DEFAULT NULL,
        sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        is_active       TINYINT(1) NOT NULL DEFAULT 1,
        start_at        DATETIME DEFAULT NULL,
        end_at          DATETIME DEFAULT NULL,
        utm_params      VARCHAR(500) DEFAULT NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY page_id (page_id),
        KEY block_uuid (block_uuid),
        KEY active_schedule (is_active, start_at, end_at)
    ) {$charset_collate};";

    dbDelta($sql);
};
