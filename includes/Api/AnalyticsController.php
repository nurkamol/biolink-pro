<?php
/**
 * REST endpoints for the analytics dashboard.
 *
 * @package BioLinkPro\Api
 */

declare(strict_types=1);

namespace BioLinkPro\Api;

use BioLinkPro\Analytics\Reporter;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

final class AnalyticsController extends AbstractController
{
    public function __construct(private readonly Reporter $reporter)
    {
    }

    public function registerRoutes(): void
    {
        $args = [
            'id'   => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            'from' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
            'to'   => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field'],
        ];
        $cap = $this->requireCap('biolink_view_analytics');

        foreach (['summary', 'timeseries', 'links', 'devices', 'geo', 'referrers'] as $method) {
            register_rest_route(
                self::NAMESPACE,
                "/analytics/pages/(?P<id>\\d+)/{$method}",
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, $method],
                    'permission_callback' => $cap,
                    'args'                => $args,
                ]
            );
        }

        register_rest_route(
            self::NAMESPACE,
            '/analytics/pages/(?P<id>\d+)/export.csv',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'export'],
                'permission_callback' => $cap,
                'args'                => $args,
            ]
        );
    }

    /**
     * @return array{from:string, to:string}
     */
    private function range(WP_REST_Request $request): array
    {
        $to_str   = (string) $request['to'];
        $from_str = (string) $request['from'];
        $to       = $to_str !== '' ? strtotime($to_str) : time();
        $from     = $from_str !== '' ? strtotime($from_str) : (time() - 30 * DAY_IN_SECONDS);
        if ($to === false) {
            $to = time();
        }
        if ($from === false) {
            $from = time() - 30 * DAY_IN_SECONDS;
        }
        return [
            'from' => gmdate('Y-m-d 00:00:00', $from),
            'to'   => gmdate('Y-m-d 23:59:59', $to),
        ];
    }

    public function summary(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->summary((int) $request['id'], $r['from'], $r['to']));
    }

    public function timeseries(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->timeseries((int) $request['id'], $r['from'], $r['to']));
    }

    public function links(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->topLinks((int) $request['id'], $r['from'], $r['to']));
    }

    public function devices(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->devices((int) $request['id'], $r['from'], $r['to']));
    }

    public function geo(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->geo((int) $request['id'], $r['from'], $r['to']));
    }

    public function referrers(WP_REST_Request $request): WP_REST_Response
    {
        $r = $this->range($request);
        return $this->ok($this->reporter->referrers((int) $request['id'], $r['from'], $r['to']));
    }

    public function export(WP_REST_Request $request): void
    {
        $r       = $this->range($request);
        $series  = $this->reporter->timeseries((int) $request['id'], $r['from'], $r['to']);
        $links   = $this->reporter->topLinks((int) $request['id'], $r['from'], $r['to'], 100);

        $filename = sprintf('biolink-analytics-page-%d-%s_to_%s.csv', (int) $request['id'], substr($r['from'], 0, 10), substr($r['to'], 0, 10));
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, ['# Daily totals']);
        fputcsv($out, ['date', 'views', 'clicks']);
        foreach ($series as $row) {
            fputcsv($out, [$row['date'], $row['views'], $row['clicks']]);
        }
        fputcsv($out, []);
        fputcsv($out, ['# Top links']);
        fputcsv($out, ['link_id', 'label', 'url', 'clicks']);
        foreach ($links as $row) {
            fputcsv($out, [$row['link_id'], $row['label'], $row['url'], $row['clicks']]);
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
        fclose($out);
        exit;
    }
}
