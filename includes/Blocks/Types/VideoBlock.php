<?php
/**
 * "Video" block — self-hosted .mp4 URL or attachment ID with native <video> player.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class VideoBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'video';
    }

    public function label(): string
    {
        return __('Video', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'play';
    }

    public function schema(): array
    {
        return [
            'url'      => ['type' => 'url', 'default' => ''],
            'id'       => ['type' => 'int', 'default' => 0],
            'autoplay' => ['type' => 'bool', 'default' => false],
            'loop'     => ['type' => 'bool', 'default' => false],
            'muted'    => ['type' => 'bool', 'default' => false],
            'controls' => ['type' => 'bool', 'default' => true],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $src  = (string) ($data['url'] ?? '');
        if ($src === '' && ! empty($data['id'])) {
            $resolved = wp_get_attachment_url((int) $data['id']);
            if (is_string($resolved)) {
                $src = $resolved;
            }
        }
        if ($src === '') {
            return '';
        }

        $attrs = [];
        if (! empty($data['controls'])) {
            $attrs[] = 'controls';
        }
        if (! empty($data['autoplay'])) {
            $attrs[] = 'autoplay';
        }
        if (! empty($data['loop'])) {
            $attrs[] = 'loop';
        }
        if (! empty($data['muted']) || ! empty($data['autoplay'])) {
            $attrs[] = 'muted';
            $attrs[] = 'playsinline';
        }

        $mime = wp_check_filetype($src)['type'] ?? 'video/mp4';

        return sprintf(
            '<div class="bio-block bio-block--video"><video preload="metadata" %1$s><source src="%2$s" type="%3$s"></video></div>',
            esc_attr(implode(' ', $attrs)),
            esc_url($src),
            esc_attr((string) $mime)
        );
    }
}
