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
            'theme'    => 'minimal',
            'settings' => [],
            'blocks'   => [],
            'seo'      => [],
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
        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : $defaults['settings'];
        $seo      = isset($data['seo']) && is_array($data['seo']) ? $data['seo'] : $defaults['seo'];

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
