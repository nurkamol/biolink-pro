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
        // WordPress core sitemap.
        add_filter('wp_sitemaps_post_types', [$this, 'includeBioPages']);
        // Yoast SEO — false in this filter means "include in sitemap".
        add_filter('wpseo_sitemap_exclude_post_type', [$this, 'yoastInclude'], 10, 2);
        // Rank Math — array of post-type slugs.
        add_filter('rank_math/sitemap/post_types', [$this, 'rankMathInclude']);
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

    public function yoastInclude(bool $exclude, string $post_type): bool
    {
        return $post_type === BioLinkPagePostType::POST_TYPE ? false : $exclude;
    }

    /**
     * @param array<string>|string $post_types
     * @return array<string>|string
     */
    public function rankMathInclude($post_types)
    {
        if (is_array($post_types) && ! in_array(BioLinkPagePostType::POST_TYPE, $post_types, true)) {
            $post_types[] = BioLinkPagePostType::POST_TYPE;
        }
        return $post_types;
    }
}
