<?php
/**
 * Mirrors LinkBlock entries from the page JSON into the `wp_biolink_links`
 * table so per-link analytics can join on a stable BIGINT id.
 *
 * @package BioLinkPro\Analytics
 */

declare(strict_types=1);

namespace BioLinkPro\Analytics;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;

defined('ABSPATH') || exit;

final class LinkSync implements Bootable
{
    public function __construct(private readonly PageRepository $repository)
    {
    }

    public function boot(): void
    {
        add_action('save_post_' . BioLinkPagePostType::POST_TYPE, [$this, 'syncOnSave'], 20, 1);
    }

    public function syncOnSave(int $post_id): void
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        $this->sync($post_id);
    }

    public function sync(int $page_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_links';
        $data  = $this->repository->getData($page_id);

        $seen_uuids = [];
        $sort       = 0;
        foreach ($data['blocks'] as $block) {
            if (! is_array($block) || ($block['type'] ?? '') !== 'link') {
                continue;
            }
            $uuid = (string) ($block['uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }
            $bdata = is_array($block['data'] ?? null) ? $block['data'] : [];
            $label = isset($bdata['label']) ? (string) $bdata['label'] : '';
            $url   = isset($bdata['url']) ? (string) $bdata['url'] : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $seen_uuids[] = $uuid;

            // Upsert: SELECT for id, then INSERT or UPDATE
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $existing_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE page_id = %d AND block_uuid = %s LIMIT 1",
                    $page_id,
                    $uuid
                )
            );

            $row = [
                'page_id'    => $page_id,
                'block_uuid' => $uuid,
                'label'      => mb_substr($label, 0, 255),
                'url'        => $url,
                'icon'       => isset($bdata['icon']) ? mb_substr((string) $bdata['icon'], 0, 64) : null,
                'sort_order' => $sort,
                'is_active'  => 1,
                'utm_params' => isset($bdata['utm']) ? mb_substr((string) $bdata['utm'], 0, 500) : null,
            ];
            if ($existing_id > 0) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->update($table, $row, ['id' => $existing_id]);
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->insert($table, $row);
            }
            $sort++;
        }

        // Mark removed links inactive (we keep them so historical analytics still joins).
        if ($seen_uuids !== []) {
            $placeholders = implode(',', array_fill(0, count($seen_uuids), '%s'));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET is_active = 0 WHERE page_id = %d AND block_uuid NOT IN ($placeholders)",
                    array_merge([$page_id], $seen_uuids)
                )
            );
        } else {
            // No links on the page — deactivate all rows for this page.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET is_active = 0 WHERE page_id = %d", $page_id));
        }
    }

    /**
     * Look up the `wp_biolink_links.id` for a given page + block uuid, syncing if missing.
     */
    public function linkIdFor(int $page_id, string $block_uuid): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE page_id = %d AND block_uuid = %s LIMIT 1", $page_id, $block_uuid)
        );
        if ($id === 0) {
            $this->sync($page_id);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $id = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$table} WHERE page_id = %d AND block_uuid = %s LIMIT 1", $page_id, $block_uuid)
            );
        }
        return $id;
    }

    /**
     * @return array<int, array{id:int, label:string, url:string, block_uuid:string}>
     */
    public function linksForPage(int $page_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_links';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, label, url, block_uuid FROM {$table} WHERE page_id = %d ORDER BY sort_order ASC",
                $page_id
            ),
            ARRAY_A
        );
        return is_array($rows) ? $rows : [];
    }
}
