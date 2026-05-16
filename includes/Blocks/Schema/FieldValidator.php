<?php
/**
 * Tiny per-block field validator/sanitizer driven by `AbstractBlock::schema()`.
 *
 * @package BioLinkPro\Blocks\Schema
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks\Schema;

defined('ABSPATH') || exit;

/**
 * Walks a block's schema, sanitizes inputs against the declared type,
 * drops unknown keys, and fills in defaults.
 *
 * Supported types: `string`, `text`, `url`, `email`, `color`, `int`, `bool`, `enum`, `array`.
 */
final class FieldValidator
{
    /**
     * @param array<string, array{type: string, required?: bool, max?: int, enum?: list<string>, default?: mixed, items?: array<string, mixed>}> $schema
     * @param array<string, mixed>                                                                                                                $input
     * @return array<string, mixed>
     */
    public static function validate(array $schema, array $input): array
    {
        $out = [];
        foreach ($schema as $key => $rules) {
            $type     = $rules['type'] ?? 'string';
            $required = (bool) ($rules['required'] ?? false);
            $default  = $rules['default'] ?? null;
            $value    = $input[$key] ?? null;

            $sanitized = self::sanitize($value, $type, $rules);
            if ($sanitized === null) {
                if ($default !== null) {
                    $out[$key] = $default;
                } elseif (! $required) {
                    continue;
                } else {
                    // Required field with no value and no default — emit a typed empty value.
                    $out[$key] = self::emptyFor($type);
                }
                continue;
            }
            $out[$key] = $sanitized;
        }
        return $out;
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $rules
     * @return mixed
     */
    private static function sanitize($value, string $type, array $rules)
    {
        switch ($type) {
            case 'string':
                if (! is_scalar($value)) {
                    return null;
                }
                $s   = sanitize_text_field((string) $value);
                $max = isset($rules['max']) ? (int) $rules['max'] : 0;
                return $max > 0 ? mb_substr($s, 0, $max) : $s;

            case 'text':
                if (! is_scalar($value)) {
                    return null;
                }
                // Multi-line plain text — strip tags but preserve newlines.
                $t = wp_check_invalid_utf8((string) $value);
                $t = wp_strip_all_tags($t);
                return trim($t) === '' ? null : $t;

            case 'url':
                if (! is_string($value) || $value === '') {
                    return null;
                }
                $u = esc_url_raw($value);
                return $u !== '' ? $u : null;

            case 'email':
                $e = is_string($value) ? sanitize_email($value) : '';
                return $e !== '' && is_email($e) ? $e : null;

            case 'color':
                if (! is_string($value)) {
                    return null;
                }
                return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : null;

            case 'int':
                if (! is_numeric($value)) {
                    return null;
                }
                return (int) $value;

            case 'bool':
                if ($value === null) {
                    return null;
                }
                return rest_sanitize_boolean($value);

            case 'enum':
                $allowed = $rules['enum'] ?? [];
                if (! is_array($allowed) || ! is_string($value)) {
                    return null;
                }
                $v = sanitize_key($value);
                return in_array($v, $allowed, true) ? $v : null;

            case 'array':
                if (! is_array($value)) {
                    return null;
                }
                $items    = $rules['items'] ?? null;
                $sanitized = [];
                foreach ($value as $entry) {
                    if (is_array($entry) && is_array($items)) {
                        $sanitized[] = self::validate($items, $entry);
                    } elseif (is_scalar($entry)) {
                        $sanitized[] = sanitize_text_field((string) $entry);
                    }
                }
                return $sanitized;

            default:
                return null;
        }
    }

    /**
     * @return mixed
     */
    private static function emptyFor(string $type)
    {
        return match ($type) {
            'int'   => 0,
            'bool'  => false,
            'array' => [],
            default => '',
        };
    }
}
