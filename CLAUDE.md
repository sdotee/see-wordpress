# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WordPress plugin for S.EE platform integration — URL shortening, text sharing, and file hosting. The plugin source lives in the `sdotee/` subdirectory.

- **PHP 8.2+** with typed properties and return types
- **WordPress 6.0+**, follows WordPress coding standards
- **Text Domain:** `sdotee`
- **License:** MIT
- **SDK dependency:** `sdotee/sdk` (Composer)

## Development & Deployment

```bash
# Install dependencies
cd sdotee && composer install

# Compile all translations (.po → .mo)
cd sdotee/languages && for po in *.po; do msgfmt -o "${po%.po}.mo" "$po"; done

# Deploy to local Docker WordPress
sudo rm -rf ~/docker/wordpress/wordpress/wp-content/plugins/sdotee
sudo cp -r sdotee/ ~/docker/wordpress/wordpress/wp-content/plugins/sdotee/
```

No build system, linter config, or test suite exists. JS is vanilla jQuery, CSS is plain.

## WordPress.org SVN Release

SVN repo: `https://plugins.svn.wordpress.org/sdotee` (username: `sdotee`)

```bash
# 1. Checkout SVN repo (shallow)
cd /tmp && svn co https://plugins.svn.wordpress.org/sdotee sdotee-svn --depth immediates

# 2. Copy plugin files to trunk, screenshots to assets
rsync -av --exclude='.DS_Store' sdotee/ /tmp/sdotee-svn/trunk/
cp wp-assets/*.png /tmp/sdotee-svn/assets/

# 3. Add new files
cd /tmp/sdotee-svn
svn add trunk/* --force
svn add assets/* --force

# 4. Update version in trunk (see.php + readme.txt) if needed
# - see.php: Version header + SDOTEE_VERSION constant
# - readme.txt: Stable tag

# 5. Create version tag
svn cp trunk tags/X.Y.Z

# 6. Commit (must run in real terminal for password prompt)
svn ci --username sdotee -m "Release vX.Y.Z"
```

**Note:** Claude Code's terminal cannot handle interactive password input. The `svn ci` command must be run manually in a real terminal. After first login, SVN caches credentials.

## Architecture

### Entry Point & Initialization

`see.php` → defines constants, loads Composer autoloader, requires all class files, hooks `SDOTEE_Plugin::get_instance()` on `plugins_loaded`.

### Singleton Module Pattern

```
SDOTEE_Plugin (singleton)
├── SDOTEE_Settings   — Settings API, API key management (encrypted via AES-256-CBC), domain caching
├── SDOTEE_Admin      — Menus, asset enqueuing, post list columns
├── SDOTEE_ShortUrl   — Short URL meta box + auto-shorten on publish
├── SDOTEE_File       — Media Library integration, sidebar meta box, standalone upload
├── SDOTEE_Text       — Text sharing meta box + standalone sharing
└── SDOTEE_Helpers    — Static utilities: SDK client factory, encryption, history, domain fetching
```

Each module registers its own hooks in `__construct()`. All AJAX handlers follow: `check_ajax_referer()` → `current_user_can()` → sanitize input → SDK call → `wp_send_json_success/error()`.

### Data Storage

| Storage | Keys | Purpose |
|---------|------|---------|
| `wp_options` | `sdotee_api_key`, `sdotee_api_base_url`, `sdotee_default_domain`, `sdotee_default_text_domain`, `sdotee_default_file_domain`, `sdotee_auto_shorten`, `sdotee_auto_upload`, `sdotee_text_history`, `sdotee_file_history` | Plugin settings & history |
| `post_meta` | `_sdotee_short_url`, `_sdotee_short_slug`, `_sdotee_short_domain` | Short URLs on posts/pages |
| `post_meta` | `_sdotee_file_url`, `_sdotee_file_delete_key` | Media Library S.EE uploads |
| `post_meta` | `_sdotee_post_file_url`, `_sdotee_post_file_name`, `_sdotee_post_file_delete_key` | Sidebar file uploads |
| `post_meta` | `_sdotee_text_url`, `_sdotee_text_slug`, `_sdotee_text_domain` | Text shares on posts/pages |
| `transients` | `sdotee_domains_cache`, `sdotee_file_domains_cache`, `sdotee_text_domains_cache` | Cached domain lists |

### JavaScript (`admin/js/sdotee-admin.js`)

jQuery IIFE with central `SDOTEE` object. Localized data via `sdoteeAdmin` global (`ajaxUrl`, `nonce`, `i18n`). Uses event delegation for dynamic content. Tab state persisted via URL hash.

### Translations

13 languages with `.po` source and `.mo` compiled files in `sdotee/languages/`. WordPress auto-loads translations (no `load_plugin_textdomain()` call needed since WP 4.6).

## Naming Conventions

- **Options/transients:** `sdotee_` prefix
- **Post meta:** `_sdotee_` prefix (underscore hides from custom fields UI)
- **PHP classes:** `SDOTEE_` prefix, one class per file, `class-sdotee-*.php` naming
- **AJAX actions:** `sdotee_` prefix (e.g., `sdotee_create_shorturl`)
- **CSS classes:** `sdotee-` prefix
- **JS handlers:** `bind*()` methods on the `SDOTEE` object

## wp-config.php Overrides

`SDOTEE_API_KEY` and `SDOTEE_API_BASE_URL` constants override database settings when defined.
