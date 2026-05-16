<?php
/**
 * "Donation" block — links to an external payment URL (PayPal.me, Stripe
 * Payment Link, etc). Full Stripe / PayPal SDK integration ships in Phase 7.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class DonationBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'donation';
    }

    public function label(): string
    {
        return __('Donation', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'heart';
    }

    public function schema(): array
    {
        return [
            'heading'     => ['type' => 'string', 'max' => 200, 'default' => __('Support my work', 'biolink-pro')],
            'description' => ['type' => 'string', 'max' => 300, 'default' => ''],
            'amounts'     => [
                'type'    => 'array',
                'items'   => null,
                'default' => [],
            ],
            'currency'    => ['type' => 'string', 'max' => 4, 'default' => 'USD'],
            'cta_label'   => ['type' => 'string', 'max' => 40, 'default' => __('Donate', 'biolink-pro')],
            'cta_url'     => ['type' => 'url', 'required' => true],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['cta_url'])) {
            return '';
        }

        $amounts_html = '';
        if (! empty($data['amounts']) && is_array($data['amounts'])) {
            $currency = strtoupper((string) ($data['currency'] ?? 'USD'));
            $chips = [];
            foreach ($data['amounts'] as $amount) {
                if (is_numeric($amount)) {
                    $chips[] = '<span class="bio-block__donation-chip">' . esc_html($currency . ' ' . (string) $amount) . '</span>';
                }
            }
            if ($chips !== []) {
                $amounts_html = '<div class="bio-block__donation-chips">' . implode('', $chips) . '</div>';
            }
        }

        return sprintf(
            '<div class="bio-block bio-block--donation"><h3 class="bio-block__donation-heading">%1$s</h3>%2$s%3$s<a class="bio-block__donation-cta" href="%4$s" target="_blank" rel="noopener">%5$s</a></div>',
            esc_html((string) $data['heading']),
            ! empty($data['description'])
                ? '<p class="bio-block__donation-desc">' . esc_html((string) $data['description']) . '</p>'
                : '',
            $amounts_html,
            esc_url((string) $data['cta_url']),
            esc_html((string) ($data['cta_label'] ?? 'Donate'))
        );
    }
}
