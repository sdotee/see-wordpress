<?php
/**
 * Uninstall script for S.EE plugin.
 *
 * Removes all plugin data from the database.
 *
 * @package SEE
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
$options = array(
	'see_api_key',
	'see_api_base_url',
	'see_default_domain',
	'see_default_text_domain',
	'see_default_file_domain',
	'see_auto_shorten',
	'see_auto_upload',
	'see_text_history',
	'see_file_history',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete transients.
delete_transient( 'see_domains_cache' );
delete_transient( 'see_file_domains_cache' );
delete_transient( 'see_text_domains_cache' );
delete_transient( 'see_tags_cache' );

// Delete all post meta created by the plugin.
$meta_keys = array(
	'_see_short_url',
	'_see_short_slug',
	'_see_short_domain',
	'_see_file_url',
	'_see_file_delete_key',
	'_see_text_url',
	'_see_text_slug',
	'_see_text_domain',
	'_see_post_file_url',
	'_see_post_file_name',
	'_see_post_file_delete_key',
);

foreach ( $meta_keys as $meta_key ) {
	delete_metadata( 'post', 0, $meta_key, '', true );
}
