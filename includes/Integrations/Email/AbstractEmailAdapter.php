<?php
/**
 * Shared plumbing for email provider adapters.
 *
 * @package BioLinkPro\Integrations\Email
 */

declare(strict_types=1);

namespace BioLinkPro\Integrations\Email;

use BioLinkPro\Core\Bootable;
use BioLinkPro\Core\Crypto;

defined('ABSPATH') || exit;

abstract class AbstractEmailAdapter implements Bootable
{
    public function boot(): void
    {
        add_action('biolink/newsletter/subscribed', [$this, 'onSubscribed'], 10, 1);
    }

    /**
     * @param array<string, mixed> $entry { email, page_id, time, ip_hash }
     */
    public function onSubscribed(array $entry): void
    {
        if (! $this->isConfigured()) {
            return;
        }
        $email = isset($entry['email']) ? (string) $entry['email'] : '';
        if ($email === '' || ! is_email($email)) {
            return;
        }
        try {
            $this->subscribe($email, $entry);
        } catch (\Throwable $e) {
            // Don't break the public-facing form because a provider is unreachable.
            error_log(sprintf('[BioLink Pro] %s newsletter forward failed: %s', $this->providerLabel(), $e->getMessage())); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    abstract public function providerLabel(): string;
    abstract public function isConfigured(): bool;

    /**
     * @param array<string, mixed> $entry
     */
    abstract protected function subscribe(string $email, array $entry): void;

    /**
     * Read an encrypted credential from biolink_integrations.
     */
    protected function readSecret(string $key): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        $value  = (string) ($stored[$key] ?? '');
        if ($value === '') {
            return '';
        }
        return ( new Crypto() )->decrypt($value);
    }

    /**
     * Read a plain (non-encrypted) integrations setting.
     */
    protected function readSetting(string $key): string
    {
        $stored = (array) get_option('biolink_integrations', []);
        return is_string($stored[$key] ?? null) ? $stored[$key] : '';
    }
}
