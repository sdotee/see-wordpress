<?php
/**
 * Plugin Name: S.EE URL Shortener, Text & File Sharing
 * Plugin URI:  https://github.com/sdotee/see-wordpress
 * Description: Integrate S.EE URL shortener, text sharing, and file hosting into WordPress.
 * Version:     1.0.4
 * Author:      S.EE
 * Author URI:  https://s.ee
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: sdotee
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'SDOTEE_VERSION', '1.0.4' );
define( 'SDOTEE_PLUGIN_FILE', __FILE__ );
define( 'SDOTEE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SDOTEE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SDOTEE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoload.
if ( file_exists( SDOTEE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SDOTEE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include plugin classes.
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-helpers.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-settings.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-admin.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-shorturl.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-file.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-text.php';
require_once SDOTEE_PLUGIN_DIR . 'includes/class-sdotee-plugin.php';

/**
 * Initialize the plugin on plugins_loaded.
 */
function sdotee_init() {
	SDOTEE_Plugin::get_instance();
}
add_action( 'plugins_loaded', 'sdotee_init' );

/**
 * Activation hook.
 */
function sdotee_activate() {
	// Set default options if not already set.
	if ( false === get_option( 'sdotee_api_key' ) ) {
		add_option( 'sdotee_api_key', '' );
	}
	if ( false === get_option( 'sdotee_api_base_url' ) ) {
		add_option( 'sdotee_api_base_url', 'https://s.ee/api/v1/' );
	}
	if ( false === get_option( 'sdotee_default_domain' ) ) {
		add_option( 'sdotee_default_domain', '' );
	}
	if ( false === get_option( 'sdotee_default_file_domain' ) ) {
		add_option( 'sdotee_default_file_domain', '' );
	}
	if ( false === get_option( 'sdotee_default_text_domain' ) ) {
		add_option( 'sdotee_default_text_domain', '' );
	}
	if ( false === get_option( 'sdotee_auto_shorten' ) ) {
		add_option( 'sdotee_auto_shorten', '' );
	}
	if ( false === get_option( 'sdotee_auto_upload' ) ) {
		add_option( 'sdotee_auto_upload', '' );
	}
}
register_activation_hook( __FILE__, 'sdotee_activate' );

/**
 * Deactivation hook - only clear transients.
 */
function sdotee_deactivate() {
	delete_transient( 'sdotee_domains_cache' );
	delete_transient( 'sdotee_file_domains_cache' );
	delete_transient( 'sdotee_text_domains_cache' );
	delete_transient( 'sdotee_tags_cache' );
}
register_deactivation_hook( __FILE__, 'sdotee_deactivate' );
