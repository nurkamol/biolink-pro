<?php
/**
 * Lightweight server-side OpenGraph card generator.
 *
 * Renders a 1200x630 PNG per bio page using PHP GD. No external font dependency
 * — uses GD's built-in pixel font for portability across shared hosts. Cards
 * look basic but functional; a "Brand Fonts" upgrade with bundled Inter is on
 * the v2.6+ roadmap.
 *
 * @package BioLinkPro\Seo
 */

declare(strict_types=1);

namespace BioLinkPro\Seo;

defined('ABSPATH') || exit;

final class OgImageGenerator
{
    private const W = 1200;
    private const H = 630;

    /**
     * Return a public URL for the OG card for this page, generating + caching
     * the PNG on first request. Returns null if GD isn't available.
     *
     * @param array<string, mixed> $settings
     */
    public function urlFor(int $page_id, array $settings, string $theme): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $headline    = trim((string) ($settings['headline'] ?? get_the_title($page_id)));
        $subheadline = trim((string) ($settings['subheadline'] ?? ''));
        $handle      = trim((string) ($settings['handle'] ?? ''));
        $avatar_id   = (int) ($settings['avatar_id'] ?? 0);

        $cache_key = md5(
            $page_id . '|' . $theme . '|' . $headline . '|' . $subheadline . '|' . $handle . '|' . $avatar_id
        );

        $upload  = wp_upload_dir();
        $dir_abs = trailingslashit($upload['basedir']) . 'biolink-pro/og';
        $dir_url = trailingslashit($upload['baseurl']) . 'biolink-pro/og';
        if (! wp_mkdir_p($dir_abs)) {
            return null;
        }

        $filename = "{$page_id}-{$cache_key}.png";
        $abs_path = $dir_abs . '/' . $filename;
        $url      = $dir_url . '/' . $filename;

        if (file_exists($abs_path)) {
            return $url;
        }

        // Old cards for this page can stay — they'll be pruned on next garbage
        // sweep — but no need to keep accumulating. Best-effort cleanup of
        // siblings for this page.
        $stale = glob($dir_abs . "/{$page_id}-*.png") ?: [];
        foreach ($stale as $old) {
            if ($old !== $abs_path) {
                @unlink($old); // phpcs:ignore
            }
        }

        $colors = self::themeColors($theme);
        $img    = $this->render($colors, $headline, $subheadline, $handle, $avatar_id);
        if ($img === null) {
            return null;
        }

        $ok = imagepng($img, $abs_path, 6);
        imagedestroy($img);

