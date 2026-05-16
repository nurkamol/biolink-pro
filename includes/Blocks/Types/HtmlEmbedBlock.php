<?php
/**
 * "HTML embed" block — raw HTML, capability-gated.
 *
 * Only users with `unfiltered_html` can save raw HTML; for others
 * the field is run through wp_kses_post. Never accepts <script>/<iframe>
 * unless the saver has `unfiltered_html`.
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;

defined('ABSPATH') || exit;

final class HtmlEmbedBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'html_embed';
    }

    public function label(): string
    {
        return __('HTML Embed', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function schema(): array
    {
        return [
            'html' => ['type' => 'text', 'required' => true],
        ];
    }

    public function render(array $data): string
    {
        $data = FieldValidator::validate($this->schema(), $data);
        $html = (string) ($data['html'] ?? '');
        if (trim($html) === '') {
            return '';
        }

        $author_id = (int) get_post_field('post_author', get_the_ID() ?: 0);
        $allow_raw = $author_id > 0 && user_can($author_id, 'unfiltered_html');

        $safe = $allow_raw ? $html : wp_kses_post($html);

        return '<div class="bio-block bio-block--html-embed">' . $safe . '</div>';
    }
}
