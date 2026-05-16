<?php
/**
 * Wires every REST controller into WordPress on `rest_api_init`.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class RestRouter implements Bootable
{
    /**
     * @var list<AbstractController>
     */
    private array $controllers;

    /**
     * @param list<AbstractController> $controllers
     */
    public function __construct(array $controllers)
    {
        $this->controllers = $controllers;
    }

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->registerRoutes();
        }
    }
}
