<?php
/**
 * Marker interface for AI providers.
 *
 * @package BioLinkPro\Ai
 */

declare(strict_types=1);

namespace BioLinkPro\Ai;

defined('ABSPATH') || exit;

interface Provider
{
    public function id(): string;

    public function isConfigured(): bool;

    /**
     * @return list<string> 1–3 suggestions, ideally
     */
    public function suggest(string $kind, string $prompt): array;
}
