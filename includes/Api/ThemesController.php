<?php
/**
 * REST controller exposing the theme catalog to the React admin.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Themes\ThemeEngine;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class ThemesController extends AbstractController
{
    public function __construct(private readonly ThemeEngine $themes)
    {
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/themes',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $this->requireCap('biolink_manage_pages'),
            ]
        );
    }

    public function index(WP_REST_Request $_request): WP_REST_Response
    {
        $payload = [];
        foreach ($this->themes->all() as $preset) {
            $payload[] = $preset->toApiArray();
        }
        return $this->ok($payload);
    }
}
