<?php
/**
 * Audience REST endpoints — list + CSV export of newsletter / contact submissions.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Audience\SubmissionRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class AudienceController extends AbstractController
{
    public function __construct(private readonly SubmissionRepository $submissions)
    {
    }

    public function registerRoutes(): void
    {
        $cap = $this->requireCap('biolink_view_analytics');

        register_rest_route(
            self::NAMESPACE,
            '/audience',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'index'],
                'permission_callback' => $cap,
                'args'                => [
                    'page'     => ['type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint'],
                    'per_page' => ['type' => 'integer', 'default' => 50, 'sanitize_callback' => 'absint'],
                    'kind'     => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/audience/export.csv',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'export'],
                'permission_callback' => $cap,
                'args'                => [
                    'kind' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key'],
                ],
            ]
        );
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $page     = max(1, (int) $request['page']);
        $per_page = min(200, max(1, (int) $request['per_page']));
        $kind     = (string) $request['kind'];

        return $this->ok([
            'items'    => $this->submissions->list($page, $per_page, $kind),
            'total'    => $this->submissions->count($kind),
            'page'     => $page,
            'per_page' => $per_page,
        ]);
    }

    public function export(WP_REST_Request $request): void
    {
        $kind = (string) $request['kind'];
        // Pull up to 10,000 rows; that's the sweet spot before memory matters.
        $items = $this->submissions->list(1, 10000, $kind);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="biolink-audience-' . gmdate('Y-m-d') . '.csv"');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fputcsv($out, ['ID', 'Date (UTC)', 'Kind', 'Name', 'Email', 'Message', 'Page ID', 'Block UUID']);
        foreach ($items as $row) {
            fputcsv($out, [
                $row['id'],
                $row['created_at'],
                $row['kind'],
                $row['name'],
                $row['email'],
                $row['message'],
                $row['page_id'],
                $row['block_uuid'],
            ]);
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($out);
        exit;
    }
}
