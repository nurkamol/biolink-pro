<?php
/**
 * `[biolink]` and `[biolink_block]` shortcodes — embed a published bio page
 * (or a single block from one) inside any post / page / widget content area.
 *
 * Usage:
 *   [biolink id="123"]                       full page by ID
 *   [biolink slug="alvasti"]                 full page by slug
 *   [biolink id="123" header="0"]            hide the avatar/header block
 *   [biolink_block id="123" uuid="abc-…"]    render a single block by uuid
 *
 * @package BioLinkPro\Frontend
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Themes\ThemeEngine;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

final class Shortcodes implements Bootable
{
    private const HANDLE = 'biolink-pro-frontend';

    public function __construct(
        private readonly PageRepository $repository,
        private readonly PageRenderer $renderer,
        private readonly ThemeEngine $themes
    ) {
    }

    public function boot(): void
    {
        add_shortcode('biolink', [$this, 'renderPage']);
        add_shortcode('biolink_block', [$this, 'renderBlock']);
        add_action('wp_enqueue_scripts', [$this, 'maybeEnqueueAssets']);
    }

    /**
     * Enqueue the public stylesheet/JS when a post contains either shortcode.
     * Bio pages themselves are handled by Assets::enqueue, so this is just for
     * embeds living on regular posts/pages.
     */
    public function maybeEnqueueAssets(): void
    {
        if (is_singular(BioLinkPagePostType::POST_TYPE)) {
            return; // Assets already handles bio-page singulars.
        }
        $post = get_post();
        if (! $post instanceof WP_Post) {
            return;
        }
        if (! has_shortcode($post->post_content, 'biolink')
            && ! has_shortcode($post->post_content, 'biolink_block')
        ) {
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
            wp_localize_script(
                self::HANDLE,
                'BIOLINK_PRO_PUBLIC',
                [
                    'restBase' => esc_url_raw(rest_url('biolink/v1/')),
                    'pageId'   => (int) $post->ID,
                ]
            );
        }
    }

    /**
     * `[biolink id="…" slug="…" header="1|0"]`
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderPage($atts): string
    {
        $atts = shortcode_atts(
            [
                'id'     => '',
                'slug'   => '',
                'header' => '1',
            ],
            is_array($atts) ? $atts : []
        );

        $page_id = $this->resolvePageId((string) $atts['id'], (string) $atts['slug']);
        if ($page_id === 0) {
            return $this->errorComment(__('biolink: page not found.', 'biolink-pro'));
        }

        $bundle = $this->repository->findById($page_id);
        if ($bundle === null) {
            return $this->errorComment(__('biolink: page not found.', 'biolink-pro'));
        }

        $post = $bundle['post'];
        if ($post->post_status !== 'publish') {
            return $this->errorComment(__('biolink: page is not published.', 'biolink-pro'));
        }

        $data     = $bundle['data'];
        $theme    = (string) ($data['theme'] ?? 'mono');
        $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $blocks   = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        $selector = '.bio-embed-' . $post->ID;
        $style    = $this->themes->renderStyleBlock($theme, $settings, $selector);

        // Many block renderers call get_the_ID() to thread the page id through.
        // Wrap the render in a temporary post context.
        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);

        $header = $atts['header'] === '0' || $atts['header'] === 'false'
            ? ''
            : $this->renderer->renderHeader($post, $settings);
        $blocks_html = $this->renderer->renderBlocks($blocks);

        wp_reset_postdata();
        if ($previous instanceof WP_Post) {
            $GLOBALS['post'] = $previous;
        }

        return sprintf(
            '%s<div class="bio-embed bio-embed-%d bio-page bio-theme-%s">%s%s</div>',
            $style,
            (int) $post->ID,
            esc_attr($theme),
            $header,
            $blocks_html
        );
    }

    /**
     * `[biolink_block id="…" slug="…" uuid="…"]`
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderBlock($atts): string
    {
        $atts = shortcode_atts(
            ['id' => '', 'slug' => '', 'uuid' => ''],
            is_array($atts) ? $atts : []
        );

        $uuid = (string) $atts['uuid'];
        if ($uuid === '') {
            return $this->errorComment(__('biolink_block: uuid is required.', 'biolink-pro'));
        }

        $page_id = $this->resolvePageId((string) $atts['id'], (string) $atts['slug']);
        if ($page_id === 0) {
            return $this->errorComment(__('biolink_block: page not found.', 'biolink-pro'));
        }

        $bundle = $this->repository->findById($page_id);
        if ($bundle === null) {
            return $this->errorComment(__('biolink_block: page not found.', 'biolink-pro'));
        }

        $post = $bundle['post'];
        if ($post->post_status !== 'publish') {
            return $this->errorComment(__('biolink_block: page is not published.', 'biolink-pro'));
        }

        $blocks = is_array($bundle['data']['blocks'] ?? null) ? $bundle['data']['blocks'] : [];
        $match  = null;
        foreach ($blocks as $b) {
            if (is_array($b) && ($b['uuid'] ?? null) === $uuid) {
                $match = $b;
                break;
            }
        }
        if ($match === null) {
            return $this->errorComment(__('biolink_block: block not found.', 'biolink-pro'));
        }

        $theme    = (string) ($bundle['data']['theme'] ?? 'mono');
        $settings = is_array($bundle['data']['settings'] ?? null) ? $bundle['data']['settings'] : [];
        $selector = '.bio-embed-' . $post->ID;
        $style    = $this->themes->renderStyleBlock($theme, $settings, $selector);

        $previous = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $post;
        setup_postdata($post);
        $html = $this->renderer->renderBlocks([$match]);
        wp_reset_postdata();
        if ($previous instanceof WP_Post) {
            $GLOBALS['post'] = $previous;
        }

        return sprintf(
            '%s<div class="bio-embed bio-embed-%d bio-page bio-theme-%s bio-embed--single">%s</div>',
            $style,
            (int) $post->ID,
            esc_attr($theme),
            $html
        );
    }

    private function resolvePageId(string $id, string $slug): int
    {
        if ($id !== '' && ctype_digit($id)) {
            return (int) $id;
        }
        if ($slug !== '') {
            $query = new WP_Query([
                'post_type'      => BioLinkPagePostType::POST_TYPE,
                'name'           => sanitize_title($slug),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);
            $ids = $query->posts;
            if (! empty($ids)) {
                return (int) $ids[0];
            }
        }
        return 0;
    }

    private function errorComment(string $message): string
    {
        return current_user_can('edit_posts')
            ? '<!-- ' . esc_html($message) . ' -->'
            : '';
    }
}
