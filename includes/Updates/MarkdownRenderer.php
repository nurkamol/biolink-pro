<?php
/**
 * Tiny markdown → safe HTML converter for release notes.
 *
 * @package BioLinkPro\Updates
 */

declare(strict_types=1);

namespace BioLinkPro\Updates;

defined('ABSPATH') || exit;

/**
 * Intentionally minimal — handles only what GitHub release notes typically contain:
 * `#`–`####` headings, `-`/`*` lists, paragraphs, inline `code`, fenced code blocks,
 * `[label](url)` links, bold/italic. Output is run through `wp_kses_post` so it's
 * always safe to echo into admin pages.
 */
final class MarkdownRenderer
{
    public static function render(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $markdown) ?: [];
        $html  = '';
        $i     = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            // Fenced code block ```
            if (preg_match('/^```/', $line)) {
                $code = [];
                $i++;
                while ($i < $count && ! preg_match('/^```/', $lines[$i])) {
                    $code[] = $lines[$i];
                    $i++;
                }
                $html .= '<pre><code>' . esc_html(implode("\n", $code)) . "</code></pre>\n";
                $i++;
                continue;
            }

            // ATX headings
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $level = max(2, min(4, strlen($m[1]) + 1));
                $html .= '<h' . $level . '>' . self::inline($m[2]) . '</h' . $level . '>' . "\n";
                $i++;
                continue;
            }

            // Unordered list (consecutive `- ` or `* ` lines)
            if (preg_match('/^[\-\*]\s+(.*)$/', $line)) {
                $html .= "<ul>\n";
                while ($i < $count && preg_match('/^[\-\*]\s+(.*)$/', $lines[$i], $m)) {
                    $html .= "\t<li>" . self::inline($m[1]) . "</li>\n";
                    $i++;
                }
                $html .= "</ul>\n";
                continue;
            }

            // Blank line separator
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraph — join consecutive non-blank, non-special lines
            $para = [];
            while (
                $i < $count
                && trim($lines[$i]) !== ''
                && ! preg_match('/^(#{1,4}\s|[\-\*]\s|```)/', $lines[$i])
            ) {
                $para[] = $lines[$i];
                $i++;
            }
            if ($para !== []) {
                $html .= '<p>' . self::inline(implode(' ', $para)) . "</p>\n";
            }
        }

        return wp_kses_post($html);
    }

    private static function inline(string $text): string
    {
        $text = esc_html($text);

        // Inline code `…`
        $text = preg_replace_callback(
            '/`([^`]+)`/',
            static fn(array $m): string => '<code>' . $m[1] . '</code>',
            $text
        ) ?? $text;

        // Links [label](url)
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m): string {
                $url = esc_url($m[2]);
                if ($url === '') {
                    return esc_html($m[1]);
                }
                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
            },
            $text
        ) ?? $text;

        // Bold **…**
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        // Italic *…*  (after bold so the inner content isn't double-matched)
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text) ?? $text;

        return $text;
    }
}
