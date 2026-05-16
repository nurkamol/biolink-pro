=== BioLink Pro ===
Contributors: nurkamol
Tags: bio link, linktree, link in bio, landing page, link builder
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted bio link / link-in-bio builder. Drag-and-drop blocks, themes, analytics, QR codes, monetization.

== Description ==

BioLink Pro is a complete bio link / link-in-bio builder for WordPress — a self-hosted alternative to Linktree, Beacons, and Carrd. Create unlimited mobile-first bio pages with a drag-and-drop block builder, switch between polished themes, track every click, and monetize with Stripe or PayPal — all without leaving your WordPress dashboard.

**Features**

* Drag-and-drop bio page builder (18+ block types)
* 9 built-in themes + custom CSS
* Built-in analytics: clicks, views, geo, devices, top links
* Per-page QR code generation (PNG/SVG, custom colors, logo embed)
* Stripe + PayPal integration for donations and product sales
* Social media icon library (Instagram, TikTok, YouTube, X, LinkedIn, Discord, Twitch, GitHub, …)
* Optional AI suggestions (OpenAI-compatible providers)
* Pre-made templates for creators, agencies, musicians, photographers, restaurants, developers
* Frontend dashboard for creators
* Multisite compatible
* Translation ready
* GDPR-friendly (hashed IPs, exporters, retention settings)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/biolink-pro` or install via Plugins → Add New.
2. Activate the plugin through the Plugins menu.
3. Go to **Bio Links** in the admin sidebar and follow the onboarding wizard.

== Frequently Asked Questions ==

= Does this work without a paid SaaS account? =

Yes. Everything runs on your own server. No external account required (unless you opt into Stripe, PayPal, or AI providers).

= What PHP version is required? =

PHP 8.2 or higher.

== Changelog ==

= 0.1.0 =
* Initial release. Phase 1 (core bootstrap + CPT + custom tables + capabilities) and Phase 2 (REST API + React admin shell + CRUD UI) complete.
* GitHub-based auto-updater wired through the wp-admin Plugins screen.
* What's New admin page with release history pulled from GitHub Releases.
