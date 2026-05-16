<?php
/**
 * "TikTok" block — embedded video via official blockquote + embed script.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class TiktokBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'tiktok';
    }

    public function label(): string
    {
        return __('TikTok', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'play';
    }

    public function schema(): array
    {
        return [
            'url' => ['type' => 'url', 'required' => true],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $url  = (string) ($data['url'] ?? '');
        if (! preg_match('#tiktok\.com/(?:@[^/]+/)?video/(\d+)#', $url, $m)) {
            return '';
        }
        $video_id = $m[1];

        // bio.js loads the official TikTok embed script when a tiktok block is on the page.
        return sprintf(
            '<div class="bio-block bio-block--tiktok"><blockquote class="tiktok-embed" cite="%1$s" data-video-id="%2$s" style="max-width:605px;min-width:325px;"><section><a href="%1$s" target="_blank" rel="noopener">%3$s</a></section></blockquote></div>',
            esc_url($url),
            esc_attr($video_id),
            esc_html__('View on TikTok', 'biolink-pro')
        );
    }
}
