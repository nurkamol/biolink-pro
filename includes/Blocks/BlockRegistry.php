<?php
/**
 * Registry of available block types.
 *
 * @package BioLinkPro\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

/**
 * Holds the catalog of block types and dispatches `biolink/blocks/register`
 * so third-party plugins can add custom blocks.
 */
final class BlockRegistry implements Bootable
{
    /**
     * @var array<string, AbstractBlock>
     */
    private array $blocks = [];

    private bool $dispatched = false;

    public function boot(): void
    {
        add_action('init', [$this, 'dispatchRegistration'], 8);
    }

    /**
     * Fire `biolink/blocks/register` once so concrete blocks can register themselves.
     */
    public function dispatchRegistration(): void
    {
        if ($this->dispatched) {
            return;
        }
        $this->dispatched = true;

        /**
         * Register block types.
         *
         * @param BlockRegistry $registry
         */
        do_action('biolink/blocks/register', $this);
    }

    public function register(AbstractBlock $block): void
    {
        $slug = $block->slug();
        if ($slug === '') {
            return;
        }
        $this->blocks[$slug] = $block;
    }

    public function unregister(string $slug): void
    {
        unset($this->blocks[$slug]);
    }

    public function has(string $slug): bool
    {
        return isset($this->blocks[$slug]);
    }

    public function get(string $slug): ?AbstractBlock
    {
        return $this->blocks[$slug] ?? null;
    }

    /**
     * @return array<string, AbstractBlock>
     */
    public function all(): array
    {
        $this->dispatchRegistration();
        return $this->blocks;
    }

    /**
     * Serialize the catalog for the REST `/blocks` endpoint.
     *
     * @return list<array{slug: string, label: string, icon: string, schema: array<string, mixed>}>
     */
    public function describe(): array
    {
        $out = [];
        foreach ($this->all() as $block) {
            $out[] = [
                'slug'   => $block->slug(),
                'label'  => $block->label(),
                'icon'   => $block->icon(),
                'schema' => $block->schema(),
            ];
        }
        return $out;
    }
}
