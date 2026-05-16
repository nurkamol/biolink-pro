<?php
/**
 * "Social icons" block — row of brand-mark links.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Icons;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class SocialIconsBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'social_icons';
    }

    public function label(): string
    {
        return __('Social Icons', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function schema(): array
    {
        return [
            'items' => [
                'type'  => 'array',
                'items' => [
                    'platform' => ['type' => 'enum', 'enum' => Icons::socialPlatforms(), 'required' => true],
                    'url'      => ['type' => 'url', 'required' => true],
                ],
                'default' => [],
            ],
        ];
    }

    public function render(array $data): string
    {
        $data  = FieldValidator::validate($this->schema(), $data);
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items === []) {
            return '';
        }

        $html = '<div class="bio-block bio-block--social">';
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $platform = (string) ($item['platform'] ?? '');
            $url      = (string) ($item['url'] ?? '');
            $svg      = Icons::social($platform);
            if ($platform === '' || $url === '' || $svg === '') {
                continue;
            }
            $html .= sprintf(
                '<a class="bio-block__social-link" href="%1$s" rel="noopener" target="_blank" aria-label="%2$s">%3$s</a>',
                esc_url($url),
                esc_attr(ucfirst($platform)),
                $svg
            );
        }
        $html .= '</div>';

        return $html;
    }
}
