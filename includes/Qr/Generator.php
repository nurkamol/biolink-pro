<?php
/**
 * QR code generator using endroid/qr-code with on-disk cache.
 *
 * @package BioLinkPro\Qr
 */

declare(strict_types=1);

namespace BioLinkPro\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

defined('ABSPATH') || exit;

final class Generator
{
    /**
     * @param array{fg?:string, bg?:string, size?:int, format?:string, label?:string} $options
     * @return array{path:string, url:string, mime:string}|null
     */
    public function generate(int $page_id, string $url, array $options = []): ?array
    {
        $fg     = $this->hexToRgb((string) ($options['fg'] ?? '#000000'));
        $bg     = $this->hexToRgb((string) ($options['bg'] ?? '#FFFFFF'));
        $size   = max(64, min(2048, (int) ($options['size'] ?? 512)));
        $format = ($options['format'] ?? 'png') === 'svg' ? 'svg' : 'png';

        $style_hash = sha1(wp_json_encode([$url, $fg, $bg, $size, $format, $options['label'] ?? '']) ?: '');

        $uploads = wp_upload_dir(null, true);
        if (! empty($uploads['error'])) {
            return null;
        }
        $dir = trailingslashit($uploads['basedir']) . 'biolink-pro/qr';
        $url_base = trailingslashit($uploads['baseurl']) . 'biolink-pro/qr';
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        $filename = sprintf('%d-%s.%s', $page_id, substr($style_hash, 0, 12), $format);
        $path     = $dir . '/' . $filename;

        if (! file_exists($path)) {
            $writer  = $format === 'svg' ? new SvgWriter() : new PngWriter();
            $result  = ( new Builder() )
                ->writer($writer)
                ->data($url)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size($size)
                ->margin(12)
                ->foregroundColor(new Color($fg[0], $fg[1], $fg[2]))
                ->backgroundColor(new Color($bg[0], $bg[1], $bg[2]))
                ->build();
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($path, $result->getString());
        }

        $this->persistMeta($page_id, $style_hash, $fg, $bg, $format, $path);

        return [
            'path' => $path,
            'url'  => $url_base . '/' . $filename,
            'mime' => $format === 'svg' ? 'image/svg+xml' : 'image/png',
        ];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return [0, 0, 0];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @param array{0:int,1:int,2:int} $fg
     * @param array{0:int,1:int,2:int} $bg
     */
    private function persistMeta(int $page_id, string $style_hash, array $fg, array $bg, string $format, string $file_path): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_qr';
        $fg_hex = sprintf('#%02X%02X%02X', $fg[0], $fg[1], $fg[2]);
        $bg_hex = sprintf('#%02X%02X%02X', $bg[0], $bg[1], $bg[2]);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE page_id = %d AND style_hash = %s LIMIT 1",
            $page_id,
            $style_hash
        ));
        if ($existing > 0) {
            return;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert($table, [
            'page_id'    => $page_id,
            'style_hash' => $style_hash,
            'fg_color'   => $fg_hex,
            'bg_color'   => $bg_hex,
            'format'     => $format,
            'file_path'  => str_replace(ABSPATH, '', $file_path),
        ]);
    }
}
