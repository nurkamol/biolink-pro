<?php
/**
 * OpenGraph + Twitter Card + canonical meta tags for `biolink_page` CPT.
 *
 * @package BioLinkPro\Seo
 */

declare(strict_types=1);

namespace BioLinkPro\Seo;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Themes\ThemeEngine;

defined('ABSPATH') || exit;

final class MetaTags implements Bootable
{
    public function __construct(
        private readonly PageRepository $repository,
        private readonly ThemeEngine $themes
    ) {
    }

    public function boot(): void
    {
        add_action('wp_head', [$this, 'emit'], 5);
        add_filter('document_title_parts', [$this, 'documentTitle'], 10, 1);
        add_filter('pre_get_document_title', [$this, 'documentTitleOverride'], 10, 1);
    }

    /**
     * @param array<string,string> $parts
     * @return array<string,string>
     */
    public function documentTitle(array $parts): array
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return $parts;
        }
        $post = get_post();
        if (! $post) {
            return $parts;
        }
        $data = $this->repository->getData($post->ID);
        $seo  = is_array($data['seo'] ?? null) ? $data['seo'] : [];
        if (! empty($seo['custom_title'])) {
            $parts['title'] = (string) $seo['custom_title'];
        }
        return $parts;
    }

    public function documentTitleOverride(string $title): string
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE) || $title !== '') {
            return $title;
        }
        return $title;
    }

    public function emit(): void
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return;
        }
        $post = get_post();
        if (! $post) {
            return;
        }

        $data     = $this->repository->getData($post->ID);
        $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $seo      = is_array($data['seo'] ?? null) ? $data['seo'] : [];

        $title = ! empty($seo['custom_title'])
            ? (string) $seo['custom_title']
            : (! empty($settings['headline']) ? (string) $settings['headline'] : (string) $post->post_title);

        $description = ! empty($seo['custom_description'])
            ? (string) $seo['custom_description']
            : (! empty($settings['subheadline']) ? (string) $settings['subheadline'] : '');

        $og_image = '';
        if (! empty($seo['og_image_id'])) {
            $url = wp_get_attachment_image_url((int) $seo['og_image_id'], 'full');
            if ($url) {
                $og_image = $url;
            }
        }
        if ($og_image === '' && ! empty($settings['avatar_id'])) {
            $url = wp_get_attachment_image_url((int) $settings['avatar_id'], 'full');
            if ($url) {
                $og_image = $url;
            }
        }

        $url      = (string) get_permalink($post);
        $site     = (string) get_bloginfo('name');
        $locale   = (string) get_locale();
        $robots   = ! empty($seo['no_index']) ? 'noindex,nofollow' : 'index,follow';

        $tags = [
            ['name' => 'description', 'content' => $description],
            ['name' => 'robots', 'content' => $robots],
            // OpenGraph
            ['property' => 'og:type', 'content' => 'profile'],
            ['property' => 'og:title', 'content' => $title],
            ['property' => 'og:description', 'content' => $description],
            ['property' => 'og:url', 'content' => $url],
            ['property' => 'og:site_name', 'content' => $site],
            ['property' => 'og:locale', 'content' => $locale],
            // Twitter
            ['name' => 'twitter:card', 'content' => $og_image !== '' ? 'summary_large_image' : 'summary'],
            ['name' => 'twitter:title', 'content' => $title],
            ['name' => 'twitter:description', 'content' => $description],
        ];
        if ($og_image !== '') {
            $tags[] = ['property' => 'og:image', 'content' => $og_image];
            $tags[] = ['name' => 'twitter:image', 'content' => $og_image];
        }
        if (! empty($seo['twitter_site'])) {
            $tags[] = ['name' => 'twitter:site', 'content' => (string) $seo['twitter_site']];
        }

        // Suppress WP's default canonical so we can emit a self-canonical that respects custom slugs.
        echo "\n<!-- BioLink Pro SEO -->\n";
        echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";

        foreach ($tags as $tag) {
            if (empty($tag['content'])) {
                continue;
            }
            $attr = isset($tag['property']) ? 'property' : 'name';
            $name = $tag[$attr];
            printf(
                "<meta %s=\"%s\" content=\"%s\">\n",
                esc_attr($attr),
                esc_attr($name),
                esc_attr((string) $tag['content'])
            );
        }
        echo "<!-- /BioLink Pro SEO -->\n";
    }
}
