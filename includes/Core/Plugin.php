<?php
/**
 * Plugin orchestrator — singleton entry point that wires every module together.
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

use BioLinkPro\Admin\Assets as AdminAssets;
use BioLinkPro\Admin\Menu as AdminMenu;
use BioLinkPro\Admin\PluginActionLinks;
use BioLinkPro\Ai\AiController;
use BioLinkPro\Ai\OpenAiProvider;
use BioLinkPro\Ai\ProviderRegistry;
use BioLinkPro\Analytics\LinkSync;
use BioLinkPro\Analytics\Reporter;
use BioLinkPro\Analytics\Tracker;
use BioLinkPro\Api\AnalyticsController;
use BioLinkPro\Api\BlocksController;
use BioLinkPro\Api\ChangelogController;
use BioLinkPro\Api\CheckoutController;
use BioLinkPro\Api\ClickController;
use BioLinkPro\Api\FormsController;
use BioLinkPro\Api\PagesController;
use BioLinkPro\Api\PortabilityController;
use BioLinkPro\Api\QrController;
use BioLinkPro\Api\RestRouter;
use BioLinkPro\Api\SettingsController;
use BioLinkPro\Api\TemplatesController;
use BioLinkPro\Api\ThemesController;
use BioLinkPro\Api\TrackController;
use BioLinkPro\Api\UnlockController;
use BioLinkPro\Api\WebhookController;
use BioLinkPro\Cron\Pruner;
use BioLinkPro\Integrations\Email\MailchimpAdapter;
use BioLinkPro\Integrations\Email\MailerLiteAdapter;
use BioLinkPro\Integrations\Email\ResendAdapter;
use BioLinkPro\Integrations\PayPal\Checkout as PayPalCheckout;
use BioLinkPro\Integrations\PayPal\ReturnHandler as PayPalReturnHandler;
use BioLinkPro\Integrations\Stripe\Checkout as StripeCheckout;
use BioLinkPro\Integrations\Stripe\StripeWebhookListener;
use BioLinkPro\Blocks\BlockRegistry;
use BioLinkPro\Blocks\Types\ButtonBlock;
use BioLinkPro\Blocks\Types\ContactFormBlock;
use BioLinkPro\Blocks\Types\CountdownBlock;
use BioLinkPro\Blocks\Types\DividerBlock;
use BioLinkPro\Blocks\Types\DonationBlock;
use BioLinkPro\Blocks\Types\FaqBlock;
use BioLinkPro\Blocks\Types\HtmlEmbedBlock;
use BioLinkPro\Blocks\Types\ImageGalleryBlock;
use BioLinkPro\Blocks\Types\LinkBlock;
use BioLinkPro\Blocks\Types\MapBlock;
use BioLinkPro\Blocks\Types\NewsletterBlock;
use BioLinkPro\Blocks\Types\ProductCardBlock;
use BioLinkPro\Blocks\Types\RichTextBlock;
use BioLinkPro\Blocks\Types\SocialIconsBlock;
use BioLinkPro\Blocks\Types\SpotifyBlock;
use BioLinkPro\Blocks\Types\TiktokBlock;
use BioLinkPro\Blocks\Types\VideoBlock;
use BioLinkPro\Blocks\Types\YouTubeBlock;
use BioLinkPro\Database\Migrator;
use BioLinkPro\Frontend\Assets as FrontendAssets;
use BioLinkPro\Frontend\PageRenderer;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Frontend\Shortcodes;
use BioLinkPro\Frontend\TemplateLoader;
use BioLinkPro\Frontend\UnlockHandler;
use BioLinkPro\Qr\Generator as QrGenerator;
use BioLinkPro\Seo\MetaTags;
use BioLinkPro\Seo\Sitemap;
use BioLinkPro\Seo\StructuredData;
use BioLinkPro\Templates\TemplateLibrary;
use BioLinkPro\Themes\ThemeEngine;
use BioLinkPro\Updates\GitHubUpdater;

defined('ABSPATH') || exit;

/**
 * Lightweight service container + bootstrap surface.
 *
 * Modules register themselves via {@see self::register()}; {@see self::boot()}
 * runs each one in registration order on `plugins_loaded`.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    /**
     * @var array<string, object>
     */
    private array $services = [];

    /**
     * @var array<int, Bootable>
     */
    private array $bootables = [];

    private bool $booted = false;

    private function __construct()
    {
    }

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a service by identifier so other modules can resolve it later.
     *
     * @template T of object
     * @param class-string<T>|string $id
     * @param T                      $service
     */
    public function register(string $id, object $service): void
    {
        $this->services[$id] = $service;

        if ($service instanceof Bootable) {
            $this->bootables[] = $service;
        }
    }

    /**
     * Resolve a previously registered service.
     *
     * @template T of object
     * @param class-string<T>|string $id
     * @return T|null
     */
    public function get(string $id): ?object
    {
        /** @var T|null */
        return $this->services[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Wire and boot every module.
     *
     * Idempotent — boot is a no-op once it has completed.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        load_plugin_textdomain(
            'biolink-pro',
            false,
            dirname(BIOLINK_BASENAME) . '/languages'
        );

        $this->registerCoreServices();

        foreach ($this->bootables as $bootable) {
            $bootable->boot();
        }

        /**
         * Fires once BioLink Pro has finished booting all of its modules.
         *
         * @param Plugin $plugin
         */
        do_action('biolink/plugin/booted', $this);

        $this->booted = true;
    }

    private function registerCoreServices(): void
    {
        $this->register(Capabilities::class, new Capabilities());
        $this->register(Migrator::class, new Migrator());
        $this->register(BioLinkPagePostType::class, new BioLinkPagePostType());

        $repository = new PageRepository();
        $this->register(PageRepository::class, $repository);

        $registry = new BlockRegistry();
        $this->register(BlockRegistry::class, $registry);

        // Register built-in blocks via the same public hook third parties use.
        add_action(
            'biolink/blocks/register',
            static function (BlockRegistry $r): void {
                // P1
                $r->register(new LinkBlock());
                $r->register(new ButtonBlock());
                $r->register(new SocialIconsBlock());
                $r->register(new ImageGalleryBlock());
                $r->register(new RichTextBlock());
                $r->register(new DividerBlock());
                $r->register(new VideoBlock());
                $r->register(new YouTubeBlock());
                // P2
                $r->register(new SpotifyBlock());
                $r->register(new TiktokBlock());
                $r->register(new FaqBlock());
                $r->register(new CountdownBlock());
                $r->register(new ProductCardBlock());
                $r->register(new HtmlEmbedBlock());
                $r->register(new MapBlock());
                $r->register(new NewsletterBlock());
                $r->register(new DonationBlock());
                $r->register(new ContactFormBlock());
            },
            5
        );

        $themes = new ThemeEngine();
        $this->register(ThemeEngine::class, $themes);

        $this->register(PageRenderer::class, new PageRenderer($registry, $repository, $themes));
        $this->register(TemplateLoader::class, new TemplateLoader());
        $this->register(FrontendAssets::class, new FrontendAssets());

        // Phase 5 — Analytics
        $tracker  = new Tracker();
        $link_sync = new LinkSync($repository);
        $reporter = new Reporter();
        $this->register(Tracker::class, $tracker);
        $this->register(LinkSync::class, $link_sync);
        $this->register(Reporter::class, $reporter);
        $this->register(Pruner::class, new Pruner());

        // Wire passcode unlocks → analytics. Fires from both UnlockHandler
        // (no-JS path) and UnlockController (inline modal path).
        add_action('biolink/link/unlocked', static function (string $uuid, int $page_id) use ($tracker): void {
            $tracker->persistUnlock($uuid, $page_id);
        }, 10, 2);

        // Phase 6 — QR + SEO
        $this->register(QrGenerator::class, new QrGenerator());
        $this->register(MetaTags::class, new MetaTags($repository, $themes));
        $this->register(StructuredData::class, new StructuredData($repository));
        $this->register(Sitemap::class, new Sitemap());

        // Phase 9 — Templates
        $templates = new TemplateLibrary($repository);
        $this->register(TemplateLibrary::class, $templates);

        // Phase 8 — AI providers
        $ai_registry = new ProviderRegistry();
        $ai_registry->register(new OpenAiProvider());
        $this->register(ProviderRegistry::class, $ai_registry);

        // v1.1 — real integrations
        $stripe = new StripeCheckout();
        $paypal = new PayPalCheckout();
        $this->register(StripeCheckout::class, $stripe);
        $this->register(PayPalCheckout::class, $paypal);
        $this->register(StripeWebhookListener::class, new StripeWebhookListener($stripe));
        $this->register(PayPalReturnHandler::class, new PayPalReturnHandler($paypal));
        $this->register(UnlockHandler::class, new UnlockHandler($repository));
        $this->register(Shortcodes::class, new Shortcodes($repository, $this->get(PageRenderer::class), $themes));
        $this->register(MailchimpAdapter::class, new MailchimpAdapter());
        $this->register(MailerLiteAdapter::class, new MailerLiteAdapter());
        $this->register(ResendAdapter::class, new ResendAdapter());

        $updater = new GitHubUpdater(
            'nurkamol',
            'biolink-pro',
            BIOLINK_FILE,
            BIOLINK_VERSION
        );
        $this->register(GitHubUpdater::class, $updater);

        $this->register(
            RestRouter::class,
            new RestRouter([
                new PagesController($repository),
                new BlocksController($registry, $repository),
                new ChangelogController($updater),
                new ThemesController($themes),
                new FormsController(),
                new ClickController($tracker),
                new TrackController($tracker),
                new AnalyticsController($reporter),
                new QrController($this->get(QrGenerator::class)),
                new TemplatesController($templates),
                new SettingsController(),
                new AiController($ai_registry),
                new WebhookController(),
                new PortabilityController($repository),
                new CheckoutController($stripe, $paypal),
                new UnlockController($repository, $this->get(PageRenderer::class)),
            ])
        );

        $this->register(AdminMenu::class, new AdminMenu());
        $this->register(AdminAssets::class, new AdminAssets());
        $this->register(PluginActionLinks::class, new PluginActionLinks());
    }

    /**
     * Reset the singleton — test-only hook.
     *
     * @internal
     */
    public static function resetForTests(): void
    {
        self::$instance = null;
    }
}
