<?php
/**
 * Read/write helpers for the `_biolink_data` JSON blob stored on each `biolink_page`.
 *
 * @package BioLinkPro\Frontend\Repository
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend\Repository;

use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use WP_Post;

defined('ABSPATH') || exit;

/**
 * Encapsulates JSON-meta access so controllers never touch `get_post_meta` directly.
 *
 * Shape persisted under `_biolink_data`:
 *
 * ```
 * {
 *   "theme":    "minimal",
 *   "settings": { ... },
 *   "blocks":   [ { "uuid": "…", "type": "link", "data": { ... } }, ... ],
 *   "seo":      { ... }
 * }
 * ```
 */
final class PageRepository
{
    /**
     * Default JSON skeleton for a brand-new page.
     *
     * @return array{theme: string, settings: array<string, mixed>, blocks: list<array<string, mixed>>, seo: array<string, mixed>}
     */
    public static function defaultData(): array
    {
        return [
            'theme'    => 'mono',
            'settings' => self::defaultSettings(),
            'blocks'   => [],
            'seo'      => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'avatar_id'          => 0,
            'handle'             => '',
            'headline'           => '',
            'subheadline'        => '',
            'hide_name'          => false,
            'bg_type'            => 'theme',
            'bg_color'           => '',
            'bg_gradient_from'   => '',
            'bg_gradient_to'     => '',
            'bg_gradient_angle'  => 135,
            'bg_image_id'        => 0,
            'bg_overlay'         => 0,
            'accent_color'       => '',
            'accent_text_color'  => '',
            'button_shape'       => '',
            'button_style'       => '',
            'custom_css'         => '',
            // Wallpaper polish (v2.6)
            'bg_position'        => 'cover-center', // cover-center | cover-top | cover-bottom | contain | tile
            'bg_blur'            => 0, // px
            'bg_overlay_color'   => '#000000', // hex for the wallpaper overlay tint
            // Content card (v2.6)
            'content_bg_type'    => '', // '' | solid | glass
            'content_bg_color'   => '',
            'content_bg_opacity' => 90,
            'content_blur'       => 12,
            'content_radius'     => 22,
            'content_max_width'  => 620,
        ];
    }

    /**
     * Load and normalize the builder JSON for a page.
     *
     * @return array{theme: string, settings: array<string, mixed>, blocks: list<array<string, mixed>>, seo: array<string, mixed>}
     */
    public function getData(int $page_id): array
    {
        $raw = get_post_meta($page_id, BioLinkPagePostType::META_DATA, true);
        if (! is_string($raw) || $raw === '') {
            return self::defaultData();
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return self::defaultData();
        }

        return self::normalize($decoded);
    }

    /**
     * Persist the full builder JSON (after normalization).
     *
     * @param array<string, mixed> $data
     */
    public function saveData(int $page_id, array $data): bool
    {
        $normalized = self::normalize($data);
        $encoded    = wp_json_encode($normalized);
        if (! is_string($encoded)) {
            return false;
        }
        $result = update_post_meta($page_id, BioLinkPagePostType::META_DATA, wp_slash($encoded));

        if ($result !== false) {
            /**
             * Fires after a page's builder JSON is saved. Used by RevisionRepository
             * to snapshot, but any subscriber can hook in.
             *
             * @param int                  $page_id
             * @param array<string, mixed> $data        The normalized data just written.
             */
            do_action('biolink/page/saved', $page_id, $normalized);
        }

        return $result !== false;
    }

    /**
     * Return both the WP_Post and decoded builder JSON in one call.
     *
     * @return array{post: WP_Post, data: array<string, mixed>}|null
     */
    public function findById(int $page_id): ?array
    {
        $post = get_post($page_id);
        if (! $post instanceof WP_Post || $post->post_type !== BioLinkPagePostType::POST_TYPE) {
            return null;
        }
        return [
            'post' => $post,
            'data' => $this->getData($page_id),
        ];
    }

    /**
     * Append a block to a page's `blocks` array. Returns the new block (with assigned uuid) or null on failure.
     *
     * @param array{type: string, data?: array<string, mixed>} $block
     * @return array<string, mixed>|null
     */
    public function appendBlock(int $page_id, array $block): ?array
    {
        $data    = $this->getData($page_id);
        $entry   = self::normalizeBlock($block);
        $data['blocks'][] = $entry;
        return $this->saveData($page_id, $data) ? $entry : null;
    }

    /**
     * @param array<string, mixed> $patch
     * @return array<string, mixed>|null
     */
    public function updateBlock(int $page_id, string $uuid, array $patch): ?array
    {
        $data  = $this->getData($page_id);
        $found = null;
        foreach ($data['blocks'] as $i => $block) {
            if (($block['uuid'] ?? null) === $uuid) {
                if (isset($patch['data']) && is_array($patch['data'])) {
                    $block['data'] = $patch['data'];
                }
                if (isset($patch['type']) && is_string($patch['type']) && $patch['type'] !== '') {
                    $block['type'] = sanitize_key($patch['type']);
                }
                $data['blocks'][$i] = $block;
                $found              = $block;
                break;
            }
        }
        if ($found === null) {
            return null;
        }
        return $this->saveData($page_id, $data) ? $found : null;
    }

    public function deleteBlock(int $page_id, string $uuid): bool
    {
        $data    = $this->getData($page_id);
        $before  = count($data['blocks']);
        $data['blocks'] = array_values(array_filter(
            $data['blocks'],
            static fn(array $b): bool => ($b['uuid'] ?? null) !== $uuid
        ));
        if (count($data['blocks']) === $before) {
            return false;
        }
        return $this->saveData($page_id, $data);
    }

