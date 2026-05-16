<?php
/**
 * Bundled starter-template library + apply-as-new-page logic.
 *
 * Templates are JSON files under `templates/data/`. Each carries a theme, page
 * settings, and a list of blocks. Applying creates a fresh `biolink_page` draft.
 *
 * @package BioLinkPro\Templates
 */

declare(strict_types=1);

namespace BioLinkPro\Templates;

use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use WP_Error;
use WP_Post;

defined('ABSPATH') || exit;

final class TemplateLibrary
{
    public function __construct(private readonly PageRepository $repository)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $dir = BIOLINK_PATH . 'templates/data';
        if (! is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '/*.json') ?: [];
        $out = [];
        foreach ($files as $file) {
            $raw = file_get_contents($file);
            if (! is_string($raw)) {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (! is_array($decoded) || empty($decoded['slug'])) {
                continue;
            }
            $out[] = $decoded;
        }
        usort($out, static fn($a, $b) => strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? '')));
        return $out;
    }

    public function get(string $slug): ?array
    {
        foreach ($this->all() as $tpl) {
            if (($tpl['slug'] ?? '') === $slug) {
                return $tpl;
            }
        }
        return null;
    }

    public function apply(string $slug): WP_Post|WP_Error
    {
        $tpl = $this->get($slug);
        if ($tpl === null) {
            return new WP_Error('biolink_template_not_found', __('Template not found.', 'biolink-pro'), ['status' => 404]);
        }

        $post_id = wp_insert_post([
            'post_type'   => BioLinkPagePostType::POST_TYPE,
            'post_title'  => (string) ($tpl['label'] ?? 'New bio page'),
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ], true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $blocks = [];
        foreach ((array) ($tpl['blocks'] ?? []) as $block) {
            if (! is_array($block)) {
                continue;
            }
            $blocks[] = [
                'uuid' => wp_generate_uuid4(),
                'type' => (string) ($block['type'] ?? ''),
                'data' => is_array($block['data'] ?? null) ? $block['data'] : [],
            ];
        }

        $this->repository->saveData((int) $post_id, [
            'theme'    => (string) ($tpl['theme'] ?? 'mono'),
            'settings' => is_array($tpl['settings'] ?? null) ? $tpl['settings'] : [],
            'blocks'   => $blocks,
            'seo'      => is_array($tpl['seo'] ?? null) ? $tpl['seo'] : [],
        ]);

        $post = get_post($post_id);
        return $post instanceof WP_Post ? $post : new WP_Error('biolink_template_apply_failed', 'Could not load created page.', ['status' => 500]);
    }
}
