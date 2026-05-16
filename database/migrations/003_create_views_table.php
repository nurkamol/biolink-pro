<?php
/**
 * Migration 003 — create biolink_views table.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_views';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        viewed_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_hash         CHAR(64) DEFAULT NULL,
        country         CHAR(2) DEFAULT NULL,
        device          VARCHAR(16) NOT NULL DEFAULT 'other',
        browser         VARCHAR(32) DEFAULT NULL,
        os              VARCHAR(32) DEFAULT NULL,
        referrer_host   VARCHAR(191) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY page_id_viewed_at (page_id, viewed_at),
        KEY viewed_at (viewed_at)
    ) {$charset_collate};";

    dbDelta($sql);
};
