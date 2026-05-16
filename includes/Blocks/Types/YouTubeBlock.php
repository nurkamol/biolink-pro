<?php
/**
 * "YouTube" block — lite facade (no iframe / JS until the user clicks).
 *
 * Renders a static thumbnail + play button; the bio.js progressive-enhancement
 * bundle swaps in the real iframe on first interaction.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Icons;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class YouTubeBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return __('YouTube', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'play';
    }

    public function schema(): array
    {
        return [
            'url'   => ['type' => 'url', 'required' => true],
            'title' => ['type' => 'string', 'max' => 200, 'default' => ''],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $url  = (string) ($data['url'] ?? '');
        if ($url === '') {
            return '';
        }
        $video_id = self::extractVideoId($url);
        if ($video_id === '') {
            return '';
        }

        $title = (string) ($data['title'] ?? '');
        if ($title === '') {
            $title = __('YouTube video', 'biolink-pro');
        }

        $thumb = sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $video_id);
        $play  = Icons::utility('play');

        return sprintf(
            '<div class="bio-block bio-block--youtube" data-yt-id="%1$s">' .
                '<button type="button" class="bio-block__yt-facade" aria-label="%2$s">' .
                    '<img src="%3$s" alt="" loading="lazy" class="bio-block__yt-thumb">' .
                    '<span class="bio-block__yt-play" aria-hidden="true">%4$s</span>' .
                '</button>' .
            '</div>',
            esc_attr($video_id),
            esc_attr(
                /* translators: %s: video title */
                sprintf(__('Play %s', 'biolink-pro'), $title)
            ),
            esc_url($thumb),
            $play
        );
    }

    private static function extractVideoId(string $url): string
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?(?:[^&]*&)*v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $url, $m)) {
            return $m[1];
        }
        // Bare ID
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }
        return '';
    }
}
