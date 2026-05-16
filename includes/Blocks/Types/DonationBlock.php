<?php
/**
 * "Donation" block — accepts donations via Stripe Checkout, PayPal Orders,
 * or a plain external link (PayPal.me / Stripe Payment Link).
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;
use BioLinkPro\Core\Plugin;
use BioLinkPro\Integrations\PayPal\Checkout as PayPalCheckout;
use BioLinkPro\Integrations\Stripe\Checkout as StripeCheckout;

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
            'cta_url'     => ['type' => 'url', 'default' => ''],
            'provider'    => ['type' => 'enum', 'enum' => ['link', 'stripe', 'paypal'], 'default' => 'link'],
        ];
    }

    public function render(array $data, ?string $uuid = null): string
    {
        $data     = FieldValidator::validate($this->schema(), $data);
        $provider = $this->resolveProvider((string) ($data['provider'] ?? 'link'), (string) ($data['cta_url'] ?? ''));

        if ($provider === 'link' && empty($data['cta_url'])) {
            return '';
        }

        $amounts_html = $this->renderAmountsChips($data, $provider);
        $form_or_link = $this->renderCta($data, $provider, $uuid);

        return sprintf(
            '<div class="bio-block bio-block--donation"><h3 class="bio-block__donation-heading">%1$s</h3>%2$s%3$s%4$s</div>',
            esc_html((string) $data['heading']),
            ! empty($data['description'])
                ? '<p class="bio-block__donation-desc">' . esc_html((string) $data['description']) . '</p>'
                : '',
            $amounts_html,
            $form_or_link
        );
    }

    private function resolveProvider(string $provider, string $cta_url): string
    {
        if ($provider === 'stripe') {
            $stripe = Plugin::instance()->get(StripeCheckout::class);
            if ($stripe instanceof StripeCheckout && $stripe->isConfigured()) {
                return 'stripe';
            }
        }
        if ($provider === 'paypal') {
            $paypal = Plugin::instance()->get(PayPalCheckout::class);
            if ($paypal instanceof PayPalCheckout && $paypal->isConfigured()) {
                return 'paypal';
            }
        }
        return 'link';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderAmountsChips(array $data, string $provider): string
    {
        if (empty($data['amounts']) || ! is_array($data['amounts'])) {
            return '';
        }
        $currency = strtoupper((string) ($data['currency'] ?? 'USD'));
        $chips    = [];
        foreach ($data['amounts'] as $amount) {
            if (! is_numeric($amount) || (float) $amount <= 0) {
                continue;
            }
            $value = (float) $amount;
            if ($provider === 'link') {
                $chips[] = '<span class="bio-block__donation-chip">' . esc_html($currency . ' ' . (string) $value) . '</span>';
            } else {
                $chips[] = sprintf(
                    '<button type="submit" class="bio-block__donation-chip bio-block__donation-chip--btn" name="amount" value="%s">%s</button>',
                    esc_attr((string) $value),
                    esc_html($currency . ' ' . (string) $value)
                );
            }
        }
        return $chips === [] ? '' : '<div class="bio-block__donation-chips">' . implode('', $chips) . '</div>';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderCta(array $data, string $provider, ?string $uuid): string
    {
        $currency  = strtoupper((string) ($data['currency'] ?? 'USD'));
        $cta_label = (string) ($data['cta_label'] ?? 'Donate');

        if ($provider === 'link') {
            return sprintf(
                '<a class="bio-block__donation-cta" href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url((string) $data['cta_url']),
                esc_html($cta_label)
            );
        }

        $page_id        = (int) (get_the_ID() ?: 0);
        $endpoint       = $provider === 'stripe' ? '/biolink/v1/stripe/checkout' : '/biolink/v1/paypal/checkout';
        $name           = (string) ($data['heading'] ?? 'Donation');
        $default_amount = '';
        if (! empty($data['amounts']) && is_array($data['amounts'])) {
            foreach ($data['amounts'] as $a) {
                if (is_numeric($a) && (float) $a > 0) {
                    $default_amount = (string) (float) $a;
                    break;
                }
            }
        }

        return sprintf(
            '<form class="bio-block__donation-form" data-action="checkout" data-provider="%1$s" data-endpoint="%2$s" data-page="%3$d" data-block="%4$s" data-currency="%5$s" data-name="%6$s">' .
                '<input type="number" name="amount" min="1" step="0.01" placeholder="%7$s" value="%8$s" required class="bio-block__donation-input">' .
                '<button type="submit" class="bio-block__donation-cta">%9$s</button>' .
                '<div class="bio-block__donation-status" aria-live="polite"></div>' .
            '</form>',
            esc_attr($provider),
            esc_attr(rest_url($endpoint)),
            $page_id,
            esc_attr((string) $uuid),
            esc_attr($currency),
            esc_attr($name),
            esc_attr__('Amount', 'biolink-pro'),
            esc_attr($default_amount),
            esc_html($cta_label)
        );
    }
}
