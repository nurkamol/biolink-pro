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

if (! function_exists('add_filter')) {
    function add_filter(string $_hook, $_callback, int $_priority = 10, int $_accepted_args = 1): bool
    {
        return true;
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $_hook, $value, ...$_args)
    {
        return $value;
    }
}

if (! function_exists('esc_html')) {
    function esc_html($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('esc_url')) {
    function esc_url($url): string
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }
        if (! preg_match('#^(https?|mailto|tel|ftp)://?#i', $url) && ! str_starts_with($url, '/')) {
            return '';
        }
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('esc_url_raw')) {
    function esc_url_raw($url): string
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }
        if (! preg_match('#^(https?|mailto|tel|ftp)://?#i', $url)) {
            return '';
        }
        return $url;
    }
}

if (! function_exists('wp_kses_post')) {
    function wp_kses_post($html): string
    {
        // Minimal stub — strips <script>, <iframe>, on* attrs. Real wp_kses_post is far more thorough.
        $html = (string) $html;
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>#is', '', $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html ?? '');
        return $html ?? '';
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field($str): string
    {
        if (! is_scalar($str)) {
            return '';
        }
        $str = (string) $str;
        $str = wp_strip_all_tags($str);
        $str = preg_replace('/[\r\n\t]+/', ' ', $str) ?? $str;
        return trim($str);
    }
}

if (! function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str): string
    {
        if (! is_scalar($str)) {
            return '';
        }
        return wp_strip_all_tags((string) $str);
    }
}

if (! function_exists('sanitize_email')) {
    function sanitize_email($email): string
    {
        return is_string($email) ? trim($email) : '';
    }
}

if (! function_exists('is_email')) {
    function is_email($email): bool
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string): string
    {
        $string = (string) $string;
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string) ?? $string;
        return strip_tags($string);
    }
}

if (! function_exists('wp_check_invalid_utf8')) {
    function wp_check_invalid_utf8($string): string
    {
        return is_string($string) ? $string : '';
    }
}

if (! function_exists('rest_sanitize_boolean')) {
    function rest_sanitize_boolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $v = strtolower($value);
            if (in_array($v, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
        }
        return (bool) $value;
    }
}

if (! function_exists('plugin_basename')) {
    function plugin_basename(string $file): string
    {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (! function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $flags = 0, int $depth = 512)
    {
        return json_encode($data, $flags, $depth);
    }
}

if (! function_exists('wp_salt')) {
    function wp_salt(string $_scheme = 'auth'): string
    {
        return 'biolink-test-salt';
    }
}

if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (! defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (! defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (! defined('AUTH_KEY')) {
    define('AUTH_KEY', 'biolink-test-auth-key-do-not-use-in-prod');
}
