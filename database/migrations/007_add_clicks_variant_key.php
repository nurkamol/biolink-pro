<?php
/**
 * Migration 007 — add variant_key column to biolink_clicks.
 *
 * Stores which A/B variant was active for the click. NULL means no
 * variant test was running on the link at click time (the default).
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_clicks';
    // dbDelta needs the full CREATE TABLE — it'll diff and add the new column.
    $sql = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        link_id         BIGINT UNSIGNED NOT NULL,
        page_id         BIGINT UNSIGNED NOT NULL,
        clicked_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_hash         CHAR(64) DEFAULT NULL,
        country         CHAR(2) DEFAULT NULL,
        device          VARCHAR(16) NOT NULL DEFAULT 'other',
        browser         VARCHAR(32) DEFAULT NULL,
        os              VARCHAR(32) DEFAULT NULL,
        referrer_host   VARCHAR(191) DEFAULT NULL,
        utm_source      VARCHAR(64) DEFAULT NULL,
        utm_medium      VARCHAR(64) DEFAULT NULL,
        utm_campaign    VARCHAR(64) DEFAULT NULL,
        variant_key     VARCHAR(32) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY link_id_clicked_at (link_id, clicked_at),
        KEY page_id_clicked_at (page_id, clicked_at),
        KEY clicked_at (clicked_at),
        KEY link_variant (link_id, variant_key)
    ) {$charset_collate};";

    dbDelta($sql);
};
