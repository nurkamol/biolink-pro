<?php
/**
 * @package BioLinkPro\Ai
 */

declare(strict_types=1);

namespace BioLinkPro\Ai;

defined('ABSPATH') || exit;

final class ProviderRegistry
{
    /**
     * @var array<string, Provider>
     */
    private array $providers = [];

    public function register(Provider $provider): void
    {
        $this->providers[$provider->id()] = $provider;
    }

    public function active(): ?Provider
    {
        $settings = (array) get_option('biolink_settings', []);
        $preferred = (string) ($settings['ai_provider'] ?? 'openai');
        if (isset($this->providers[$preferred]) && $this->providers[$preferred]->isConfigured()) {
            return $this->providers[$preferred];
        }
        foreach ($this->providers as $p) {
            if ($p->isConfigured()) {
                return $p;
            }
        }
        return null;
    }
}
