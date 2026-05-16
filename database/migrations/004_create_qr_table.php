<?php
/**
 * Migration 004 — create biolink_qr table.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_qr';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        style_hash      CHAR(40) NOT NULL,
        fg_color        CHAR(7) NOT NULL DEFAULT '#000000',
        bg_color        CHAR(7) NOT NULL DEFAULT '#FFFFFF',
        logo_attachment BIGINT UNSIGNED DEFAULT NULL,
        format          VARCHAR(8) NOT NULL DEFAULT 'png',
        file_path       VARCHAR(255) DEFAULT NULL,
        generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY page_style (page_id, style_hash)
    ) {$charset_collate};";

    dbDelta($sql);
};
