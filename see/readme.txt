=== S.EE URL Shortener, Text & File Sharing ===
Contributors: sdotee
Tags: url shortener, short url, file upload, text share, s.ee
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Integrate S.EE URL shortener, text sharing, and file hosting into WordPress.

== Description ==

Bring the power of the S.EE platform directly into your dashboard. Shorten URLs, share text, and upload files without leaving your site.

**Features:**

* **URL Shortening** - Generate short URLs for your posts and pages using your custom S.EE domains.
* **Auto Shorten** - Automatically create short URLs when you publish a post or page.
* **Text Sharing** - Share text snippets as plain text, Markdown, or source code via S.EE.
* **File Upload** - Upload files to S.EE from the Media Library or the post editor sidebar, with one-click copy in URL, HTML, Markdown, and BBCode formats.
* **Auto Upload** - Optionally auto-upload all new media to S.EE.
* **Post List Integration** - View and copy short URLs directly from the Posts/Pages list.
* **Management Dashboard** - Standalone tools for text sharing and file uploads with history tracking.

**Requirements:**

* PHP 8.1 or higher
* WordPress 6.0 or higher
* An S.EE account and API key

== Installation ==

1. Upload the `see` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > S.EE and enter your API key.
4. Click "Test Connection" to verify your API key works.
5. Select your default domains and configure automation options.

== Frequently Asked Questions ==

= Where do I get an API key? =

Sign up at [s.ee](https://s.ee) and generate an API key from your account settings.

= Can I define the API key in wp-config.php? =

Yes. Add `define( 'SEE_API_KEY', 'your-api-key-here' );` to your `wp-config.php` file. This takes priority over the database setting.

= Can I use a custom API base URL? =

Yes. Either set it in Settings > S.EE or define `SEE_API_BASE_URL` in `wp-config.php`.

= Which post types support short URL generation? =

Posts and Pages are supported by default.

== Screenshots ==

1. Settings page - Configure your API key, select default domains for short URLs, files, and text sharing, and enable automation options.
2. Management page - Standalone tools for text sharing and file uploads with history tracking.
3. Post editor sidebar - S.EE meta boxes for text sharing, short URL generation, and file uploads with one-click copy in multiple formats.

== Changelog ==

= 1.0.0 =
* Initial release.
* URL shortening with meta box and auto-shorten support.
* Text sharing with plain text, Markdown, and source code support.
* File upload integration with Media Library.
* Settings page with API key management and domain configuration.
* Management page for standalone text sharing and file uploads.
* Post/Page list column for short URLs.
* Full i18n support with 13 languages: English, 简体中文, 繁體中文, 日本語, 한국어, Bahasa Indonesia, Tiếng Việt, Deutsch, Français, Español, Português, Português do Brasil, and Русский.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
