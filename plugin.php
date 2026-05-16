<?php
/**
 * Plugin Name:       BioLink Pro
 * Plugin URI:        https://example.com/biolink-pro
 * Description:       Self-hosted bio link / link-in-bio builder. Drag-and-drop blocks, themes, analytics, QR codes, monetization.
 * Version:           0.1.0-dev
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       biolink-pro
 * Domain Path:       /languages
 *
 * @package BioLinkPro
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

if (defined('BIOLINK_VERSION')) {
    return;
}

define('BIOLINK_VERSION', '0.1.0-dev');
define('BIOLINK_DB_VERSION', '1');
define('BIOLINK_FILE', __FILE__);
define('BIOLINK_PATH', plugin_dir_path(__FILE__));
define('BIOLINK_URL', plugin_dir_url(__FILE__));
define('BIOLINK_BASENAME', plugin_basename(__FILE__));
define('BIOLINK_MIN_PHP', '8.2');
define('BIOLINK_MIN_WP', '6.5');

if (version_compare(PHP_VERSION, BIOLINK_MIN_PHP, '<')) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: 1: required PHP version, 2: current PHP version */
                    __('BioLink Pro requires PHP %1$s or higher. You are running PHP %2$s.', 'biolink-pro'),
                    BIOLINK_MIN_PHP,
                    PHP_VERSION
                )
            )
        );
    });
    return;
}

if (! file_exists(BIOLINK_PATH . 'vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'BioLink Pro is missing its Composer dependencies. Run "composer install" inside the plugin directory.',
                'biolink-pro'
            )
        );
    });
    return;
}

require_once BIOLINK_PATH . 'vendor/autoload.php';

register_activation_hook(__FILE__, [BioLinkPro\Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [BioLinkPro\Core\Deactivator::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    if (! version_compare(get_bloginfo('version'), BIOLINK_MIN_WP, '>=')) {
        add_action('admin_notices', static function (): void {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: 1: required WP version, 2: current WP version */
                        __('BioLink Pro requires WordPress %1$s or higher. You are running WordPress %2$s.', 'biolink-pro'),
                        BIOLINK_MIN_WP,
                        get_bloginfo('version')
                    )
                )
            );
        });
        return;
    }

    BioLinkPro\Core\Plugin::instance()->boot();
}, 5);
