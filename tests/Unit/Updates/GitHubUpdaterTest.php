<?php
/**
 * @package BioLinkPro\Tests\Unit\Updates
 */

declare(strict_types=1);

namespace BioLinkPro\Tests\Unit\Updates;

use BioLinkPro\Updates\GitHubUpdater;
use PHPUnit\Framework\TestCase;

final class GitHubUpdaterTest extends TestCase
{
    public function testTagToVersionStripsVPrefix(): void
    {
        self::assertSame('1.2.3', GitHubUpdater::tagToVersion('v1.2.3'));
        self::assertSame('1.2.3', GitHubUpdater::tagToVersion('V1.2.3'));
        self::assertSame('1.2.3', GitHubUpdater::tagToVersion('1.2.3'));
    }

    public function testFindAssetUrlMatchesExactName(): void
    {
        // Stub plugin_basename in bootstrap returns "<dirname>/<basename>". Use a
        // realistic plugin path so the derived slug is `biolink-pro`.
        $updater = new GitHubUpdater('nurkamol', 'biolink-pro', '/wp-content/plugins/biolink-pro/plugin.php', '1.0.0');
        $release = [
            'tag_name' => 'v1.2.3',
            'assets'   => [
                ['name' => 'biolink-pro-v1.2.3.zip', 'browser_download_url' => 'https://example.com/expected.zip'],
                ['name' => 'source.tar.gz', 'browser_download_url' => 'https://example.com/source.tar.gz'],
            ],
        ];
        self::assertSame('https://example.com/expected.zip', $updater->findAssetUrl($release));
    }

    public function testFindAssetUrlPrefersExactMatchOverFallback(): void
    {
        $updater = new GitHubUpdater('nurkamol', 'biolink-pro', '/wp-content/plugins/biolink-pro/plugin.php', '1.0.0');
        $release = [
            'tag_name' => 'v2.0.0',
            'assets'   => [
                ['name' => 'biolink-pro-beta.zip', 'browser_download_url' => 'https://example.com/wrong.zip'],
                ['name' => 'biolink-pro-v2.0.0.zip', 'browser_download_url' => 'https://example.com/right.zip'],
            ],
        ];
        self::assertSame('https://example.com/right.zip', $updater->findAssetUrl($release));
    }

    public function testFindAssetUrlReturnsNullWithoutAssets(): void
    {
        $updater = new GitHubUpdater('nurkamol', 'biolink-pro', '/tmp/fake.php', '1.0.0');
        self::assertNull($updater->findAssetUrl(['tag_name' => 'v1.0.0', 'assets' => []]));
        self::assertNull($updater->findAssetUrl(['tag_name' => 'v1.0.0']));
    }

    public function testPluginSlugDerivedFromBasename(): void
    {
        $updater = new GitHubUpdater('nurkamol', 'biolink-pro', '/tmp/fake.php', '1.0.0');
        // plugin_basename is stubbed by tests/bootstrap.php; should return 'tmp' as the dirname.
        // We just assert that calling it doesn't blow up and returns a non-empty string.
        self::assertNotEmpty($updater->pluginBasename());
        self::assertNotEmpty($updater->pluginSlug());
    }
}
