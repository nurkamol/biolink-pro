<?php
/**
 * Schema migrator — runs `database/migrations/*` files in order.
 *
 * @package BioLinkPro\Database
 */

declare(strict_types=1);

namespace BioLinkPro\Database;

use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

/**
 * Runs forward-only schema migrations.
 *
 * Each migration file lives in `database/migrations/` and returns a callable
 * that accepts (string $prefix, string $charset_collate) and runs `dbDelta()`.
 */
final class Migrator implements Bootable
{
    private const OPTION_KEY = 'biolink_db_version';

    public function boot(): void
    {
        add_action('plugins_loaded', [$this, 'maybeMigrate'], 9);
    }

    /**
     * Run any pending migrations if the stored version trails the constant.
     */
    public function maybeMigrate(): void
    {
        $installed = (string) get_option(self::OPTION_KEY, '0');
        if (version_compare($installed, BIOLINK_DB_VERSION, '>=')) {
            return;
        }

        $this->run();
    }

    /**
     * Force-run every migration. Used on activation and from {@see maybeMigrate()}.
     */
    public function run(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        global $wpdb;
        $prefix          = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();

        foreach ($this->migrationFiles() as $file) {
            $migration = require $file;
            if (is_callable($migration)) {
                $migration($prefix, $charset_collate);
            }
        }

        update_option(self::OPTION_KEY, BIOLINK_DB_VERSION, false);
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $dir = BIOLINK_PATH . 'database/migrations/';
        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '*.php');
        if ($files === false) {
            return [];
        }

        sort($files, SORT_STRING);
        return $files;
    }
}