    /**
     * Reorder blocks according to the supplied uuid list.
     *
     * Unknown uuids are dropped; existing blocks not present in the list are appended in their original order.
     *
     * @param list<string> $uuid_order
     */
    public function reorderBlocks(int $page_id, array $uuid_order): bool
    {
        $data     = $this->getData($page_id);
        $by_uuid  = [];
        foreach ($data['blocks'] as $block) {
            if (isset($block['uuid']) && is_string($block['uuid'])) {
                $by_uuid[$block['uuid']] = $block;
            }
        }

        $reordered = [];
        foreach ($uuid_order as $uuid) {
            if (isset($by_uuid[$uuid])) {
                $reordered[] = $by_uuid[$uuid];
                unset($by_uuid[$uuid]);
            }
        }
        foreach ($by_uuid as $remaining) {
            $reordered[] = $remaining;
        }

        $data['blocks'] = $reordered;
        return $this->saveData($page_id, $data);
    }

    /**
     * Ensure the JSON has the expected top-level shape + every block has a uuid.
     *
     * @param array<string, mixed> $data
     * @return array{theme: string, settings: array<string, mixed>, blocks: list<array<string, mixed>>, seo: array<string, mixed>}
     */
    public static function normalize(array $data): array
    {
        $defaults = self::defaultData();
        $theme    = isset($data['theme']) && is_string($data['theme']) ? sanitize_key($data['theme']) : $defaults['theme'];
        $settings = isset($data['settings']) && is_array($data['settings'])
            ? self::normalizeSettings($data['settings'])
            : $defaults['settings'];
        $seo      = isset($data['seo']) && is_array($data['seo'])
            ? self::normalizeSeo($data['seo'])
            : $defaults['seo'];

        $blocks = [];
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            foreach ($data['blocks'] as $block) {
                if (is_array($block) && isset($block['type'])) {
                    $blocks[] = self::normalizeBlock($block);
                }
            }
        }

        return [
            'theme'    => $theme,
            'settings' => $settings,
            'blocks'   => $blocks,
            'seo'      => $seo,
        ];
    }

    /**
     * Normalize page-level settings to known shape; unknown keys preserved as-is.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    /**
     * Sanitize per-page SEO overrides.
     *
     * @param array<string, mixed> $seo
     * @return array<string, mixed>
     */
    public static function normalizeSeo(array $seo): array
    {
        $out = [
            'custom_title'       => isset($seo['custom_title']) ? sanitize_text_field((string) $seo['custom_title']) : '',
            'custom_description' => isset($seo['custom_description']) ? sanitize_text_field((string) $seo['custom_description']) : '',
            'og_image_id'        => isset($seo['og_image_id']) ? (int) $seo['og_image_id'] : 0,
            'no_index'           => ! empty($seo['no_index']),
            'twitter_site'       => '',
        ];
        if (isset($seo['twitter_site']) && is_string($seo['twitter_site'])) {
            $handle = ltrim(trim($seo['twitter_site']), '@');
            if (preg_match('/^[A-Za-z0-9_]{1,15}$/', $handle)) {
                $out['twitter_site'] = '@' . $handle;
            }
        }
        return $out;
    }

    public static function normalizeSettings(array $settings): array
    {
        $defaults = self::defaultSettings();
        $merged   = array_merge($defaults, $settings);

        $merged['avatar_id']         = isset($merged['avatar_id']) ? (int) $merged['avatar_id'] : 0;
        $merged['bg_image_id']       = isset($merged['bg_image_id']) ? (int) $merged['bg_image_id'] : 0;
        $merged['bg_overlay']        = max(0, min(100, (int) $merged['bg_overlay']));
        $merged['bg_gradient_angle'] = max(0, min(360, (int) $merged['bg_gradient_angle']));
        $merged['hide_name']         = (bool) $merged['hide_name'];

        $bg_type = is_string($merged['bg_type'] ?? null) ? sanitize_key($merged['bg_type']) : 'theme';
        $merged['bg_type'] = in_array($bg_type, ['theme', 'color', 'gradient', 'image'], true) ? $bg_type : 'theme';

        foreach (['handle', 'headline', 'subheadline'] as $text_key) {
            $merged[$text_key] = is_string($merged[$text_key]) ? sanitize_text_field($merged[$text_key]) : '';
        }
        foreach (['bg_color', 'bg_gradient_from', 'bg_gradient_to', 'accent_color', 'accent_text_color'] as $color_key) {
            $val = (string) ($merged[$color_key] ?? '');
            $merged[$color_key] = preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $val) ? $val : '';
        }

        $shape = (string) ($merged['button_shape'] ?? '');
        $merged['button_shape'] = in_array($shape, ['pill', 'rounded', 'square', ''], true) ? $shape : '';
        $style = (string) ($merged['button_style'] ?? '');
        $merged['button_style'] = in_array($style, ['filled', 'outline', 'glass', ''], true) ? $style : '';

        return $merged;
    }

    /**
     * @param array<string, mixed> $block
     * @return array{uuid: string, type: string, data: array<string, mixed>}
     */
    public static function normalizeBlock(array $block): array
    {
        $uuid = isset($block['uuid']) && is_string($block['uuid']) && self::isValidUuid($block['uuid'])
            ? $block['uuid']
            : wp_generate_uuid4();

        $type = isset($block['type']) && is_string($block['type']) ? sanitize_key($block['type']) : '';
        $data = isset($block['data']) && is_array($block['data']) ? $block['data'] : [];

        return [
            'uuid' => $uuid,
            'type' => $type,
            'data' => $data,
        ];
    }

    public static function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }
}
