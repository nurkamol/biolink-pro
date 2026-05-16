<?php
/**
 * Minimal bio page template.
 *
 * Stripped-down full-page HTML — no theme header/footer — so the bio page is
 * pure progressive HTML + tiny CSS. Theme integration is intentionally absent
 * to keep the LCP fast and the markup predictable across hosts.
 *
 * Themes can override by filtering `biolink/template/bio-page` and returning
 * a different absolute path.
 *
 * @package BioLinkPro\Templates
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use BioLinkPro\Core\Plugin;
use BioLinkPro\Frontend\PageRenderer;

/** @var \WP_Post|null $post */
$post     = get_post();
$plugin   = Plugin::instance();
$renderer = $plugin->get(PageRenderer::class);

if (! $post instanceof WP_Post || ! $renderer instanceof PageRenderer) {
    status_header(404);
    nocache_headers();
    return;
}

if ($post->post_status !== 'publish' && ! current_user_can('biolink_manage_pages')) {
    status_header(404);
    nocache_headers();
    return;
}

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="format-detection" content="telephone=no">
	<title><?php echo esc_html(wp_get_document_title()); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class('bio-body'); ?>>
	<?php
	// PageRenderer escapes per-block; the assembled HTML stream is intentionally raw.
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $renderer->render($post);
	?>
	<?php wp_footer(); ?>
</body>
</html>
<?php
