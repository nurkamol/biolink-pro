<?php
/**
 * Base class for every REST controller in the `biolink/v1` namespace.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Shared response/permission helpers. Concrete controllers register routes
 * inside {@see registerRoutes()}.
 */
abstract class AbstractController
{
    public const NAMESPACE = 'biolink/v1';

    /**
     * Register every route owned by this controller. Called from {@see RestRouter}.
     */
    abstract public function registerRoutes(): void;

    /**
     * Build a permission callback that checks a single capability.
     *
     * @return callable(WP_REST_Request): bool|WP_Error
     */
    protected function requireCap(string $capability): callable
    {
        return static function (WP_REST_Request $_request) use ($capability) {
            if (current_user_can($capability)) {
                return true;
            }
            return new WP_Error(
                'biolink_forbidden',
                __('You do not have permission to perform this action.', 'biolink-pro'),
                ['status' => rest_authorization_required_code()]
            );
        };
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     */
    protected function ok(array $data, int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response($data, $status);
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function error(string $code, string $message, int $status, array $extra = []): WP_Error
    {
        return new WP_Error('biolink_' . $code, $message, array_merge(['status' => $status], $extra));
    }
}
