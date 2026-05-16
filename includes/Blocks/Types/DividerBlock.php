<?php
/**
 * "Divider" block — visual separator (line / dot row / blank space).
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class DividerBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'divider';
    }

    public function label(): string
    {
        return __('Divider', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function schema(): array
    {
        return [
            'style'   => ['type' => 'enum', 'enum' => ['line', 'dots', 'space'], 'default' => 'line'],
            'color'   => ['type' => 'color', 'default' => '#dcdcde'],
            'spacing' => ['type' => 'enum', 'enum' => ['sm', 'md', 'lg'], 'default' => 'md'],
        ];
    }

    public function render(array $data): string
    {
        $data    = FieldValidator::validate($this->schema(), $data);
        $style   = (string) ($data['style'] ?? 'line');
        $color   = (string) ($data['color'] ?? '#dcdcde');
        $spacing = (string) ($data['spacing'] ?? 'md');

        return sprintf(
            '<div class="bio-block bio-block--divider bio-block--divider-%1$s bio-block--divider-spacing-%2$s" style="--bio-divider-color:%3$s" role="separator" aria-hidden="true"></div>',
            esc_attr($style),
            esc_attr($spacing),
            esc_attr($color)
        );
    }
}
