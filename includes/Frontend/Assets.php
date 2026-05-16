<?php
/**
 * Enqueue the minimal public stylesheet only on bio pages.
 *
 * @package BioLinkPro\Frontend
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;

defined('ABSPATH') || exit;

final class Assets implements Bootable
{
    private const HANDLE = 'biolink-pro-frontend';

    public function boot(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return;
        }

        $css_path = BIOLINK_PATH . 'assets/frontend/biolink.css';
        wp_enqueue_style(
            self::HANDLE,
            BIOLINK_URL . 'assets/frontend/biolink.css',
            [],
            file_exists($css_path) ? (string) filemtime($css_path) : BIOLINK_VERSION
        );

        $js_path = BIOLINK_PATH . 'assets/frontend/biolink.js';
        if (file_exists($js_path)) {
            wp_enqueue_script(
                self::HANDLE,
                BIOLINK_URL . 'assets/frontend/biolink.js',
                [],
                (string) filemtime($js_path),
                true
            );
        }
    }
}
