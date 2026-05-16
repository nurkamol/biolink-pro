<?php
/**
 * Passcode-gated link unlocker.
 *
 * Locked links route through `<bio_page_url>?biolink_unlock={uuid}`. We hook
 * `template_redirect`, render a standalone passcode form, verify the POST,
 * and 302 to the underlying URL on success.
 *
 * @package BioLinkPro\Frontend
 */

declare(strict_types=1);

namespace BioLinkPro\Frontend;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class UnlockHandler implements Bootable
{
    public function __construct(private readonly PageRepository $repository)
    {
    }

    public function boot(): void
    {
        add_action('template_redirect', [$this, 'maybeUnlock']);
    }

    public function maybeUnlock(): void
    {
        if (! is_singular(BioLinkPagePostType::POST_TYPE)) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $uuid = isset($_GET['biolink_unlock'])
            ? sanitize_text_field(wp_unslash((string) $_GET['biolink_unlock']))
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($uuid === '') {
            return;
        }

        $post = get_queried_object();
        if (! $post instanceof WP_Post) {
            return;
        }

        $bundle = $this->repository->findById($post->ID);
        if ($bundle === null) {
            return;
        }

        $block = $this->findBlock((array) ($bundle['data']['blocks'] ?? []), $uuid);
        if ($block === null) {
            $this->fail(__('Link not found.', 'biolink-pro'));
            return;
        }

        $data        = is_array($block['data'] ?? null) ? $block['data'] : [];
        $hash        = isset($data['_passcode_hash']) ? (string) $data['_passcode_hash'] : '';
        $destination = isset($data['url']) ? (string) $data['url'] : '';

        if ($hash === '' || $destination === '') {
            // Not actually locked, or no destination. Bounce back to the page.
            wp_safe_redirect(get_permalink($post));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submitted = isset($_POST['passcode'])
                ? sanitize_text_field(wp_unslash((string) $_POST['passcode']))
                : '';
            if ($submitted !== '' && wp_check_password($submitted, $hash, 0)) {
                /**
                 * Fires after a successful passcode unlock, just before the redirect.
                 *
                 * @param string $uuid  Block uuid that was unlocked.
                 * @param int    $page_id
                 */
                do_action('biolink/link/unlocked', $uuid, $post->ID);
                nocache_headers();
                wp_redirect(esc_url_raw($destination));
                exit;
            }
            $error = __('Incorrect passcode. Try again.', 'biolink-pro');
        }

        $this->renderForm(
            $uuid,
            (string) ($data['label'] ?? __('Locked link', 'biolink-pro')),
            $error,
            get_permalink($post)
        );
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return array<string, mixed>|null
     */
    private function findBlock(array $blocks, string $uuid): ?array
    {
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            if (($block['uuid'] ?? null) === $uuid) {
                return $block;
            }
        }
        return null;
    }

    private function fail(string $message): void
    {
        status_header(404);
        wp_die(esc_html($message), esc_html__('Link not found', 'biolink-pro'), ['response' => 404]);
    }

    private function renderForm(string $uuid, string $label, string $error, string $back): void
    {
        nocache_headers();
        status_header(200);

        $action = esc_url(
            add_query_arg(['biolink_unlock' => $uuid], get_permalink((int) get_queried_object_id()))
        );

        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex,nofollow" />
<title><?php echo esc_html(sprintf(/* translators: %s: link label */ __('Unlock %s', 'biolink-pro'), $label)); ?></title>
<style>
:root { color-scheme: light dark; }
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    display: grid;
    place-items: center;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f4f1ea;
    color: #0a0a0a;
    padding: 24px;
}
.card {
    width: 100%;
    max-width: 380px;
    background: #fff;
    border-radius: 22px;
    padding: 28px 24px;
    box-shadow: 0 20px 48px rgba(0,0,0,0.08);
    text-align: center;
}
.lock { font-size: 32px; margin-bottom: 6px; }
h1 { font-size: 18px; margin: 6px 0 4px; font-weight: 700; }
p { margin: 0 0 18px; color: #5b5b5b; font-size: 14px; line-height: 1.4; }
form { display: flex; flex-direction: column; gap: 10px; }
input[type="password"] {
    padding: 12px 14px;
    border: 1px solid #e6e2d6;
    border-radius: 12px;
    font: inherit;
    font-size: 15px;
    text-align: center;
    letter-spacing: 0.06em;
}
input[type="password"]:focus {
    outline: 2px solid #672ac0;
    outline-offset: 2px;
    border-color: transparent;
}
button {
    padding: 12px 14px;
    border: 0;
    background: #672ac0;
    color: #fff;
    border-radius: 999px;
    font: inherit;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
}
button:hover { background: #571eaa; }
.err {
    background: #fdecea;
    color: #c41e3a;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 10px;
}
.back {
    display: inline-block;
    margin-top: 16px;
    color: #5b5b5b;
    text-decoration: none;
    font-size: 13px;
}
.back:hover { color: #0a0a0a; }
@media (prefers-color-scheme: dark) {
    body { background: #1c1a16; color: #f4f1ea; }
    .card { background: #2a2823; box-shadow: 0 20px 48px rgba(0,0,0,0.4); }
    p, .back { color: #a8a8a8; }
    input[type="password"] { background: #1c1a16; border-color: #3a3833; color: inherit; }
}
</style>
</head>
<body>
<main class="card">
    <div class="lock" aria-hidden="true">🔒</div>
    <h1><?php echo esc_html($label); ?></h1>
    <p><?php esc_html_e('Enter the passcode to continue.', 'biolink-pro'); ?></p>

    <?php if ($error !== '') : ?>
        <div class="err"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo $action; // already escaped ?>">
        <input
            type="password"
            name="passcode"
            placeholder="<?php esc_attr_e('Passcode', 'biolink-pro'); ?>"
            autocomplete="off"
            autofocus
            required
            inputmode="text"
        />
        <button type="submit"><?php esc_html_e('Unlock', 'biolink-pro'); ?></button>
    </form>

    <a class="back" href="<?php echo esc_url($back); ?>">
        ← <?php esc_html_e('Back to page', 'biolink-pro'); ?>
    </a>
</main>
</body>
</html>
<?php
        exit;
    }
}
