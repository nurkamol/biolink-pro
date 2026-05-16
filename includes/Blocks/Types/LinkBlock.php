<?php
/**
 * "Link" block — labelled card with URL and optional icon / UTM.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Analytics\LinkSync;
use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Icons;
use BioLinkPro\Blocks\Schema\FieldValidator;
use BioLinkPro\Core\Plugin;
use BioLinkPro\Frontend\UnlockHandler;

defined('ABSPATH') || exit;

final class LinkBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'link';
    }

    public function label(): string
    {
        return __('Link', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'link';
    }

    public function schema(): array
    {
        return [
            'label'    => ['type' => 'string', 'required' => true, 'max' => 120],
            'url'      => ['type' => 'url', 'required' => true],
            'icon'     => ['type' => 'enum', 'enum' => Icons::utilityNames(), 'default' => 'link'],
            'utm'      => ['type' => 'string', 'max' => 200, 'default' => ''],
            'featured' => ['type' => 'bool', 'default' => false],
        ];
    }

    public function render(array $data, ?string $uuid = null): string
    {
        // Preserve the meta keys (_thumbnail_id etc.) the validator would strip.
        $thumbnail_id  = isset($data['_thumbnail_id']) ? (int) $data['_thumbnail_id'] : 0;
        $passcode_hash = isset($data['_passcode_hash']) ? (string) $data['_passcode_hash'] : '';
        $variants      = isset($data['_variants']) && is_array($data['_variants']) ? $data['_variants'] : [];

        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['label']) || empty($data['url'])) {
            return '';
        }

        // A/B variant pick — deterministic per visitor for a given (page, block).
        $variant_key = '';
        $page_id_for_seed = (int) (get_the_ID() ?: 0);
        if ($variants !== [] && $uuid !== null && $page_id_for_seed > 0) {
            $variant = self::pickVariant($variants, $uuid, $page_id_for_seed);
            if ($variant !== null) {
                $variant_key = isset($variant['key']) ? sanitize_key((string) $variant['key']) : '';
                if (isset($variant['label']) && is_string($variant['label']) && $variant['label'] !== '') {
                    $data['label'] = $variant['label'];
                }
                if (isset($variant['url']) && is_string($variant['url']) && $variant['url'] !== '') {
                    $data['url'] = $variant['url'];
                }
            }
        }

        $url = $data['url'];

        // Passcode-gated links bypass click tracking + UTM until unlocked.
        // Once the visitor has cleared the passcode for this (page, uuid),
        // the cookie tells us to skip the unlock form on subsequent clicks.
        $page_id = (int) (get_the_ID() ?: 0);
        $needs_unlock = $passcode_hash !== ''
            && $uuid !== null
            && $page_id > 0
            && ! UnlockHandler::isUnlocked($page_id, $uuid);

        if ($needs_unlock) {
            $url = add_query_arg(
                ['biolink_unlock' => $uuid],
                get_permalink($page_id)
            );
        } else {
            // Reset $page_id for the click-tracking block below using $page_id_for_seed
            // since we may have already established it for variant picking.
            $page_id = $page_id_for_seed > 0 ? $page_id_for_seed : $page_id;
            // Route through /click/{id} when we have a stable link_id so analytics
            // can record the click + apply UTM at redirect time.
            if ($page_id > 0 && $uuid !== null) {
                $sync = Plugin::instance()->get(LinkSync::class);
                if ($sync instanceof LinkSync) {
                    $link_id = $sync->linkIdFor($page_id, $uuid);
                    if ($link_id > 0) {
                        $click_url = rest_url('biolink/v1/click/' . $link_id);
                        if ($variant_key !== '') {
                            $click_url = add_query_arg(['v' => $variant_key], $click_url);
                        }
                        $url = $click_url;
                    }
                }
            } elseif (! empty($data['utm'])) {
                // Fallback: append UTM inline if click tracking isn't wired up.
                $url = add_query_arg(self::parseUtm((string) $data['utm']), $url);
            }
        }

        $classes = 'bio-block bio-block--link';
        if (! empty($data['featured'])) {
            $classes .= ' bio-block--featured';
        }
        if ($passcode_hash !== '') {
            $classes .= ' bio-block--locked';
        }

        $icon_svg = Icons::utility((string) ($data['icon'] ?? 'link'));

        // Per-block thumbnail overrides the icon glyph when set.
        $leading = '';
        if ($thumbnail_id > 0) {
            $thumb = wp_get_attachment_image(
                $thumbnail_id,
                'thumbnail',
                false,
                [
                    'class'   => 'bio-block__thumb',
                    'loading' => 'lazy',
                    'alt'     => esc_attr($data['label']),
                ]
            );
            if ($thumb !== '') {
                $leading = $thumb;
                $classes .= ' bio-block--has-thumb';
            }
        }
        if ($leading === '' && $icon_svg !== '') {
            $leading = '<span class="bio-block__icon" aria-hidden="true">' . $icon_svg . '</span>';
        }

        // Locked + not-yet-unlocked links open in the same window so the form lands cleanly.
        $target = $needs_unlock ? '_self' : '_blank';
        $lock_indicator = $passcode_hash !== ''
            ? '<span class="bio-block__lock" aria-hidden="true">🔒</span>'
            : '';

        return sprintf(
            '<a class="%1$s" href="%2$s" rel="noopener" target="%3$s">%4$s<span class="bio-block__label">%5$s</span>%6$s</a>',
            esc_attr($classes),
            esc_url($url),
            esc_attr($target),
            $leading,
            esc_html($data['label']),
            $lock_indicator
        );
    }

    /**
     * Pick an A/B variant deterministically based on (visitor IP + page + uuid)
     * so the same visitor sees the same variant on repeat visits.
     *
     * @param list<array<string, mixed>> $variants
     * @return array<string, mixed>|null
     */
    private static function pickVariant(array $variants, string $uuid, int $page_id): ?array
    {
        $valid = [];
        $total = 0;
        foreach ($variants as $v) {
            if (! is_array($v)) {
                continue;
            }
            $weight = max(1, (int) ($v['weight'] ?? 50));
            $valid[] = ['v' => $v, 'w' => $weight];
            $total  += $weight;
        }
        if ($valid === []) {
            return null;
        }
        $ip   = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '0';
        $seed = $ip . '|' . $page_id . '|' . $uuid;
        $bucket = hexdec(substr(md5($seed), 0, 8)) % $total;
        $running = 0;
        foreach ($valid as $entry) {
            $running += $entry['w'];
            if ($bucket < $running) {
                return $entry['v'];
            }
        }
        return $valid[array_key_last($valid)]['v'];
    }

    /**
     * @return array<string, string>
     */
    private static function parseUtm(string $raw): array
    {
        $out = [];
        parse_str(ltrim($raw, '?&'), $parsed);
        foreach ($parsed as $k => $v) {
            $k = (string) $k;
            if (str_starts_with($k, 'utm_') && is_scalar($v)) {
                $out[$k] = (string) $v;
            }
        }
        return $out;
    }
}
