<?php
/**
 * JSON-LD structured data emitter for bio pages.
 *
 * Emits a `Person` graph for the page subject + a `WebPage` wrapper. FAQ blocks
 * already emit their own `FAQPage` script inside their render path.
 *
 * @package BioLinkPro\Seo
 */

declare(strict_types=1);

namespace BioLinkPro\Seo;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;

defined('ABSPATH') || exit;

final class StructuredData implements Bootable
{
    public function __construct(private readonly PageRepository $repository)
    {
    }

    public function boot(): void
    {
        add_action('wp_head', [$this, 'emit'], 7);
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
        $blocks   = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        $person_name = ! empty($settings['headline']) ? (string) $settings['headline'] : (string) $post->post_title;
        $person_url  = (string) get_permalink($post);
        $description = ! empty($settings['subheadline']) ? (string) $settings['subheadline'] : '';

        $same_as = [];
        foreach ($blocks as $block) {
            if (! is_array($block) || ($block['type'] ?? '') !== 'social_icons') {
                continue;
            }
            $items = is_array($block['data']['items'] ?? null) ? $block['data']['items'] : [];
            foreach ($items as $item) {
                if (is_array($item) && ! empty($item['url'])) {
                    $same_as[] = (string) $item['url'];
                }
            }
        }
        $same_as = array_values(array_unique($same_as));

        $person = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $person_name,
            'url'         => $person_url,
            'description' => $description,
        ];
        if ($same_as !== []) {
            $person['sameAs'] = $same_as;
        }
        if (! empty($settings['avatar_id'])) {
            $img = wp_get_attachment_image_url((int) $settings['avatar_id'], 'full');
            if ($img) {
                $person['image'] = $img;
            }
        }

        $webpage = [
            '@context'      => 'https://schema.org',
            '@type'         => 'WebPage',
            'name'          => $person_name,
            'url'           => $person_url,
            'description'   => $description,
            'inLanguage'    => get_locale(),
        ];

        echo "<script type=\"application/ld+json\">" . wp_json_encode($person) . "</script>\n";
        echo "<script type=\"application/ld+json\">" . wp_json_encode($webpage) . "</script>\n";
    }
}