        return $ok ? $url : null;
    }

    /**
     * @param array{bg:int[], fg:int[], muted:int[], accent:int[]} $colors
     */
    private function render(array $colors, string $headline, string $subheadline, string $handle, int $avatar_id): ?\GdImage
    {
        $img = imagecreatetruecolor(self::W, self::H);
        if (! $img instanceof \GdImage) {
            return null;
        }

        // Gradient-ish background — two horizontal bands.
        $bg_top    = imagecolorallocate($img, $colors['bg'][0], $colors['bg'][1], $colors['bg'][2]);
        $bg_bottom = imagecolorallocate(
            $img,
            max(0, $colors['bg'][0] - 20),
            max(0, $colors['bg'][1] - 20),
            max(0, $colors['bg'][2] - 20)
        );
        $accent = imagecolorallocate($img, $colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
        $fg     = imagecolorallocate($img, $colors['fg'][0], $colors['fg'][1], $colors['fg'][2]);
        $muted  = imagecolorallocate($img, $colors['muted'][0], $colors['muted'][1], $colors['muted'][2]);

        imagefilledrectangle($img, 0, 0, self::W, self::H, $bg_top);
        imagefilledrectangle($img, 0, intval(self::H * 0.55), self::W, self::H, $bg_bottom);

        // Accent stripe along the top.
        imagefilledrectangle($img, 0, 0, self::W, 8, $accent);

        // Avatar circle (if attachment exists).
        $avatar_x = 100;
        $avatar_y = intval(self::H / 2) - 80;
        if ($avatar_id > 0) {
            $this->drawAvatar($img, $avatar_id, $avatar_x, $avatar_y, 160);
        } else {
            // Stub circle with initials.
            $circle = imagecolorallocate($img, $colors['accent'][0], $colors['accent'][1], $colors['accent'][2]);
            imagefilledellipse($img, $avatar_x + 80, $avatar_y + 80, 160, 160, $circle);
        }

        // Text block — to the right of the avatar.
        $text_x = $avatar_x + 200;
        $text_y = $avatar_y + 10;

        // GD has 5 built-in fonts (1-5). Font 5 is the largest at ~9x15px,
        // but `imagestring` doesn't scale. For larger text we tile the chars
        // using imagestringup-like manual placement. Pragmatic v2.5 path:
        // accept the small built-in font; v2.6+ will swap to imagettftext
        // with a bundled Inter font for proper display sizes.
        $title = self::truncate($headline ?: __('Untitled', 'biolink-pro'), 40);
        imagestring($img, 5, $text_x, $text_y, $title, $fg);
        $text_y += 60;
        if ($handle !== '') {
            imagestring($img, 4, $text_x, $text_y, '@' . ltrim($handle, '@'), $accent);
            $text_y += 40;
        }
        if ($subheadline !== '') {
            $sub = self::truncate($subheadline, 80);
            imagestring($img, 3, $text_x, $text_y, $sub, $muted);
        }

        // Footer mark.
        imagestring($img, 2, 100, self::H - 50, 'made with BioLink Pro', $muted);

        return $img;
    }

    private function drawAvatar(\GdImage $canvas, int $attachment_id, int $x, int $y, int $size): void
    {
        $path = get_attached_file($attachment_id);
        if (! $path || ! file_exists($path)) {
            return;
        }
        $info = @getimagesize($path);
        if ($info === false) {
            return;
        }
        $src = match ($info[2]) {
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default        => false,
        };
        if (! $src instanceof \GdImage) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        // Square crop from center.
        $side = min($sw, $sh);
        $sx   = (int) (($sw - $side) / 2);
        $sy   = (int) (($sh - $side) / 2);

        imagecopyresampled($canvas, $src, $x, $y, $sx, $sy, $size, $size, $side, $side);
        imagedestroy($src);
    }

    /**
     * @return array{bg:int[], fg:int[], muted:int[], accent:int[]}
     */
    private static function themeColors(string $theme): array
    {
        $presets = [
            'mono'     => ['bg' => [248, 246, 240], 'fg' => [10, 10, 10],  'muted' => [110, 110, 110], 'accent' => [103, 42, 192]],
            'glass'    => ['bg' => [230, 240, 245], 'fg' => [40, 40, 60],  'muted' => [120, 130, 145], 'accent' => [40, 110, 220]],
            'forest'   => ['bg' => [240, 245, 232], 'fg' => [30, 60, 30],  'muted' => [100, 130, 100], 'accent' => [50, 130, 70]],
            'midnight' => ['bg' => [15, 15, 25],    'fg' => [240, 240, 245], 'muted' => [150, 150, 170], 'accent' => [120, 150, 255]],
            'neon'     => ['bg' => [12, 12, 12],    'fg' => [255, 255, 255], 'muted' => [180, 180, 180], 'accent' => [255, 30, 180]],
            'sunset'   => ['bg' => [255, 200, 170], 'fg' => [80, 30, 30],   'muted' => [160, 100, 100], 'accent' => [255, 90, 80]],
            'aurora'   => ['bg' => [180, 200, 240], 'fg' => [40, 30, 80],   'muted' => [110, 120, 160], 'accent' => [130, 80, 220]],
            'sky'      => ['bg' => [220, 235, 250], 'fg' => [30, 60, 100],  'muted' => [110, 140, 180], 'accent' => [70, 130, 220]],
        ];
        return $presets[$theme] ?? $presets['mono'];
    }

    private static function truncate(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }
        return rtrim(substr($s, 0, $max - 1)) . '…';
    }
}
