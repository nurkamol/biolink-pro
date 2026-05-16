<?php
/**
 * "Product card" block — image, name, price, CTA.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class ProductCardBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'product_card';
    }

    public function label(): string
    {
        return __('Product Card', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'shopping-bag';
    }

    public function schema(): array
    {
        return [
            'image_id'    => ['type' => 'int', 'default' => 0],
            'name'        => ['type' => 'string', 'required' => true, 'max' => 120],
            'description' => ['type' => 'string', 'max' => 300, 'default' => ''],
            'price'       => ['type' => 'string', 'max' => 32, 'default' => ''],
            'cta_label'   => ['type' => 'string', 'max' => 40, 'default' => __('Buy now', 'biolink-pro')],
            'cta_url'     => ['type' => 'url', 'required' => true],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['name']) || empty($data['cta_url'])) {
            return '';
        }

        $image = '';
        if (! empty($data['image_id'])) {
            $image = wp_get_attachment_image(
                (int) $data['image_id'],
                'medium',
                false,
                [
                    'class'   => 'bio-block__product-img',
                    'loading' => 'lazy',
                    'alt'     => esc_attr((string) $data['name']),
                ]
            );
        }

        return sprintf(
            '<div class="bio-block bio-block--product">%1$s<div class="bio-block__product-body"><h3 class="bio-block__product-name">%2$s</h3>%3$s%4$s<a class="bio-block__product-cta" href="%5$s" target="_blank" rel="noopener">%6$s</a></div></div>',
            $image,
            esc_html((string) $data['name']),
            ! empty($data['description']) ? '<p class="bio-block__product-desc">' . esc_html((string) $data['description']) . '</p>' : '',
            ! empty($data['price']) ? '<p class="bio-block__product-price">' . esc_html((string) $data['price']) . '</p>' : '',
            esc_url((string) $data['cta_url']),
            esc_html((string) ($data['cta_label'] ?? 'Buy now'))
        );
    }
}
