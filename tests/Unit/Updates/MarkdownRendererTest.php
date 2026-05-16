<?php
/**
 * @package BioLinkPro\Tests\Unit\Updates
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Updates;

use BioLinkPro\Updates\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class MarkdownRendererTest extends TestCase
{
    public function testEmptyInputReturnsEmpty(): void
    {
        self::assertSame('', MarkdownRenderer::render(''));
    }

    public function testSingleHeading(): void
    {
        $html = MarkdownRenderer::render("# Heading");
        self::assertStringContainsString('<h2>Heading</h2>', $html);
    }

    public function testHeadingLevelsCapAtH4(): void
    {
        $html = MarkdownRenderer::render("# h1\n\n## h2\n\n### h3\n\n#### h4\n\n##### deep");
        self::assertStringContainsString('<h2>h1</h2>', $html);
        self::assertStringContainsString('<h3>h2</h3>', $html);
        self::assertStringContainsString('<h4>h3</h4>', $html);
        self::assertStringContainsString('<h4>h4</h4>', $html);
    }

    public function testUnorderedList(): void
    {
        $html = MarkdownRenderer::render("- one\n- two\n- three");
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>one</li>', $html);
        self::assertStringContainsString('<li>two</li>', $html);
        self::assertStringContainsString('<li>three</li>', $html);
        self::assertStringContainsString('</ul>', $html);
    }

    public function testParagraphJoinsLines(): void
    {
        $html = MarkdownRenderer::render("first line\nsecond line");
        self::assertSame('<p>first line second line</p>', trim($html));
    }

    public function testBoldAndItalic(): void
    {
        $html = MarkdownRenderer::render("Hello **world** and *italic*");
        self::assertStringContainsString('<strong>world</strong>', $html);
        self::assertStringContainsString('<em>italic</em>', $html);
    }

    public function testInlineCode(): void
    {
        $html = MarkdownRenderer::render('Use `npm install` first');
        self::assertStringContainsString('<code>npm install</code>', $html);
    }

    public function testLink(): void
    {
        $html = MarkdownRenderer::render('See [docs](https://example.test/docs) for more');
        self::assertStringContainsString('href="https://example.test/docs"', $html);
        self::assertStringContainsString('>docs</a>', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testFencedCodeBlock(): void
    {
        $html = MarkdownRenderer::render("```\nphp artisan migrate\n```");
        self::assertStringContainsString('<pre><code>php artisan migrate</code></pre>', $html);
    }

    public function testEscapesRawHtml(): void
    {
        $html = MarkdownRenderer::render('Hello <script>alert(1)</script>');
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }
}
