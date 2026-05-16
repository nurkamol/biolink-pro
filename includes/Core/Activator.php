<?php
/**
 * Activation hook handler.
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

use BioLinkPro\Database\Migrator;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;

defined('ABSPATH') || exit;

/**
 * Runs once when the plugin is activated.
 *
 * - PHP / WP version gate (deactivates self on mismatch)
 * - Optional sodium check (warning only; encryption falls back later)
 * - Creates the `uploads/biolink-pro/` directory
 * - Registers the CPT so its rewrite rules can flush
 * - Runs database migrations
 * - Grants custom capabilities
 * - Flushes rewrite rules
 */
final class Activator
{
    public static function activate(): void
    {
        self::guardEnvironment();
        self::ensureUploadsDir();

        (new BioLinkPagePostType())->register();
        (new Migrator())->run();
        (new Capabilities())->install();

        if (get_option('biolink_settings') === false) {
            add_option('biolink_settings', [], '', false);
        }
        if (get_option('biolink_onboarding_complete') === false) {
            add_option('biolink_onboarding_complete', false, '', false);
        }

        flush_rewrite_rules(false);

        /**
         * Fires at the end of plugin activation.
         */
        do_action('biolink/plugin/activated');
    }

    /**
     * Deactivate self with an admin notice if PHP/WP versions don't meet the bar.
     */
    private static function guardEnvironment(): void
    {
        if (version_compare(PHP_VERSION, BIOLINK_MIN_PHP, '<')) {
            self::abortActivation(
                sprintf(
                    /* translators: 1: required PHP, 2: current PHP */
                    __('BioLink Pro requires PHP %1$s or higher. You are running PHP %2$s.', 'biolink-pro'),
                    BIOLINK_MIN_PHP,
                    PHP_VERSION
                )
            );
        }

        if (version_compare(get_bloginfo('version'), BIOLINK_MIN_WP, '<')) {
            self::abortActivation(
                sprintf(
                    /* translators: 1: required WP, 2: current WP */
                    __('BioLink Pro requires WordPress %1$s or higher.', 'biolink-pro'),
                    BIOLINK_MIN_WP
                )
            );
        }
    }

    private static function abortActivation(string $message): never
    {
        deactivate_plugins(BIOLINK_BASENAME);
        wp_die(
            esc_html($message),
            esc_html__('Plugin Activation Error', 'biolink-pro'),
            ['back_link' => true]
        );
    }

    private static function ensureUploadsDir(): void
    {
        $uploads = wp_upload_dir(null, false);
        if (empty($uploads['basedir'])) {
            return;
        }

        $dir = trailingslashit($uploads['basedir']) . 'biolink-pro';
        if (! file_exists($dir)) {
            wp_mkdir_p($dir);
        }

        $qr = $dir . '/qr';
        if (! file_exists($qr)) {
            wp_mkdir_p($qr);
        }

        $index = $dir . '/index.php';
        if (! file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        }
    }
}
