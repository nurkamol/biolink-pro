<?php
/**
 * Inline-SVG icon dictionary.
 *
 * @package BioLinkPro\Blocks
 */

declare(strict_types=1);

namespace BioLinkPro\Blocks;

defined('ABSPATH') || exit;

/**
 * Keeps the frontend payload small by hard-coding only the icons blocks need,
 * instead of pulling a full icon-font library. SVGs are 24×24 stroke-2 lucide-style.
 *
 * Two categories:
 *   - utility icons (link, button) — currentColor strokes
 *   - social brand marks (filled paths, currentColor fill)
 */
final class Icons
{
    /**
     * @var array<string, string>
     */
    private const UTILITY = [
        'link'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
        'globe'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'mail'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/></svg>',
        'phone'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'shopping-bag' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        'play'        => '<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="6,4 20,12 6,20"/></svg>',
        'download'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
        'arrow-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>',
        'heart'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'calendar'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'star'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>',
        'pin'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    ];

    /**
     * Social-platform brand marks (filled).
     *
     * @var array<string, string>
     */
    private const SOCIAL = [
        'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.75 3.75 0 0 1-1.38-.9 3.75 3.75 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63a5.92 5.92 0 0 0-2.13 1.39A5.92 5.92 0 0 0 .63 4.14c-.3.76-.5 1.64-.56 2.91C.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.8.73 1.48 1.39 2.13a5.92 5.92 0 0 0 2.13 1.39c.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.92 5.92 0 0 0 2.13-1.39 5.92 5.92 0 0 0 1.39-2.13c.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.92 5.92 0 0 0-1.39-2.13A5.92 5.92 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zm0 10.16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z"/></svg>',
        'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43V8.36a8.16 8.16 0 0 0 4.77 1.52V6.43a4.85 4.85 0 0 1-1.84-.26z"/></svg>',
        'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1C24 15.9 24 12 24 12s0-3.9-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>',
        'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'linkedin'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95v5.66H9.36V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56v11.45zM22.23 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.46c.98 0 1.77-.77 1.77-1.72V1.72C24 .77 23.21 0 22.23 0z"/></svg>',
        'github'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 .3a12 12 0 0 0-3.79 23.38c.6.11.82-.26.82-.58v-2.03c-3.34.73-4.04-1.43-4.04-1.43-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.21.09 1.85 1.24 1.85 1.24 1.07 1.84 2.81 1.31 3.49 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.46-1.33-5.46-5.93 0-1.31.47-2.38 1.24-3.22-.13-.31-.54-1.53.11-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.28-1.55 3.3-1.23 3.3-1.23.65 1.65.24 2.87.12 3.18a4.64 4.64 0 0 1 1.23 3.22c0 4.61-2.8 5.63-5.47 5.92.42.36.81 1.1.81 2.22v3.29c0 .32.21.7.83.58A12 12 0 0 0 12 .3"/></svg>',
        'twitch'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.149 0L.537 4.119v17.81h6.327V24h3.224l3.045-3.045h4.926L24 15.062V0H2.149zm19.76 14.045L18.46 17.49h-5.224l-3.045 3.045v-3.045H5.434V1.93h16.475v12.115zm-3.045-8.715v6.328h-1.91V5.33h1.91zm-5.224 0v6.328h-1.91V5.33h1.91z"/></svg>',
        'discord'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.79 19.79 0 0 0-4.885-1.515.07.07 0 0 0-.073.035c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.68 12.68 0 0 0-.617-1.25.078.078 0 0 0-.073-.035 19.74 19.74 0 0 0-4.885 1.515.073.073 0 0 0-.033.027C.533 9.05-.32 13.58.099 18.06a.08.08 0 0 0 .032.057 19.93 19.93 0 0 0 5.993 3.03.08.08 0 0 0 .087-.028 14.21 14.21 0 0 0 1.226-1.994.076.076 0 0 0-.041-.105 13.13 13.13 0 0 1-1.873-.892.077.077 0 0 1-.008-.127c.126-.094.252-.193.372-.292a.075.075 0 0 1 .078-.011c3.927 1.793 8.18 1.793 12.06 0a.075.075 0 0 1 .079.01c.12.1.245.199.372.293a.077.077 0 0 1-.006.127 12.3 12.3 0 0 1-1.873.891.077.077 0 0 0-.041.106c.36.7.772 1.366 1.225 1.994a.076.076 0 0 0 .088.028 19.84 19.84 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.06.06 0 0 0-.031-.029zM8.02 15.331c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.094 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.094 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
        'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'threads'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.46 11.13a8 8 0 0 0-.3-.13c-.17-3.28-1.95-5.16-4.96-5.18h-.04c-1.8 0-3.3.77-4.22 2.16l1.66 1.14c.69-1.04 1.76-1.27 2.56-1.27h.03c1 0 1.76.3 2.26.86.36.41.6 1 .72 1.73-.92-.16-1.92-.21-2.99-.15-3.01.17-4.95 1.93-4.82 4.38.07 1.24.69 2.32 1.74 3.02.89.6 2.05.88 3.25.82 1.59-.09 2.83-.69 3.71-1.81.66-.84 1.09-1.94 1.27-3.31.74.45 1.29 1.04 1.59 1.74.51 1.2.54 3.17-1.06 4.78-1.4 1.4-3.09 2.01-5.65 2.03-2.83-.02-4.98-.93-6.38-2.7-1.32-1.66-2-4.06-2.02-7.12.03-3.06.7-5.46 2.02-7.12 1.4-1.77 3.54-2.68 6.38-2.7 2.85.02 5.04.93 6.5 2.71 1.69 2.07 1.83 4.65 1.85 4.72l1.99-.16c-.13-1.31-.42-3.13-1.61-4.79C18.7 1.39 16.07.16 12.74.13H12.7c-3.32.02-5.91 1.27-7.7 3.71-1.6 2.18-2.42 5.18-2.45 8.95.03 3.77.85 6.77 2.45 8.95 1.79 2.44 4.38 3.69 7.7 3.71h.04c2.95-.02 5.03-.79 6.74-2.5 2.25-2.25 2.18-5.07 1.44-6.8-.53-1.24-1.54-2.25-2.96-2.92zm-5.04 5.34c-1.32.07-2.7-.52-2.77-1.83-.05-.97.69-2.05 2.85-2.18l.39-.01c.78 0 1.51.08 2.18.22-.25 3.13-1.72 3.71-2.65 3.8z"/></svg>',
        'spotify'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0a12 12 0 1 0 0 24 12 12 0 0 0 0-24zm5.5 17.31a.75.75 0 0 1-1.03.25c-2.82-1.73-6.38-2.12-10.57-1.17a.75.75 0 0 1-.33-1.46c4.59-1.04 8.5-.59 11.68 1.36a.75.75 0 0 1 .25 1.02zm1.47-3.27a.94.94 0 0 1-1.28.31c-3.23-1.98-8.15-2.56-11.97-1.4a.94.94 0 0 1-.55-1.79c4.37-1.33 9.81-.69 13.49 1.59a.94.94 0 0 1 .31 1.29zm.13-3.4c-3.87-2.3-10.27-2.51-13.97-1.39a1.12 1.12 0 1 1-.66-2.15c4.25-1.29 11.32-1.04 15.78 1.6a1.12 1.12 0 1 1-1.15 1.94z"/></svg>',
        'website'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'email'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><polyline points="22,6 12,13 2,6"/></svg>',
    ];

    public static function utility(string $name): string
    {
        return self::UTILITY[$name] ?? '';
    }

    public static function social(string $platform): string
    {
        return self::SOCIAL[$platform] ?? '';
    }

    /**
     * @return list<string>
     */
    public static function utilityNames(): array
    {
        return array_keys(self::UTILITY);
    }

    /**
     * @return list<string>
     */
    public static function socialPlatforms(): array
    {
        return array_keys(self::SOCIAL);
    }
}
