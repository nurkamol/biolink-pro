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
use BioLinkPro\Api\BlocksController;
use BioLinkPro\Api\ChangelogController;
use BioLinkPro\Api\PagesController;
use BioLinkPro\Api\RestRouter;
use BioLinkPro\Api\ThemesController;
use BioLinkPro\Blocks\BlockRegistry;
use BioLinkPro\Blocks\Types\ButtonBlock;
use BioLinkPro\Blocks\Types\DividerBlock;
use BioLinkPro\Blocks\Types\ImageGalleryBlock;
use BioLinkPro\Blocks\Types\LinkBlock;
use BioLinkPro\Blocks\Types\RichTextBlock;
use BioLinkPro\Blocks\Types\SocialIconsBlock;
use BioLinkPro\Blocks\Types\VideoBlock;
use BioLinkPro\Blocks\Types\YouTubeBlock;
use BioLinkPro\Database\Migrator;
use BioLinkPro\Frontend\Assets as FrontendAssets;
use BioLinkPro\Frontend\PageRenderer;
use BioLinkPro\Frontend\PostType\BioLinkPagePostType;
use BioLinkPro\Frontend\Repository\PageRepository;
use BioLinkPro\Frontend\TemplateLoader;
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

        // Register P1 blocks via the same public hook third parties use.
        add_action(
            'biolink/blocks/register',
            static function (BlockRegistry $r): void {
                $r->register(new LinkBlock());
                $r->register(new ButtonBlock());
                $r->register(new SocialIconsBlock());
                $r->register(new ImageGalleryBlock());
                $r->register(new RichTextBlock());
                $r->register(new DividerBlock());
                $r->register(new VideoBlock());
                $r->register(new YouTubeBlock());
            },
            5
        );

        $themes = new ThemeEngine();
        $this->register(ThemeEngine::class, $themes);

        $this->register(PageRenderer::class, new PageRenderer($registry, $repository, $themes));
        $this->register(TemplateLoader::class, new TemplateLoader());
        $this->register(FrontendAssets::class, new FrontendAssets());

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
