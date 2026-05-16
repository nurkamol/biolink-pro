<?php
/**
 * "Image gallery" block — list of WP media-library attachments rendered as a grid.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class ImageGalleryBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'image_gallery';
    }

    public function label(): string
    {
        return __('Image Gallery', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function schema(): array
    {
        return [
            'ids'    => [
                'type'    => 'array',
                'items'   => null,
                'default' => [],
            ],
            'layout' => ['type' => 'enum', 'enum' => ['grid', 'list'], 'default' => 'grid'],
            'size'   => ['type' => 'enum', 'enum' => ['medium', 'large', 'full'], 'default' => 'medium'],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $ids  = [];
        foreach ((array) ($data['ids'] ?? []) as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }
        if ($ids === []) {
            return '';
        }

        $layout = $data['layout'] ?? 'grid';
        $size   = $data['size'] ?? 'medium';

        $html = sprintf('<div class="bio-block bio-block--gallery bio-block--gallery-%s">', esc_attr((string) $layout));
        foreach ($ids as $id) {
            $img = wp_get_attachment_image(
                $id,
                $size,
                false,
                [
                    'class'   => 'bio-block__image',
                    'loading' => 'lazy',
                ]
            );
            if ($img === '') {
                continue;
            }
            $full = wp_get_attachment_image_url($id, 'full');
            if ($full !== false) {
                $html .= sprintf(
                    '<a class="bio-block__image-link" href="%s" target="_blank" rel="noopener">%s</a>',
                    esc_url($full),
                    $img
                );
            } else {
                $html .= $img;
            }
        }
        $html .= '</div>';
        return $html;
    }
}
