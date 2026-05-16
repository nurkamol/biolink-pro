<?php
/**
 * Base class every BioLink block type extends.
 *
 * @package BioLinkPro\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks;

defined('ABSPATH') || exit;

/**
 * Concrete blocks live in `includes/Blocks/Types/` and ship in Phase 3.
 *
 * This abstract intentionally has no behavior yet — it defines the contract
 * the registry depends on so the REST `/blocks` endpoint can describe types
 * before any block class exists.
 */
abstract class AbstractBlock
{
    /** Stable snake_case identifier persisted in stored JSON. */
    abstract public function slug(): string;

    /** Translated, human-readable label shown in the admin inserter. */
    abstract public function label(): string;

    /** Lucide icon name (or path to inline SVG) for the inserter. */
    abstract public function icon(): string;

    /**
     * Schema describing each field on this block's `data` payload.
     *
     * Keys are field names; values describe `type`, `required`, and optional
     * `max` / `enum` / `default`. See docs/BLOCKS.md for the full spec.
     *
     * @return array<string, array{type: string, required?: bool, max?: int, enum?: list<string>, default?: mixed}>
     */
    abstract public function schema(): array;

    /**
     * Render the block to HTML for the public bio page.
     *
     * @param array<string, mixed> $data Decoded block data after sanitization.
     */
    abstract public function render(array $data): string;

    /**
     * Asset handles the block needs enqueued on the frontend. Default: none.
     *
     * @return array{admin?: list<string>, frontend?: list<string>}
     */
    public function assets(): array
    {
        return [];
    }

    /**
     * Hook called after the parent page is saved. Default: no-op.
     *
     * @param int                  $page_id Parent CPT post ID.
     * @param array<string, mixed> $data    Decoded block data.
     */
    public function onSave(int $page_id, array $data): void
    {
        // Override to mirror block data into a custom table (see LinkBlock in Phase 3).
    }
}
