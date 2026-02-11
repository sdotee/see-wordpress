# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin for S.EE platform integration — URL shortening, text sharing, and file hosting. The plugin source lives in the `see/` subdirectory.

- **PHP 8.1+** with typed properties and return types
- **WordPress 6.0+**, follows WordPress coding standards
- **Text Domain:** `see`
- **License:** MIT
- **SDK dependency:** `sdotee/sdk` (Composer)

## Development & Deployment

```bash
# Install dependencies
cd see && composer install

# Compile all translations (.po → .mo)
cd see/languages && for po in *.po; do msgfmt -o "${po%.po}.mo" "$po"; done

# Deploy to local Docker WordPress
sudo rm -rf ~/docker/wordpress/wordpress/wp-content/plugins/see
sudo cp -r see/ ~/docker/wordpress/wordpress/wp-content/plugins/see/
sudo chown -R www-data:www-data ~/docker/wordpress/wordpress/wp-content/plugins/see/
```

No build system, linter config, or test suite exists. JS is vanilla jQuery, CSS is plain.

## Architecture

### Entry Point & Initialization

`see.php` → defines constants, loads Composer autoloader, requires all class files, hooks `SEE_Plugin::get_instance()` on `plugins_loaded`.

### Singleton Module Pattern

```
SEE_Plugin (singleton)
├── SEE_Settings   — Settings API, API key management (encrypted via AES-256-CBC), domain caching
├── SEE_Admin      — Menus, asset enqueuing, post list columns
├── SEE_ShortUrl   — Short URL meta box + auto-shorten on publish
├── SEE_File       — Media Library integration, sidebar meta box, standalone upload
├── SEE_Text       — Text sharing meta box + standalone sharing
└── SEE_Helpers    — Static utilities: SDK client factory, encryption, history, domain fetching
```

Each module registers its own hooks in `__construct()`. All AJAX handlers follow: `check_ajax_referer()` → `current_user_can()` → sanitize input → SDK call → `wp_send_json_success/error()`.

### Data Storage

| Storage | Keys | Purpose |
|---------|------|---------|
| `wp_options` | `see_api_key`, `see_api_base_url`, `see_default_domain`, `see_default_text_domain`, `see_default_file_domain`, `see_auto_shorten`, `see_auto_upload`, `see_text_history`, `see_file_history` | Plugin settings & history |
| `post_meta` | `_see_short_url`, `_see_short_slug`, `_see_short_domain` | Short URLs on posts/pages |
| `post_meta` | `_see_file_url`, `_see_file_delete_key` | Media Library S.EE uploads |
| `post_meta` | `_see_post_file_url`, `_see_post_file_name`, `_see_post_file_delete_key` | Sidebar file uploads |
| `post_meta` | `_see_text_url`, `_see_text_slug`, `_see_text_domain` | Text shares on posts/pages |
| `transients` | `see_domains_cache`, `see_file_domains_cache`, `see_text_domains_cache` | Cached domain lists |

### JavaScript (`admin/js/see-admin.js`)

jQuery IIFE with central `SEE` object. Localized data via `seeAdmin` global (`ajaxUrl`, `nonce`, `i18n`). Uses event delegation for dynamic content. Tab state persisted via URL hash.

### Translations

13 languages with `.po` source and `.mo` compiled files in `see/languages/`. WordPress auto-loads translations (no `load_plugin_textdomain()` call needed since WP 4.6).

## Naming Conventions

- **Options/transients:** `see_` prefix
- **Post meta:** `_see_` prefix (underscore hides from custom fields UI)
- **PHP classes:** `SEE_` prefix, one class per file, `class-see-*.php` naming
- **AJAX actions:** `see_` prefix (e.g., `see_create_shorturl`)
- **CSS classes:** `see-` prefix
- **JS handlers:** `bind*()` methods on the `SEE` object

## wp-config.php Overrides

`SEE_API_KEY` and `SEE_API_BASE_URL` constants override database settings when defined.
