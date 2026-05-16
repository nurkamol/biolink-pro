<?php
/**
 * @package BioLinkPro\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Blocks;

use BioLinkPro\Blocks\Schema\FieldValidator;
use PHPUnit\Framework\TestCase;

final class FieldValidatorTest extends TestCase
{
    public function testStringTrimAndMax(): void
    {
        $out = FieldValidator::validate(
            ['label' => ['type' => 'string', 'max' => 5]],
            ['label' => 'hello world']
        );
        self::assertSame('hello', $out['label']);
    }

    public function testUrlSanitization(): void
    {
        $out = FieldValidator::validate(
            ['url' => ['type' => 'url']],
            ['url' => 'https://example.test/path?q=1']
        );
        self::assertStringStartsWith('https://example.test', $out['url']);
    }

    public function testUrlRejectsEmpty(): void
    {
        $out = FieldValidator::validate(
            ['url' => ['type' => 'url']],
            ['url' => '']
        );
        self::assertArrayNotHasKey('url', $out);
    }

    public function testEnumAcceptsKnown(): void
    {
        $out = FieldValidator::validate(
            ['size' => ['type' => 'enum', 'enum' => ['sm', 'md', 'lg'], 'default' => 'md']],
            ['size' => 'lg']
        );
        self::assertSame('lg', $out['size']);
    }

    public function testEnumRejectsUnknown(): void
    {
        $out = FieldValidator::validate(
            ['size' => ['type' => 'enum', 'enum' => ['sm', 'md', 'lg'], 'default' => 'md']],
            ['size' => 'huge']
        );
        self::assertSame('md', $out['size']);
    }

    public function testColorHexValidation(): void
    {
        $out = FieldValidator::validate(
            ['c' => ['type' => 'color']],
            ['c' => '#FF00aa']
        );
        self::assertSame('#ff00aa', $out['c']);

        $out = FieldValidator::validate(
            ['c' => ['type' => 'color']],
            ['c' => 'not-a-color']
        );
        self::assertArrayNotHasKey('c', $out);
    }

    public function testIntCast(): void
    {
        $out = FieldValidator::validate(
            ['n' => ['type' => 'int']],
            ['n' => '42']
        );
        self::assertSame(42, $out['n']);
    }

    public function testBoolCast(): void
    {
        $cases = [
            ['1', true],
            [0, false],
            ['true', true],
            ['false', false],
        ];
        foreach ($cases as [$in, $expected]) {
            $out = FieldValidator::validate(
                ['flag' => ['type' => 'bool']],
                ['flag' => $in]
            );
            self::assertSame($expected, $out['flag'], "input: " . var_export($in, true));
        }
    }

    public function testRequiredEmptyGetsEmptyValue(): void
    {
        $out = FieldValidator::validate(
            ['label' => ['type' => 'string', 'required' => true]],
            []
        );
        self::assertSame('', $out['label']);
    }

    public function testOptionalEmptyIsDropped(): void
    {
        $out = FieldValidator::validate(
            ['label' => ['type' => 'string']],
            []
        );
        self::assertArrayNotHasKey('label', $out);
    }

    public function testDefaultUsedWhenInputMissing(): void
    {
        $out = FieldValidator::validate(
            ['size' => ['type' => 'enum', 'enum' => ['sm', 'md', 'lg'], 'default' => 'md']],
            []
        );
        self::assertSame('md', $out['size']);
    }

    public function testArrayOfScalarsSanitized(): void
    {
        $out = FieldValidator::validate(
            ['ids' => ['type' => 'array']],
            ['ids' => [1, 'two', 3.5]]
        );
        self::assertCount(3, $out['ids']);
    }

    public function testArrayOfObjects(): void
    {
        $out = FieldValidator::validate(
            [
                'items' => [
                    'type'  => 'array',
                    'items' => [
                        'name'  => ['type' => 'string'],
                        'url'   => ['type' => 'url'],
                    ],
                ],
            ],
            ['items' => [
                ['name' => 'GitHub', 'url' => 'https://github.com/'],
                ['name' => 'Twitter'], // missing url is dropped silently
            ]]
        );
        self::assertCount(2, $out['items']);
        self::assertSame('GitHub', $out['items'][0]['name']);
        self::assertStringStartsWith('https://github.com', $out['items'][0]['url']);
    }

    public function testUnknownKeysDropped(): void
    {
        $out = FieldValidator::validate(
            ['known' => ['type' => 'string']],
            ['known' => 'ok', 'extra' => 'should be removed']
        );
        self::assertArrayHasKey('known', $out);
        self::assertArrayNotHasKey('extra', $out);
    }
}
