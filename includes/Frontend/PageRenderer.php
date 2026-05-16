<?php
/**
 * Renders a bio page to HTML by dispatching each stored block through its block class.
 *
 * @package BioLinkPro\Frontend
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend;

use BioLinkPro\Blocks\BlockRegistry;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Frontend\UnlockHandler;
use BioLinkPro\Themes\ThemeEngine;
use WP_Post;

defined('ABSPATH') || exit;

final class PageRenderer
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly PageRepository $repository,
        private readonly ThemeEngine $themes
    ) {
    }

    public function themes(): ThemeEngine
    {
        return $this->themes;
    }

    /**
     * Render the avatar/headline/subheadline header for a page.
     *
     * @param array<string, mixed> $settings
     */
    public function renderHeader(WP_Post $post, array $settings): string
    {
        $avatar_id   = isset($settings['avatar_id']) ? (int) $settings['avatar_id'] : 0;
        $handle      = isset($settings['handle']) ? trim((string) $settings['handle']) : '';
        $headline    = isset($settings['headline']) && trim((string) $settings['headline']) !== ''
            ? (string) $settings['headline']
            : $post->post_title;
        $subheadline = isset($settings['subheadline']) ? (string) $settings['subheadline'] : '';
        $show_name   = empty($settings['hide_name']);

        $html = '<header class="bio-header">';

        if ($avatar_id > 0) {
            $img = wp_get_attachment_image(
                $avatar_id,
                'thumbnail',
                false,
                [
                    'class'   => 'bio-header__avatar',
                    'loading' => 'eager',
                    'alt'     => esc_attr($headline),
                ]
            );
            if ($img !== '') {
                $html .= $img;
            }
        }

        if ($show_name && $headline !== '') {
            $html .= '<h1 class="bio-header__title">' . esc_html($headline) . '</h1>';
        }

        if ($handle !== '') {
            $handle_display = str_starts_with($handle, '@') ? $handle : '@' . $handle;
            $html .= '<p class="bio-header__handle">' . esc_html($handle_display) . '</p>';
        }

        if ($subheadline !== '') {
            $html .= '<p class="bio-header__subtitle">' . esc_html($subheadline) . '</p>';
        }

        $html .= '</header>';
        return $html;
    }

    /**
     * "Powered by" footer credit. Honors the `biolink_settings.show_credit` toggle
     * (default true) — site owners can disable it.
     */
    private function renderCredit(): string
    {
        $settings    = (array) get_option('biolink_settings', []);
        $show_credit = ! array_key_exists('show_credit', $settings) || (bool) $settings['show_credit'];
        if (! $show_credit) {
            return '';
        }
        return sprintf(
            '<p class="bio-page__credit"><a href="https://github.com/nurkamol/biolink-pro" target="_blank" rel="noopener">%s</a></p>',
            esc_html__('Made with BioLink Pro', 'biolink-pro')
        );
    }

    /**
     * Render a generic locked-content placeholder. Click → unlock form.
     */
    private function renderLockedPlaceholder(int $page_id, string $uuid, string $label): string
    {
        $url = add_query_arg(['biolink_unlock' => $uuid], get_permalink($page_id));
        return sprintf(
            '<a class="bio-block bio-block--locked-placeholder" href="%s">'
            . '<span class="bio-block__lock" aria-hidden="true">🔒</span>'
            . '<span class="bio-block__label">%s</span>'
            . '<span class="bio-block__sub">%s</span>'
            . '</a>',
            esc_url($url),
            esc_html($label),
            esc_html__('Click to unlock', 'biolink-pro')
        );
    }

    /**
     * Decide whether a block is inside its visibility window.
     *
     * @param array<string, mixed> $data
     */
    private function isScheduleActive(array $data): bool
    {
        $start = isset($data['_start_at']) && $data['_start_at'] !== ''
            ? strtotime((string) $data['_start_at'])
            : false;
        $end = isset($data['_end_at']) && $data['_end_at'] !== ''
            ? strtotime((string) $data['_end_at'])
            : false;

        if ($start === false && $end === false) {
            return true;
        }

        // current_time('timestamp') returns site-local time, matching the
        // datetime-local values the admin writes.
        $now = (int) current_time('timestamp');
        if ($start !== false && $now < $start) {
            return false;
        }
        if ($end !== false && $now > $end) {
            return false;
        }
        return true;
    }

    /**
     * Render every block on the page to a stream of HTML.
     *
     * @param list<array<string, mixed>> $blocks
     */
    public function renderBlocks(array $blocks): string
    {
        $html = '<div class="bio-blocks">';
        foreach ($blocks as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }
            $instance = $this->registry->get((string) $block['type']);
            if ($instance === null) {
                continue;
            }
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            // Per-block visibility toggle (Linktree-style on/off switch in the admin).
            if (array_key_exists('_active', $data) && $data['_active'] === false) {
                continue;
            }

            // Per-block schedule window. Times are stored as "YYYY-MM-DDTHH:MM:SS"
            // in the site's local timezone (the admin writes datetime-local values).
            if (! $this->isScheduleActive($data)) {
                continue;
            }

            $uuid = isset($block['uuid']) && is_string($block['uuid']) ? $block['uuid'] : null;

            // Passcode gate: if the block has _passcode_hash and the visitor
            // hasn't unlocked it, replace the output with a placeholder card
            // (skipping LinkBlock, which renders its own unlock-routed href).
            $hash       = isset($data['_passcode_hash']) ? (string) $data['_passcode_hash'] : '';
            $is_link    = $instance instanceof \BioLinkPro\Blocks\Types\LinkBlock;
            $page_id    = (int) (get_the_ID() ?: 0);
            $unlocked   = $uuid !== null && $page_id > 0
                ? UnlockHandler::isUnlocked($page_id, $uuid)
                : false;
            $gated      = $hash !== '' && ! $unlocked && ! $is_link;

            if ($gated) {
                $label = isset($data['label']) && is_string($data['label']) && $data['label'] !== ''
                    ? (string) $data['label']
                    : (string) ($data['heading'] ?? $data['name'] ?? __('Locked content', 'biolink-pro'));
                $html .= $this->renderLockedPlaceholder($page_id, (string) $uuid, $label);
                continue;
            }

            // LinkBlock + DonationBlock + ProductCardBlock take uuid as a 2nd arg
            // (click tracking + checkout-form correlation).
            if ($is_link
                || $instance instanceof \BioLinkPro\Blocks\Types\DonationBlock
                || $instance instanceof \BioLinkPro\Blocks\Types\ProductCardBlock
            ) {
                $rendered = $instance->render($data, $uuid);
            } else {
                $rendered = $instance->render($data);
            }

            /**
             * Filter a block's HTML before it lands on the page.
             *
             * @param string               $output
             * @param string               $type
             * @param array<string, mixed> $data
             * @param string|null          $uuid
             */
            $output = apply_filters('biolink/block/render', $rendered, (string) $block['type'], $data, $uuid);
            if (! is_string($output)) {
                continue;
            }

            // Highlight wrapper class — drives the pulse animation on the public template.
            if (! empty($data['_highlight'])) {
                $output = '<div class="bio-block bio-block--highlight">' . $output . '</div>';
            }

            $html .= $output;
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Render an entire page (header + blocks).
     */
    public function render(WP_Post $post): string
    {
        $page = $this->repository->findById($post->ID);
        if ($page === null) {
            return '';
        }
        $data     = $page['data'];
        $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $blocks   = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];

        /**
         * Fires before block stream renders.
         *
         * @param WP_Post              $post
         * @param array<string, mixed> $data
         */
        do_action('biolink/page/render/before', $post, $data);

        $theme_slug = (string) ($data['theme'] ?? ThemeEngine::DEFAULT_SLUG);
        if (! $this->themes->has($theme_slug)) {
            $theme_slug = ThemeEngine::DEFAULT_SLUG;
        }

        $html  = $this->themes->renderStyleBlock($theme_slug, $settings);
        $html .= '<article class="bio-page bio-theme-' . esc_attr($theme_slug) . '">';
        $html .= $this->renderHeader($post, $settings);
        $html .= $this->renderBlocks($blocks);
        $html .= $this->renderCredit();
        $html .= '</article>';

        /**
         * Filter the entire bio page HTML.
         *
         * @param string               $html
         * @param WP_Post              $post
         * @param array<string, mixed> $data
         */
        return (string) apply_filters('biolink/page/render', $html, $post, $data);
    }
}
