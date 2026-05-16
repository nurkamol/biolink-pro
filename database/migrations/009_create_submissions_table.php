<?php
/**
 * Migration 009 — create biolink_submissions table.
 *
 * Stores newsletter subscriptions + contact form submissions so the Audience
 * tab can list them and export to CSV. Sits alongside the existing
 * forwarding to Mailchimp / MailerLite / Resend (which fires regardless).
 *
 * @package BioLinkPro\Database\Migrations
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return static function (string $prefix, string $charset_collate): void {
    $table = $prefix . 'biolink_submissions';
    $sql   = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id         BIGINT UNSIGNED NOT NULL,
        block_uuid      CHAR(36) DEFAULT NULL,
        kind            VARCHAR(32) NOT NULL DEFAULT 'newsletter',
        email           VARCHAR(190) DEFAULT NULL,
        name            VARCHAR(190) DEFAULT NULL,
        payload         LONGTEXT DEFAULT NULL,
        ip_hash         CHAR(64) DEFAULT NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY kind_created (kind, created_at),
        KEY page_kind (page_id, kind),
        KEY email_idx (email)
    ) {$charset_collate};";

    dbDelta($sql);
};
