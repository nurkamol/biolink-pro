<?php
/**
 * CRUD for the audience submissions table.
 *
 * @package BioLinkPro\Audience
 */

declare(strict_types=1);

namespace BioLinkPro\Audience;

use BioLinkPro\Analytics\Tracker;
use BioLinkPro\Core\Bootable;

defined('ABSPATH') || exit;

final class SubmissionRepository implements Bootable
{
    public function boot(): void
    {
        add_action('biolink/newsletter/subscribed', [$this, 'recordNewsletter'], 5);
        add_action('biolink/contact/submitted', [$this, 'recordContact'], 5);
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function recordNewsletter(array $entry): void
    {
        $this->insert([
            'page_id'    => (int) ($entry['page_id'] ?? 0),
            'block_uuid' => (string) ($entry['block_uuid'] ?? ''),
            'kind'       => 'newsletter',
            'email'      => (string) ($entry['email'] ?? ''),
            'name'       => '',
            'payload'    => $entry,
        ]);
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function recordContact(array $entry): void
    {
        $this->insert([
            'page_id'    => (int) ($entry['page_id'] ?? 0),
            'block_uuid' => (string) ($entry['block_uuid'] ?? ''),
            'kind'       => 'contact',
            'email'      => (string) ($entry['email'] ?? ''),
            'name'       => (string) ($entry['name'] ?? ''),
            'payload'    => $entry,
        ]);
    }

    /**
     * @param array{page_id:int, block_uuid:string, kind:string, email:string, name:string, payload:array<string, mixed>} $row
     */
    private function insert(array $row): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_submissions';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->insert(
            $table,
            [
                'page_id'    => $row['page_id'],
                'block_uuid' => $row['block_uuid'] !== '' ? substr($row['block_uuid'], 0, 36) : null,
                'kind'       => substr($row['kind'], 0, 32),
                'email'      => $row['email'] !== '' ? substr($row['email'], 0, 190) : null,
                'name'       => $row['name'] !== '' ? substr($row['name'], 0, 190) : null,
                'payload'    => (string) wp_json_encode($row['payload']),
                'ip_hash'    => $ip !== '' ? Tracker::hashIp($ip) : null,
                'created_at' => current_time('mysql', true),
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(int $page = 1, int $per_page = 50, string $kind = ''): array
    {
        global $wpdb;
        $table  = $wpdb->prefix . 'biolink_submissions';
        $offset = max(0, ($page - 1) * $per_page);
        $where  = '';
        $args   = [];
        if ($kind !== '') {
            $where  = 'WHERE kind = %s';
            $args[] = $kind;
        }
        $args[] = $per_page;
        $args[] = $offset;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, page_id, block_uuid, kind, email, name, payload, created_at
                 FROM {$table} {$where}
                 ORDER BY created_at DESC
                 LIMIT %d OFFSET %d",
                ...$args
            ),
            ARRAY_A
        );
        $out = [];
        foreach ((array) $rows as $row) {
            $payload = json_decode((string) ($row['payload'] ?? ''), true);
            $out[] = [
                'id'         => (int) $row['id'],
                'page_id'    => (int) $row['page_id'],
                'block_uuid' => (string) ($row['block_uuid'] ?? ''),
                'kind'       => (string) $row['kind'],
                'email'      => (string) ($row['email'] ?? ''),
                'name'       => (string) ($row['name'] ?? ''),
                'message'    => is_array($payload) ? (string) ($payload['message'] ?? '') : '',
                'created_at' => (string) $row['created_at'],
            ];
        }
        return $out;
    }

    public function count(string $kind = ''): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'biolink_submissions';
        if ($kind === '') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE kind = %s", $kind)
        );
    }
}
