<?php
/**
 * "Countdown" block — live countdown to a target datetime, JS-driven.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class CountdownBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'countdown';
    }

    public function label(): string
    {
        return __('Countdown', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'calendar';
    }

    public function schema(): array
    {
        return [
            'label'           => ['type' => 'string', 'max' => 200, 'default' => ''],
            'target'          => ['type' => 'string', 'required' => true, 'max' => 64],
            'expired_message' => ['type' => 'string', 'max' => 200, 'default' => __('We\'re live!', 'biolink-pro')],
        ];
    }

    public function render(array $data): string
    {
        $data   = FieldValidator::validate($this->schema(), $data);
        $target = (string) ($data['target'] ?? '');
        if ($target === '') {
            return '';
        }
        // Try to parse the target into ISO-8601 with UTC offset
        $ts = strtotime($target);
        if ($ts === false) {
            return '';
        }
        $iso = gmdate('Y-m-d\TH:i:s\Z', $ts);

        $label   = (string) ($data['label'] ?? '');
        $expired = (string) ($data['expired_message'] ?? '');

        return sprintf(
            '<div class="bio-block bio-block--countdown" data-target="%1$s" data-expired="%2$s">%3$s<div class="bio-block__countdown-grid"><span class="bio-block__countdown-cell"><span class="bio-block__countdown-num" data-unit="d">0</span><span class="bio-block__countdown-lbl">%4$s</span></span><span class="bio-block__countdown-cell"><span class="bio-block__countdown-num" data-unit="h">0</span><span class="bio-block__countdown-lbl">%5$s</span></span><span class="bio-block__countdown-cell"><span class="bio-block__countdown-num" data-unit="m">0</span><span class="bio-block__countdown-lbl">%6$s</span></span><span class="bio-block__countdown-cell"><span class="bio-block__countdown-num" data-unit="s">0</span><span class="bio-block__countdown-lbl">%7$s</span></span></div></div>',
            esc_attr($iso),
            esc_attr($expired),
            $label !== '' ? '<p class="bio-block__countdown-label">' . esc_html($label) . '</p>' : '',
            esc_html__('days', 'biolink-pro'),
            esc_html__('hours', 'biolink-pro'),
            esc_html__('mins', 'biolink-pro'),
            esc_html__('secs', 'biolink-pro')
        );
    }
}
