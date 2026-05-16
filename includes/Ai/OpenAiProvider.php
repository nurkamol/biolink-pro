<?php
/**
 * OpenAI Chat Completions adapter.
 *
 * @package BioLinkPro\Ai
 */

declare(strict_types=1);

namespace BioLinkPro\Ai;

use BioLinkPro\Core\Crypto;

defined('ABSPATH') || exit;

final class OpenAiProvider implements Provider
{
    public function id(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        $key = $this->apiKey();
        return $key !== '';
    }

    /**
     * @return list<string>
     */
    public function suggest(string $kind, string $prompt): array
    {
        $key = $this->apiKey();
        if ($key === '') {
            return [];
        }

        $system = match ($kind) {
            'bio'   => 'You are a brand copywriter. Write 3 concise (under 18 words) bio subtitle options for a link-in-bio page based on the user prompt. Return one per line, no numbering.',
            'cta'   => 'You are a conversion copywriter. Write 3 short (under 4 words) call-to-action button labels based on the user prompt. Return one per line, no numbering.',
            'theme' => 'Recommend a UI theme. Return 3 lines, each "<theme_slug>: <one-sentence reason>". Allowed slugs: mono, glass, forest, midnight, neon, sunset, aurora, sky.',
            default => 'Help the user with their bio link page. Return 3 short suggestions, one per line.',
        };

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode([
                    'model'       => 'gpt-4o-mini',
                    'temperature' => 0.8,
                    'max_tokens'  => 240,
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]),
            ]
        );

        if (is_wp_error($response)) {
            return [];
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return [];
        }
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $text = $body['choices'][0]['message']['content'] ?? '';
        if (! is_string($text) || $text === '') {
            return [];
        }
        $lines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $text) ?: [])));
        return array_slice($lines, 0, 3);
    }

    private function apiKey(): string
    {
        $integrations = (array) get_option('biolink_integrations', []);
        $stored       = (string) ($integrations['openai_api_key'] ?? '');
        if ($stored === '') {
            return '';
        }
        return ( new Crypto() )->decrypt($stored);
    }
}
