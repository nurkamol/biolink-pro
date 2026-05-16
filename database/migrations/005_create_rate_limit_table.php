<?php
/**
 * Migration 005 — create biolink_rate_limit table.
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_rate_limit';
    $sql   = "CREATE TABLE {$table} (
        bucket_key      VARCHAR(128) NOT NULL,
        count           INT UNSIGNED NOT NULL DEFAULT 0,
        expires_at      DATETIME NOT NULL,
        PRIMARY KEY  (bucket_key),
        KEY expires_at (expires_at)
    ) {$charset_collate};";

    dbDelta($sql);
};
