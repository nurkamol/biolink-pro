<?php
/**
 * Marker interface for services that need to register hooks on plugin boot.
 *
 * @package BioLinkPro\Core
 */

declare(strict_types=1);

namespace BioLinkPro\Core;

defined('ABSPATH') || exit;

interface Bootable
{
    /**
     * Register hooks / filters / rewrite rules. Called once per request.
     */
    public function boot(): void;
}
