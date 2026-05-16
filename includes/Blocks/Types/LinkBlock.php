<?php
/**
 * "Link" block — labelled card with URL and optional icon / UTM.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Icons;
use BioLinkPro\Blocks\Schema\FieldValidator;

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

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['label']) || empty($data['url'])) {
            return '';
        }

        $url = $data['url'];
        if (! empty($data['utm'])) {
            $url = add_query_arg(self::parseUtm((string) $data['utm']), $url);
        }

        $classes = 'bio-block bio-block--link';
        if (! empty($data['featured'])) {
            $classes .= ' bio-block--featured';
        }

        $icon_svg = Icons::utility((string) ($data['icon'] ?? 'link'));

        return sprintf(
            '<a class="%1$s" href="%2$s" rel="noopener" target="_blank">%3$s<span class="bio-block__label">%4$s</span></a>',
            esc_attr($classes),
            esc_url($url),
            $icon_svg !== '' ? '<span class="bio-block__icon" aria-hidden="true">' . $icon_svg . '</span>' : '',
            esc_html($data['label'])
        );
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
