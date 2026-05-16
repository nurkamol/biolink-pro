<?php
/**
 * "Map" block — embedded OpenStreetMap (no API key required).
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class MapBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'map';
    }

    public function label(): string
    {
        return __('Map', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'pin';
    }

    public function schema(): array
    {
        return [
            'lat'   => ['type' => 'string', 'required' => true, 'max' => 32],
            'lng'   => ['type' => 'string', 'required' => true, 'max' => 32],
            'zoom'  => ['type' => 'int', 'default' => 14],
            'label' => ['type' => 'string', 'max' => 120, 'default' => ''],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $lat  = (float) ($data['lat'] ?? 0);
        $lng  = (float) ($data['lng'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return '';
        }
        $zoom = max(1, min(19, (int) ($data['zoom'] ?? 14)));

        // OpenStreetMap embed — no API key needed
        $delta = 0.01 / max(1, $zoom / 8);
        $bbox  = sprintf(
            '%F,%F,%F,%F',
            $lng - $delta,
            $lat - $delta,
            $lng + $delta,
            $lat + $delta
        );
        $src = sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%s&layer=mapnik&marker=%F,%F',
            $bbox,
            $lat,
            $lng
        );
        $label = (string) ($data['label'] ?? '');

        return sprintf(
            '<div class="bio-block bio-block--map">%1$s<iframe src="%2$s" width="100%%" height="280" loading="lazy" referrerpolicy="no-referrer-when-downgrade" sandbox="allow-scripts allow-same-origin allow-popups" title="%3$s"></iframe></div>',
            $label !== '' ? '<p class="bio-block__map-label">' . esc_html($label) . '</p>' : '',
            esc_url($src),
            esc_attr__('Map', 'biolink-pro')
        );
    }
}
