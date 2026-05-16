<?php
/**
 * "Spotify" block — track / album / playlist embed.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class SpotifyBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'spotify';
    }

    public function label(): string
    {
        return __('Spotify', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'play';
    }

    public function schema(): array
    {
        return [
            'url'    => ['type' => 'url', 'required' => true],
            'height' => ['type' => 'enum', 'enum' => ['compact', 'normal', 'tall'], 'default' => 'normal'],
            'theme'  => ['type' => 'enum', 'enum' => ['default', 'black'], 'default' => 'default'],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $url  = (string) ($data['url'] ?? '');
        $info = self::parseSpotifyUrl($url);
        if ($info === null) {
            return '';
        }

        $heights = ['compact' => 152, 'normal' => 232, 'tall' => 380];
        $height  = $heights[$data['height'] ?? 'normal'] ?? 232;
        $theme   = $data['theme'] === 'black' ? '0' : '1';

        $src = sprintf(
            'https://open.spotify.com/embed/%s/%s?utm_source=biolink&theme=%s',
            $info['kind'],
            $info['id'],
            $theme
        );

        return sprintf(
            '<div class="bio-block bio-block--spotify"><iframe src="%1$s" height="%2$d" width="100%%" allowtransparency="true" allow="encrypted-media; clipboard-write" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-popups" title="%3$s"></iframe></div>',
            esc_url($src),
            (int) $height,
            esc_attr__('Spotify embed', 'biolink-pro')
        );
    }

    /**
     * @return array{kind: string, id: string}|null
     */
    private static function parseSpotifyUrl(string $url): ?array
    {
        if (preg_match('#open\.spotify\.com/(track|album|playlist|episode|show)/([A-Za-z0-9]+)#i', $url, $m)) {
            return ['kind' => strtolower($m[1]), 'id' => $m[2]];
        }
        if (preg_match('#spotify:(track|album|playlist|episode|show):([A-Za-z0-9]+)#i', $url, $m)) {
            return ['kind' => strtolower($m[1]), 'id' => $m[2]];
        }
        return null;
    }
}
