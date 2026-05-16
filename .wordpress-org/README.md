# WordPress.org plugin directory assets

This directory holds the graphic assets that WordPress.org's plugin directory
displays on the plugin's public page. They are **never** included in the
release zip — wp.org reads them from a separate `/assets/` SVN path when
the plugin is published there.

For our GitHub-hosted release flow, this folder is informational only.

## Required files

| File | Dimensions | Purpose |
|---|---|---|
| `banner-1544x500.png` | 1544 × 500 | Banner shown at the top of the plugin's wp.org page |
| `banner-1544x500.jpg` | 1544 × 500 | JPG fallback (smaller file) |
| `banner-772x250.png` | 772 × 250 | Low-DPI banner fallback |
| `icon-256x256.png` | 256 × 256 | Square icon shown in search results / Install Now button |
| `icon-128x128.png` | 128 × 128 | Low-DPI icon fallback |
| `icon.svg` | vector | SVG icon (overrides PNG icons on supported browsers) |
| `screenshot-1.png` | any | First screenshot shown on the wp.org page (block builder) |
| `screenshot-2.png` | any | Analytics dashboard |
| `screenshot-3.png` | any | Theme picker grid |
| `screenshot-4.png` | any | Background overrides |
| `screenshot-5.png` | any | What's New page |
| `screenshot-6.png` | any | Mobile-rendered bio page |

Screenshots are referenced by index in the `== Screenshots ==` section of
`readme.txt`. Order matters (1, 2, 3 … match the descriptions there).

## Producing the assets

The placeholder `icon.svg` in this folder is a starting point. For
production, design at the largest size required and downscale. Suggested
tools: Figma, Affinity Designer, Sketch.

## Submitting to WordPress.org

1. Read https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
2. Drop the assets into the SVN `/assets/` directory at
   `https://plugins.svn.wordpress.org/biolink-pro/assets/`
3. Commit — wp.org regenerates the page within a few minutes.

## Plugin Check note for wp.org submission

`Updates\GitHubUpdater` overrides WordPress's update routine. WordPress.org
does not permit this. To submit a fork to wp.org, gate the updater behind a
constant:

```php
// in plugin.php, before instantiating the updater
if ( ! defined( 'BIOLINK_PRO_DISABLE_UPDATER' ) || ! BIOLINK_PRO_DISABLE_UPDATER ) {
    // register GitHubUpdater as today
}
```

Then set `define( 'BIOLINK_PRO_DISABLE_UPDATER', true );` in the wp.org
shipped variant.
