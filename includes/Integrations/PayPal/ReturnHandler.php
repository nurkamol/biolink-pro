<?php
/**
 * Captures PayPal orders when the visitor returns from the approval flow.
 *
 * PayPal redirects to `<bio_page_url>?biolink_paypal=return&token={order_id}&PayerID={…}`
 * once the user has approved. We hook `template_redirect` on bio pages,
 * capture the order, then redirect to a clean URL with `?biolink_payment=success|failed`
 * so the URL bar doesn't leak PayPal's params.
 *
 * @package BioLinkPro\Integrations\PayPal
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\PayPal;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;

defined('ABSPATH') || exit;

final class ReturnHandler implements Bootable
{
    public function __construct(private readonly Checkout $checkout)
    {
    }

    public function boot(): void
    {
        add_action('template_redirect', [$this, 'maybeCapture']);
    }

    public function maybeCapture(): void
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- this is a payment-provider return URL
        $action = isset($_GET['biolink_paypal']) ? sanitize_text_field(wp_unslash((string) $_GET['biolink_paypal'])) : '';
        $token  = isset($_GET['token']) ? sanitize_text_field(wp_unslash((string) $_GET['token'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($action === 'cancel') {
            $this->cleanRedirect('cancel');
            return;
        }
        if ($action !== 'return' || $token === '') {
            return;
        }

        $entry  = $this->checkout->captureAndLog($token);
        $status = ($entry !== null && ($entry['status'] ?? '') === 'COMPLETED') ? 'success' : 'failed';
        $this->cleanRedirect($status);
    }

    private function cleanRedirect(string $status): void
    {
        $clean = remove_query_arg(['biolink_paypal', 'token', 'PayerID']);
        $clean = add_query_arg(['biolink_payment' => $status], $clean);
        nocache_headers();
        wp_safe_redirect($clean);
        exit;
    }
}
