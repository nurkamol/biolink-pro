# BioLink Pro

A production-grade WordPress plugin for building bio link / link-in-bio pages — a self-hosted alternative to Linktree, Beacons, and Carrd.

## What this is

This folder is the **starter scaffold** for the BioLink Pro plugin. It contains the directory structure, planning docs, and the original project brief so an AI agent (or human dev) can pick up work from the CLI.

## Quick start (resume work from CLI)

```bash
cd /path/to/biolink-pro
# Open in your editor or launch Claude Code / Codex / Cursor here
claude
```

When you start a session, point the agent at `docs/PROMPT.md` (the original brief) and `docs/ARCHITECTURE.md` (the build plan). The agent should read `docs/CLAUDE.md` first for coding conventions.

## Status

**Phase 0 — Scaffold only.** No PHP code has been written yet. Begin at Phase 1 (see `docs/ROADMAP.md`).

## Folder structure

```
biolink-pro/
├── admin/          ← React admin dashboard (Gutenberg-style UI)
├── frontend/       ← Public-facing bio page renderer
├── includes/       ← Core PHP classes (OOP, namespaced)
├── templates/      ← PHP template parts
├── assets/         ← Compiled JS/CSS, images, fonts
├── blocks/         ← Builder block definitions (links, embeds, forms, …)
├── api/            ← REST API controllers
├── modules/        ← Optional feature modules
├── database/       ← Migrations + custom table schemas
├── integrations/   ← Stripe, PayPal, OpenAI, social providers
├── themes/         ← Theme presets (Minimal, Neon, Glassmorphism, …)
├── analytics/      ← Tracking + reporting engine
├── ai/             ← AI helper module (OpenAI, modular providers)
├── docs/           ← Planning + reference docs (start here)
├── plugin.php      ← Main plugin bootstrap (to be created)
├── uninstall.php   ← Cleanup on uninstall (to be created)
└── readme.txt      ← WordPress.org plugin readme (to be created)
```

## Docs index

| File | Purpose |
|---|---|
| `docs/PROMPT.md` | Original full project brief |
| `docs/CLAUDE.md` | Coding conventions + AI agent instructions |
| `docs/ARCHITECTURE.md` | System architecture, modules, data flow |
| `docs/DATABASE.md` | Custom tables, schema, migrations |
| `docs/API.md` | REST endpoint inventory |
| `docs/BLOCKS.md` | Builder block catalog + registration |
| `docs/THEMES.md` | Theme engine + presets |
| `docs/SECURITY.md` | Security checklist (nonces, caps, sanitization) |
| `docs/ROADMAP.md` | Phased delivery plan |
| `docs/SCALABILITY.md` | Performance + scale recommendations |

## License

GPL-2.0-or-later (WordPress plugin standard).
