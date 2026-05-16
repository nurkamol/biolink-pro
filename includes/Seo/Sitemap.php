<?php
/**
 * Tell wp_sitemaps to include public `biolink_page` CPT entries.
 *
 * @package BioLinkPro\Seo
 */

declare(strict_types=1);

namespace BioLinkPro\Seo;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;

defined('ABSPATH') || exit;

final class Sitemap implements Bootable
{
    public function boot(): void
    {
        add_filter('wp_sitemaps_post_types', [$this, 'includeBioPages']);
    }

    /**
     * @param array<string, \WP_Post_Type> $post_types
     * @return array<string, \WP_Post_Type>
     */
    public function includeBioPages(array $post_types): array
    {
        $cpt = get_post_type_object(BioLinkPagePostType::POST_TYPE);
        if ($cpt instanceof \WP_Post_Type) {
            $post_types[BioLinkPagePostType::POST_TYPE] = $cpt;
        }
        return $post_types;
    }
}
