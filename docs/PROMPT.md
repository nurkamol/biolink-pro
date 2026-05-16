# WordPress Bio Link Creator Plugin Prompt

Create a production-ready, modern, scalable WordPress plugin called **“BioLink Pro”** — a complete bio link / link-in-bio builder similar to Linktree, Beacons, and Carrd.

The plugin must be built with professional coding standards, optimized performance, security best practices, extensibility, and a polished UX/UI.

---

# Core Goal

Allow WordPress users to create customizable mobile-first bio pages using a drag-and-drop builder with analytics, themes, social links, branding, and monetization features.

The plugin should feel like a premium SaaS product but fully self-hosted inside WordPress.

---

# Technical Requirements

## Stack

- WordPress Plugin Architecture
- PHP 8.2+
- WordPress Coding Standards
- REST API support
- React-based Admin Dashboard (Gutenberg-style UI)
- TailwindCSS or modern utility-first styling
- Vanilla JS frontend output (optimized)
- No jQuery dependency unless absolutely required
- Modular architecture
- OOP structure
- Composer autoloading
- Namespace everything properly
- Translation-ready (i18n)
- Multisite compatible
- Compatible with latest WordPress version

---

# Plugin Structure

Use a clean enterprise architecture:

```txt
/plugin-root
├── /admin
├── /frontend
├── /includes
├── /templates
├── /assets
├── /blocks
├── /api
├── /modules
├── /database
├── /integrations
├── /themes
├── /analytics
├── /ai
├── plugin.php
├── uninstall.php
├── readme.txt
```

Use:
- Custom Post Types
- Custom Database Tables where needed
- WordPress REST API
- AJAX fallback support
- Secure nonce validation
- Capability checks everywhere
- Sanitization and escaping everywhere

---

# Main Features

## 1. Bio Page Builder

Users can:
- Create unlimited bio pages
- Set custom slugs
- Add profile image/avatar
- Add title + description
- Add CTA buttons
- Drag-and-drop reorder blocks
- Duplicate pages
- Save drafts
- Schedule publishing
- Add SEO metadata

Builder blocks:
- Links
- Buttons
- Social icons
- Image gallery
- Videos
- Spotify embeds
- YouTube embeds
- TikTok embeds
- Contact forms
- Divider
- Rich text
- FAQ
- Countdown timer
- Newsletter signup
- Product cards
- Donation button
- HTML embed
- Map embed

---

# Frontend Features

## Responsive Mobile-First UI

- Extremely optimized mobile layout
- Fast loading
- Accessibility compliant
- Smooth animations
- Lazy loading
- Retina-ready

## Themes

Include:
- Minimal
- Dark mode
- Neon
- Creator
- Glassmorphism
- Professional
- Retro
- Modern gradient
- Clean monochrome

Allow:
- Custom fonts
- Background videos
- Gradient backgrounds
- Animated backgrounds
- Custom CSS
- Theme presets

---

# Analytics System

Create built-in analytics dashboard:
- Link clicks
- Device type
- Country
- Referrer
- Browser
- UTM tracking
- Daily/weekly/monthly stats
- Top-performing links
- QR scan analytics

Use charts and visual reports.

---

# QR Code System

Each bio page automatically gets:
- QR code generation
- Download as PNG/SVG
- Custom colors
- Logo embedding
- Dynamic QR support

---

# Social Media Integrations

Support:
- Instagram
- TikTok
- YouTube
- Facebook
- X/Twitter
- LinkedIn
- Telegram
- WhatsApp
- Discord
- Twitch
- GitHub

Include official icons.

---

# Monetization Features

Add:
- Stripe integration
- PayPal integration
- Donation buttons
- Tip jar
- Digital product links
- Affiliate link tracking

---

# AI Features (Optional but Modular)

Create optional AI module:
- AI-generated bio descriptions
- AI theme suggestions
- AI CTA generation
- AI color palette suggestions

Support:
- OpenAI API integration
- Modular providers

---

# Advanced Features

## Custom Domains

Allow:
- domain mapping
- cname support
- branded URLs

## User System

Support:
- frontend dashboard
- user profiles
- role permissions
- creator accounts
- team collaboration

## Templates

Include pre-made templates:
- Creator
- Agency
- Musician
- Photographer
- Influencer
- Startup
- Personal brand
- Restaurant
- Developer portfolio

---

# Performance Requirements

- 90+ Lighthouse score
- Asset minification
- CSS/JS code splitting
- Page caching support
- CDN-friendly
- Query optimization
- Transient caching
- Image optimization
- WebP support

---

# Security Requirements

Implement:
- Nonce verification
- CSRF protection
- SQL injection prevention
- XSS prevention
- Rate limiting
- Secure REST endpoints
- Capability-based permissions
- GDPR-friendly settings

---

# SEO Features

- Schema.org support
- Open Graph tags
- Twitter cards
- XML sitemap support
- Meta customization
- Canonical URLs

---

# Admin Dashboard

Build modern SaaS-like admin panel:
- Clean UI
- Statistics overview
- Theme manager
- Analytics reports
- Template library
- Import/export
- Settings panel
- Integrations manager
- Onboarding wizard

---

# Developer Features

Add:
- Hooks/actions/filters
- Public API
- REST API documentation
- Extendable modules
- Custom block registration system
- Webhook support

---

# Optional Premium Features Architecture

Prepare architecture for:
- Subscription plans
- SaaS mode
- White-labeling
- Team workspaces
- Advanced analytics
- A/B testing
- Email marketing integrations
- Automation workflows

---

# Deliverables

Generate:
1. Full plugin architecture
2. Database schema
3. Admin UI structure
4. REST API endpoints
5. Frontend rendering system
6. React builder structure
7. Security layer
8. Analytics engine
9. Theme engine
10. Installation flow
11. Upgrade system
12. Example code implementation
13. File-by-file explanation
14. WordPress coding standards compliance
15. Scalability recommendations

---

# Design Direction

The plugin UI should feel like a mix of:
- modern SaaS dashboard
- Apple-level simplicity
- Notion-style cleanliness
- Framer-level smoothness
- minimal creator tools

Avoid:
- outdated WordPress admin styling
- cluttered UI
- excessive settings

---

# Final Goal

Create a plugin that can realistically compete with modern link-in-bio SaaS platforms while remaining:
- lightweight
- extensible
- developer-friendly
- highly optimized
- beautiful
- scalable for thousands of users/pages

---

# Additional Development Instructions

- Write clean and maintainable code
- Use SOLID principles
- Follow WordPress security best practices
- Add inline code comments
- Include database migration strategy
- Include plugin activation/deactivation hooks
- Include proper uninstall cleanup
- Include fallback handling for shared hosting
- Include extensibility for future SaaS conversion
- Use reusable React components
- Build reusable theme engine
- Use scalable analytics architecture
- Optimize for low server resource usage

---

# Expected Output

Generate:
- Full plugin architecture
- Boilerplate setup
- Core plugin files
- React admin panel structure
- REST API examples
- Database schema
- Frontend rendering engine
- Theme system
- Analytics engine
- Example UI/UX structure
- Deployment recommendations
- Scalability strategy
- Security audit checklist
- Future roadmap

The output should be production-grade and realistic for a commercial plugin.