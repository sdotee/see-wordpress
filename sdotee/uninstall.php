<?php
/**
 * Uninstall script for S.EE plugin.
 *
 * Removes all plugin data from the database.
 *
 * @package SDOTEE
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
$options = array(
	'sdotee_api_key',
	'sdotee_api_base_url',
	'sdotee_default_domain',
	'sdotee_default_text_domain',
	'sdotee_default_file_domain',
	'sdotee_auto_shorten',
	'sdotee_auto_upload',
	'sdotee_text_history',
	'sdotee_file_history',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete transients.
delete_transient( 'sdotee_domains_cache' );
delete_transient( 'sdotee_file_domains_cache' );
delete_transient( 'sdotee_text_domains_cache' );
delete_transient( 'sdotee_tags_cache' );

// Delete all post meta created by the plugin.
$meta_keys = array(
	'_sdotee_short_url',
	'_sdotee_short_slug',
	'_sdotee_short_domain',
	'_sdotee_file_url',
	'_sdotee_file_delete_key',
	'_sdotee_file_page',
	'_sdotee_text_url',
	'_sdotee_text_slug',
	'_sdotee_text_domain',
	'_sdotee_post_file_url',
	'_sdotee_post_file_name',
	'_sdotee_post_file_delete_key',
	'_sdotee_post_file_page',
);

foreach ( $meta_keys as $meta_key ) {
	delete_metadata( 'post', 0, $meta_key, '', true );
}
