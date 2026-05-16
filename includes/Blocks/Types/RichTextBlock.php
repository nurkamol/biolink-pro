<?php
/**
 * "Rich text" block — markdown source, server-rendered via the same MarkdownRenderer
 * the changelog uses. Keeps the admin JS bundle small (no WYSIWYG dep).
 *
 * @package BioLinkPro\Blocks\Types
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Types;

use BioLinkPro\Blocks\AbstractBlock;
use BioLinkPro\Blocks\Schema\FieldValidator;
use BioLinkPro\Updates\MarkdownRenderer;

defined('ABSPATH') || exit;

final class RichTextBlock extends AbstractBlock
{
    public function slug(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return __('Rich Text', 'biolink-pro');
    }

    public function icon(): string
    {
        return 'star';
    }

    public function schema(): array
    {
        return [
            'markdown' => ['type' => 'text', 'required' => true],
            'align'    => ['type' => 'enum', 'enum' => ['left', 'center', 'right'], 'default' => 'left'],
        ];
    }

    public function render(array $data): string
    {
        $data     = FieldValidator::validate($this->schema(), $data);
        $markdown = (string) ($data['markdown'] ?? '');
        if (trim($markdown) === '') {
            return '';
        }

        $align = $data['align'] ?? 'left';
        $body  = MarkdownRenderer::render($markdown);

        return sprintf(
            '<div class="bio-block bio-block--text bio-block--text-%s">%s</div>',
            esc_attr((string) $align),
            $body
        );
    }
}
