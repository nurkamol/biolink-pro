<?php
/**
 * Hooks template_include so visits to a `biolink_page` CPT render our minimal
 * template (`templates/bio-page.php`) instead of inheriting the active theme's
 * single.php — keeps the public bio page lean and theme-independent.
 *
 * @package BioLinkPro\Frontend
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;

defined('ABSPATH') || exit;

final class TemplateLoader implements Bootable
{
    public function boot(): void
    {
        add_filter('template_include', [$this, 'maybeLoadBioTemplate'], 99);
    }

    public function maybeLoadBioTemplate(string $template): string
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return $template;
        }

        /**
         * Allow themes/plugins to override the bio template.
         *
         * @param string $template Default path to our bundled template.
         */
        $custom = apply_filters('biolink/template/bio-page', BIOLINK_PATH . 'templates/bio-page.php');
        if (is_string($custom) && file_exists($custom)) {
            return $custom;
        }
        return $template;
    }
}
