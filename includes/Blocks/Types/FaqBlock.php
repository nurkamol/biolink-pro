<?php
/**
 * "FAQ" block — collapsible question/answer accordion with JSON-LD FAQPage schema.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class FaqBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return __('FAQ', 'biolink-pro');
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
                    'question' => ['type' => 'string', 'required' => true, 'max' => 200],
                    'answer'   => ['type' => 'text', 'required' => true],
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

        $html = '<div class="bio-block bio-block--faq">';
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            $html .= sprintf(
                '<details class="bio-block__faq-item"><summary>%s</summary><div class="bio-block__faq-answer">%s</div></details>',
                esc_html((string) $item['question']),
                wp_kses_post(nl2br(esc_html((string) $item['answer'])))
            );
        }
        $html .= '</div>';

        // JSON-LD FAQPage structured data
        $ld = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            $ld[] = [
                '@type'          => 'Question',
                'name'           => (string) $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => (string) $item['answer'],
                ],
            ];
        }
        if ($ld !== []) {
            $schema = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $ld,
            ];
            $html .= '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
        }
        return $html;
    }
}
