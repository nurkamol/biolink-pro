<?php
/**
 * Registers the `biolink_page` custom post type.
 *
 * @package BioLinkPro\Frontend\PostType
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend\PostType;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

/**
 * One CPT, one post per bio page.
 *
 * Public, has its own rewrite slug (`/bio/{slug}`). Builder state lives in the
 * `_biolink_data` post meta key (single JSON blob — see docs/DATABASE.md).
 */
final class BioLinkPagePostType implements Bootable
{
    public const POST_TYPE = 'biolink_page';
    /** @deprecated since v2.7. Use {@see currentSlug()} which reads the dynamic setting. */
    public const REWRITE_SLUG = 'bio';
    public const META_DATA = '_biolink_data';

    public function boot(): void
    {
        add_action('init', [$this, 'register'], 5);
        add_action('init', [$this, 'registerMeta'], 6);
        // Flush rewrite rules once after a slug change.
        add_action('update_option_biolink_settings', [$this, 'maybeFlushOnSlugChange'], 10, 2);
    }

    /**
     * Resolve the current rewrite slug from saved settings (or default to 'bio').
     */
    public static function currentSlug(): string
    {
        $settings = (array) get_option('biolink_settings', []);
        $slug     = isset($settings['page_slug']) ? sanitize_title((string) $settings['page_slug']) : '';
        return $slug !== '' ? $slug : self::REWRITE_SLUG;
    }

    /**
     * If the page_slug setting just changed, schedule a rewrite flush so the
     * new prefix actually starts resolving without manual permalinks-resave.
     *
     * @param array<string, mixed>|mixed $old
     * @param array<string, mixed>|mixed $new
     */
    public function maybeFlushOnSlugChange($old, $new): void
    {
        $old_slug = is_array($old) && isset($old['page_slug']) ? sanitize_title((string) $old['page_slug']) : '';
        $new_slug = is_array($new) && isset($new['page_slug']) ? sanitize_title((string) $new['page_slug']) : '';
        if ($old_slug === $new_slug) {
            return;
        }
        // Re-register the CPT with the new slug, then flush.
        $this->register();
        flush_rewrite_rules(false);
    }

    public function register(): void
    {
        $labels = [
            'name'                  => _x('Bio Pages', 'post type general name', 'biolink-pro'),
            'singular_name'         => _x('Bio Page', 'post type singular name', 'biolink-pro'),
            'menu_name'             => _x('Bio Pages', 'admin menu', 'biolink-pro'),
            'name_admin_bar'        => _x('Bio Page', 'add new on admin bar', 'biolink-pro'),
            'add_new'               => __('Add New', 'biolink-pro'),
            'add_new_item'          => __('Add New Bio Page', 'biolink-pro'),
            'new_item'              => __('New Bio Page', 'biolink-pro'),
            'edit_item'             => __('Edit Bio Page', 'biolink-pro'),
            'view_item'             => __('View Bio Page', 'biolink-pro'),
            'all_items'             => __('All Bio Pages', 'biolink-pro'),
            'search_items'          => __('Search Bio Pages', 'biolink-pro'),
            'not_found'             => __('No bio pages found.', 'biolink-pro'),
            'not_found_in_trash'    => __('No bio pages found in Trash.', 'biolink-pro'),
            'featured_image'        => __('Profile image', 'biolink-pro'),
            'set_featured_image'    => __('Set profile image', 'biolink-pro'),
            'remove_featured_image' => __('Remove profile image', 'biolink-pro'),
            'use_featured_image'    => __('Use as profile image', 'biolink-pro'),
        ];

        $args = [
            'labels'              => $labels,
            'description'         => __('Mobile-first bio link landing pages.', 'biolink-pro'),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => true,
            'rest_base'           => 'biolink-pages',
            'menu_icon'           => 'dashicons-admin-links',
            'capability_type'     => ['biolink_page', 'biolink_pages'],
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'has_archive'         => false,
            'rewrite'             => [
                'slug'       => self::currentSlug(),
                'with_front' => false,
                'feeds'      => false,
                'pages'      => false,
            ],
            'supports'            => ['title', 'author', 'revisions', 'thumbnail'],
            'delete_with_user'    => false,
        ];

        /**
         * Filter the CPT registration arguments.
         *
         * @param array<string, mixed> $args
         */
        $args = apply_filters('biolink/post_type/args', $args);

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the single JSON meta key that stores all builder state.
     *
     * Stored as a JSON string (not WP's auto-serialized format) — we control
     * the encode/decode so that nested arrays don't get flattened by `update_post_meta`.
     */
    public function registerMeta(): void
    {
        register_post_meta(
            self::POST_TYPE,
            self::META_DATA,
            [
                'type'              => 'string',
                'description'       => __('Builder state JSON (blocks, theme, SEO).', 'biolink-pro'),
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => false,
                'sanitize_callback' => static function ($value): string {
                    if (! is_string($value) || $value === '') {
                        return '';
                    }
                    $decoded = json_decode($value, true);
                    if (! is_array($decoded)) {
                        return '';
                    }
                    return wp_json_encode($decoded) ?: '';
                },
                'auth_callback'     => static function (): bool {
                    return current_user_can('biolink_manage_pages');
                },
            ]
        );
    }
}
