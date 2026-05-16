<?php
/**
 * "Button" block — call-to-action with variant + size.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Icons;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class ButtonBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'button';
    }

    public function label(): string
    {
        return __('Button', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'arrow-right';
    }

    public function schema(): array
    {
        return [
            'label'   => ['type' => 'string', 'required' => true, 'max' => 80],
            'url'     => ['type' => 'url', 'required' => true],
            'variant' => ['type' => 'enum', 'enum' => ['primary', 'secondary', 'ghost'], 'default' => 'primary'],
            'size'    => ['type' => 'enum', 'enum' => ['sm', 'md', 'lg'], 'default' => 'md'],
            'icon'    => ['type' => 'enum', 'enum' => Icons::utilityNames(), 'default' => ''],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['label']) || empty($data['url'])) {
            return '';
        }

        $classes = sprintf(
            'bio-block bio-block--button bio-block--button-%s bio-block--button-%s',
            $data['variant'] ?? 'primary',
            $data['size'] ?? 'md'
        );

        $icon_svg = ! empty($data['icon']) ? Icons::utility((string) $data['icon']) : '';

        return sprintf(
            '<a class="%1$s" href="%2$s" rel="noopener" target="_blank">%3$s<span class="bio-block__label">%4$s</span></a>',
            esc_attr($classes),
            esc_url((string) $data['url']),
            $icon_svg !== '' ? '<span class="bio-block__icon" aria-hidden="true">' . $icon_svg . '</span>' : '',
            esc_html((string) $data['label'])
        );
    }
}
