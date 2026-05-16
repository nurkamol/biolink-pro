<?php
/**
 * PHPUnit bootstrap — loads Composer autoload and Brain\Monkey for WP function stubs.
 *
 * @package BioLinkPro\Tests
 */

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($autoload)) {
    fwrite(STDERR, "Run `composer install` before running the test suite.\n");
    exit(1);
}

require_once $autoload;

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
if (! defined('BIOLINK_VERSION')) {
    define('BIOLINK_VERSION', '0.1.0-test');
}
if (! defined('BIOLINK_DB_VERSION')) {
    define('BIOLINK_DB_VERSION', '1');
}
if (! defined('BIOLINK_PATH')) {
    define('BIOLINK_PATH', dirname(__DIR__) . '/');
}
if (! defined('BIOLINK_URL')) {
    define('BIOLINK_URL', 'http://example.test/wp-content/plugins/biolink-pro/');
}
if (! defined('BIOLINK_BASENAME')) {
    define('BIOLINK_BASENAME', 'biolink-pro/plugin.php');
}
if (! defined('BIOLINK_MIN_PHP')) {
    define('BIOLINK_MIN_PHP', '8.2');
}
if (! defined('BIOLINK_MIN_WP')) {
    define('BIOLINK_MIN_WP', '6.5');
}

// Minimal WP function stubs for unit tests that touch pure-logic paths.
// For tests that need full WP behavior, switch to Brain\Monkey or wp-phpunit.
if (! function_exists('sanitize_key')) {
    function sanitize_key($key): string
    {
        if (! is_scalar($key)) {
            return '';
        }
        $key = strtolower((string) $key);
        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (! function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}

if (! function_exists('do_action')) {
    function do_action(string $_hook, ...$_args): void
    {
        // no-op stub
    }
}

if (! function_exists('add_action')) {
    function add_action(string $_hook, $_callback, int $_priority = 10, int $_accepted_args = 1): bool
    {
        return true;
    }
}
