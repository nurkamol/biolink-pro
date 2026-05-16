<?php
/**
 * "Contact form" block — name/email/message form that emails site admin.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class ContactFormBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'contact_form';
    }

    public function label(): string
    {
        return __('Contact Form', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'mail';
    }

    public function schema(): array
    {
        return [
            'heading'         => ['type' => 'string', 'max' => 200, 'default' => __('Get in touch', 'biolink-pro')],
            'description'     => ['type' => 'string', 'max' => 300, 'default' => ''],
            'button_text'     => ['type' => 'string', 'max' => 40, 'default' => __('Send message', 'biolink-pro')],
            'success_message' => ['type' => 'string', 'max' => 200, 'default' => __('Thanks! I\'ll get back to you soon.', 'biolink-pro')],
        ];
    }

    public function render(array $data): string
    {
        $data    = FieldValidator::validate($this->schema(), $data);
        $page_id = (int) (get_the_ID() ?: 0);
        $nonce   = wp_create_nonce('biolink_contact_' . $page_id);

        return sprintf(
            '<form class="bio-block bio-block--contact" data-action="contact" data-page="%1$d" data-nonce="%2$s" data-success="%3$s"><h3 class="bio-block__contact-heading">%4$s</h3>%5$s<input type="text" name="name" class="bio-block__contact-input" placeholder="%6$s" required autocomplete="name"><input type="email" name="email" class="bio-block__contact-input" placeholder="%7$s" required autocomplete="email"><textarea name="message" class="bio-block__contact-textarea" rows="4" placeholder="%8$s" required></textarea><input type="text" name="biolink_hp" class="bio-block__contact-hp" tabindex="-1" autocomplete="off" aria-hidden="true"><button type="submit" class="bio-block__contact-button">%9$s</button><div class="bio-block__contact-status" aria-live="polite"></div></form>',
            $page_id,
            esc_attr($nonce),
            esc_attr((string) $data['success_message']),
            esc_html((string) $data['heading']),
            ! empty($data['description'])
                ? '<p class="bio-block__contact-desc">' . esc_html((string) $data['description']) . '</p>'
                : '',
            esc_attr__('Your name', 'biolink-pro'),
            esc_attr__('you@example.com', 'biolink-pro'),
            esc_attr__('Message', 'biolink-pro'),
            esc_html((string) $data['button_text'])
        );
    }
}
