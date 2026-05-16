<?php
/**
 * "Newsletter" block — email subscribe form.
 *
 * v0.4: collects subscribers into the `biolink_newsletter_list` option +
 * optionally emails the site admin. Real provider integrations
 * (Mailchimp / MailerLite / Resend) ship in Phase 7.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class NewsletterBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'newsletter';
    }

    public function label(): string
    {
        return __('Newsletter', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function schema(): array
    {
        return [
            'heading'         => ['type' => 'string', 'max' => 200, 'default' => __('Subscribe', 'biolink-pro')],
            'description'    => ['type' => 'string', 'max' => 300, 'default' => ''],
            'placeholder'     => ['type' => 'string', 'max' => 80, 'default' => __('you@example.com', 'biolink-pro')],
            'button_text'     => ['type' => 'string', 'max' => 40, 'default' => __('Subscribe', 'biolink-pro')],
            'success_message' => ['type' => 'string', 'max' => 200, 'default' => __('Thanks! Check your inbox.', 'biolink-pro')],
        ];
    }

    public function render(array $data): string
    {
        $data    = FieldValidator::validate($this->schema(), $data);
        $page_id = (int) (get_the_ID() ?: 0);
        $nonce   = wp_create_nonce('biolink_newsletter_' . $page_id);

        return sprintf(
            '<form class="bio-block bio-block--newsletter" data-action="newsletter" data-page="%1$d" data-nonce="%2$s" data-success="%3$s"><div class="bio-block__nl-body"><h3 class="bio-block__nl-heading">%4$s</h3>%5$s<div class="bio-block__nl-row"><input type="email" name="email" class="bio-block__nl-input" placeholder="%6$s" required autocomplete="email"><button type="submit" class="bio-block__nl-button">%7$s</button></div><div class="bio-block__nl-status" aria-live="polite"></div></div></form>',
            $page_id,
            esc_attr($nonce),
            esc_attr((string) $data['success_message']),
            esc_html((string) $data['heading']),
            ! empty($data['description'])
                ? '<p class="bio-block__nl-desc">' . esc_html((string) $data['description']) . '</p>'
                : '',
            esc_attr((string) $data['placeholder']),
            esc_html((string) $data['button_text'])
        );
    }
}
