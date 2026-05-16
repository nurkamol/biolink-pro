<?php
/**
 * "Product card" block — image, name, price, CTA.
 *
 * Provider modes:
 *   - link              → external URL (Stripe Payment Link, Gumroad, etc.)
 *   - stripe            → opens a Stripe Checkout session with the configured price
 *   - paypal            → opens a PayPal approval flow
 *   - stripe_and_paypal → Stripe primary button + PayPal alt below
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
            'price_value' => ['type' => 'string', 'max' => 16, 'default' => ''],
            'currency'    => ['type' => 'string', 'max' => 4, 'default' => 'USD'],
            'cta_label'   => ['type' => 'string', 'max' => 40, 'default' => __('Buy now', 'biolink-pro')],
            'cta_url'     => ['type' => 'url', 'default' => ''],
            'provider'    => ['type' => 'enum', 'enum' => ['link', 'stripe', 'paypal', 'stripe_and_paypal'], 'default' => 'link'],
        ];
    }

    public function render(array $data, ?string $uuid = null): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        if (empty($data['name'])) {
            return '';
        }

        $provider = $this->resolveProvider((string) ($data['provider'] ?? 'link'));

        if ($provider === 'link' && empty($data['cta_url'])) {
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
            '<div class="bio-block bio-block--product">%1$s<div class="bio-block__product-body"><h3 class="bio-block__product-name">%2$s</h3>%3$s%4$s%5$s</div></div>',
            $image,
            esc_html((string) $data['name']),
            ! empty($data['description']) ? '<p class="bio-block__product-desc">' . esc_html((string) $data['description']) . '</p>' : '',
            ! empty($data['price']) ? '<p class="bio-block__product-price">' . esc_html((string) $data['price']) . '</p>' : '',
            $this->renderCta($data, $provider, $uuid)
        );
    }

    private function resolveProvider(string $provider): string
    {
        if ($provider === 'stripe_and_paypal') {
            $stripe_ok = $this->isStripeReady();
            $paypal_ok = $this->isPayPalReady();
            if ($stripe_ok && $paypal_ok) {
                return 'stripe_and_paypal';
            }
            if ($stripe_ok) {
                return 'stripe';
            }
            if ($paypal_ok) {
                return 'paypal';
            }
        }
        if ($provider === 'stripe' && $this->isStripeReady()) {
            return 'stripe';
        }
        if ($provider === 'paypal' && $this->isPayPalReady()) {
            return 'paypal';
        }
        return 'link';
    }

    private function isStripeReady(): bool
    {
        $svc = Plugin::instance()->get(StripeCheckout::class);
        return $svc instanceof StripeCheckout && $svc->isConfigured();
    }

    private function isPayPalReady(): bool
    {
        $svc = Plugin::instance()->get(PayPalCheckout::class);
        return $svc instanceof PayPalCheckout && $svc->isConfigured();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderCta(array $data, string $provider, ?string $uuid): string
    {
        $cta_label = (string) ($data['cta_label'] ?? 'Buy now');

        if ($provider === 'link') {
            return sprintf(
                '<a class="bio-block__product-cta" href="%s" target="_blank" rel="noopener">%s</a>',
                esc_url((string) $data['cta_url']),
                esc_html($cta_label)
            );
        }

        if ($provider === 'stripe_and_paypal') {
            return $this->renderCheckoutForm($data, 'stripe', $uuid, $cta_label, 'primary')
                 . $this->renderCheckoutForm($data, 'paypal', $uuid, __('or pay with PayPal', 'biolink-pro'), 'secondary');
        }

        return $this->renderCheckoutForm($data, $provider, $uuid, $cta_label, 'primary');
    }

    /**
     * @param array<string, mixed> $data
     * @param 'stripe'|'paypal'    $provider
     */
    private function renderCheckoutForm(array $data, string $provider, ?string $uuid, string $cta_label, string $style): string
    {
        $currency = strtoupper((string) ($data['currency'] ?? 'USD'));
        $name     = (string) ($data['name'] ?? 'Product');
        $amount   = is_numeric($data['price_value'] ?? null) ? (float) $data['price_value'] : 0.0;
        $page_id  = (int) (get_the_ID() ?: 0);
        $endpoint = $provider === 'stripe' ? '/biolink/v1/stripe/checkout' : '/biolink/v1/paypal/checkout';

        $cta_class = $style === 'secondary' ? 'bio-block__product-cta bio-block__product-cta--secondary' : 'bio-block__product-cta';

        return sprintf(
            '<form class="bio-block__product-form bio-block__product-form--%1$s" data-action="checkout" data-provider="%2$s" data-endpoint="%3$s" data-page="%4$d" data-block="%5$s" data-currency="%6$s" data-name="%7$s">' .
                '<input type="hidden" name="amount" value="%8$s">' .
                '<button type="submit" class="%9$s">%10$s</button>' .
                '<div class="bio-block__donation-status" aria-live="polite"></div>' .
            '</form>',
            esc_attr($style),
            esc_attr($provider),
            esc_attr(rest_url($endpoint)),
            $page_id,
            esc_attr((string) $uuid),
            esc_attr($currency),
            esc_attr($name),
            esc_attr((string) $amount),
            esc_attr($cta_class),
            esc_html($cta_label)
        );
    }
}
