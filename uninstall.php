<?php
/**
 * Uninstall cleanup for BioLink Pro.
 *
 * Runs when the plugin is deleted from wp-admin (not on deactivate).
 * Drops custom tables, removes CPT entries, deletes options, clears cron.
 * Honors `biolink_keep_data` — if true, leaves everything in place.
 *
 * @package BioLinkPro
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

if ((bool) get_option('biolink_keep_data', false) === true) {
    return;
}

global $wpdb;

// 1. Drop custom tables.
$tables = [
    $wpdb->prefix . 'biolink_links',
    $wpdb->prefix . 'biolink_clicks',
    $wpdb->prefix . 'biolink_views',
    $wpdb->prefix . 'biolink_qr',
    $wpdb->prefix . 'biolink_rate_limit',
];
foreach ($tables as $table) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// 2. Delete CPT entries + meta.
$post_ids = get_posts(
    [
        'post_type'      => 'biolink_page',
        'post_status'    => 'any',
        'numberposts'    => -1,
        'fields'         => 'ids',
        'suppress_filters' => true,
    ]
);
foreach ($post_ids as $post_id) {
    wp_delete_post((int) $post_id, true);
}

// 3. Delete options prefixed with biolink_.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like('biolink_') . '%'
    )
);

// 4. Delete user meta prefixed with biolink_.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        $wpdb->esc_like('biolink_') . '%'
    )
);

// 5. Remove uploads/biolink-pro/ recursively.
$uploads = wp_upload_dir(null, false);
if (! empty($uploads['basedir'])) {
    $root = trailingslashit($uploads['basedir']) . 'biolink-pro';
    if (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            /** @var SplFileInfo $path */
            if ($path->isDir()) {
                @rmdir($path->getPathname()); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
            } else {
                @unlink($path->getPathname()); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
            }
        }
        @rmdir($root); // phpcs:ignore WordPress.PHP.NoSilencedErrors,WordPress.WP.AlternativeFunctions
    }
}

// 6. Clear scheduled cron events.
foreach (['biolink/cron/prune_rate_limit', 'biolink/cron/prune_analytics'] as $hook) {
    $timestamp = wp_next_scheduled($hook);
    while ($timestamp !== false) {
        wp_unschedule_event($timestamp, $hook);
        $timestamp = wp_next_scheduled($hook);
    }
}

// 7. Strip custom capabilities from every role.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists(\BioLinkPro\Core\Capabilities::class)) {
        (new \BioLinkPro\Core\Capabilities())->uninstall();
    }
}
