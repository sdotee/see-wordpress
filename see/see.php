<?php
/**
 * Plugin Name: S.EE URL Shortener, Text & File Sharing
 * Plugin URI:  https://github.com/sdotee/see-wordpress
 * Description: Integrate S.EE URL shortener, text sharing, and file hosting into WordPress.
 * Version:     1.0.1
 * Author:      S.EE
 * Author URI:  https://s.ee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: see
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'SEE_VERSION', '1.0.1' );
define( 'SEE_PLUGIN_FILE', __FILE__ );
define( 'SEE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoload.
if ( file_exists( SEE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SEE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include plugin classes.
require_once SEE_PLUGIN_DIR . 'includes/class-see-helpers.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-settings.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-admin.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-shorturl.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-file.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-text.php';
require_once SEE_PLUGIN_DIR . 'includes/class-see-plugin.php';

/**
 * Initialize the plugin on plugins_loaded.
 */
function see_init() {
	SEE_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'see_init' );

/**
 * Activation hook.
 */
function see_activate() {
	// Set default options if not already set.
	if ( false === get_option( 'see_api_key' ) ) {
		add_option( 'see_api_key', '' );
	}
	if ( false === get_option( 'see_api_base_url' ) ) {
		add_option( 'see_api_base_url', 'https://s.ee/api/v1/' );
	}
	if ( false === get_option( 'see_default_domain' ) ) {
		add_option( 'see_default_domain', '' );
	}
	if ( false === get_option( 'see_default_file_domain' ) ) {
		add_option( 'see_default_file_domain', '' );
	}
	if ( false === get_option( 'see_default_text_domain' ) ) {
		add_option( 'see_default_text_domain', '' );
	}
	if ( false === get_option( 'see_auto_shorten' ) ) {
		add_option( 'see_auto_shorten', '' );
	}
	if ( false === get_option( 'see_auto_upload' ) ) {
		add_option( 'see_auto_upload', '' );
	}
}
register_activation_hook( __FILE__, 'see_activate' );

/**
 * Deactivation hook - only clear transients.
 */
function see_deactivate() {
	delete_transient( 'see_domains_cache' );
	delete_transient( 'see_file_domains_cache' );
	delete_transient( 'see_text_domains_cache' );
	delete_transient( 'see_tags_cache' );
}
register_deactivation_hook( __FILE__, 'see_deactivate' );
