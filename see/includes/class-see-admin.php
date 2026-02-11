<?php
/**
 * Admin menu and pages.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_Admin
 *
 * Registers admin menus, enqueues scripts/styles, adds columns to post lists.
 */
class SEE_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . SEE_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Post list columns.
		add_filter( 'manage_posts_columns', array( $this, 'add_shorturl_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_shorturl_column' ), 10, 2 );
		add_filter( 'manage_pages_columns', array( $this, 'add_shorturl_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'render_shorturl_column' ), 10, 2 );
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus(): void {
		// Settings page under Settings menu.
		add_options_page(
			__( 'S.EE Settings', 'see' ),
			__( 'S.EE', 'see' ),
			'manage_options',
			'see-settings',
			array( $this, 'render_settings_page' )
		);

		// Management page under Tools menu.
		add_management_page(
			__( 'S.EE Management', 'see' ),
			__( 'S.EE', 'see' ),
			'edit_posts',
			'see-management',
			array( $this, 'render_management_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS only on relevant pages.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Pages where we load our assets.
		$see_pages = array(
			'settings_page_see-settings',
			'tools_page_see-management',
			'post.php',
			'post-new.php',
			'upload.php',
			'post.php',
		);

		$screen = get_current_screen();

		// Also load on edit.php (post lists) for the short URL column.
		$is_post_list = $screen && 'edit' === $screen->base;

		if ( ! in_array( $hook_suffix, $see_pages, true ) && ! $is_post_list ) {
			// Check if it's an attachment edit page.
			if ( ! $screen || 'attachment' !== $screen->post_type ) {
				return;
			}
		}

		wp_enqueue_style(
			'see-admin-css',
			SEE_PLUGIN_URL . 'admin/css/see-admin.css',
			array(),
			SEE_VERSION
		);

		wp_enqueue_script(
			'see-admin-js',
			SEE_PLUGIN_URL . 'admin/js/see-admin.js',
			array( 'jquery' ),
			SEE_VERSION,
			true
		);

		wp_localize_script( 'see-admin-js', 'seeAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'see_admin_nonce' ),
			'i18n'    => array(
				'testing'          => __( 'Testing...', 'see' ),
				'success'          => __( 'Connection successful!', 'see' ),
				'error'            => __( 'Error', 'see' ),
				'copied'           => __( 'Copied!', 'see' ),
				'copyFailed'       => __( 'Copy failed', 'see' ),
				'generating'       => __( 'Generating...', 'see' ),
				'deleting'         => __( 'Deleting...', 'see' ),
				'uploading'        => __( 'Uploading...', 'see' ),
				'sharing'          => __( 'Sharing...', 'see' ),
				'refreshing'       => __( 'Refreshing...', 'see' ),
				'confirm_delete'   => __( 'Are you sure you want to delete this?', 'see' ),
				'confirm_remove_history' => __( "This will only remove the record from local history.\n\nThe content on S.EE will NOT be deleted. If you need to delete the source file, please visit s.ee/user/links.\n\nContinue?", 'see' ),
				'no_api_key'       => __( 'Please configure your API key in S.EE settings first.', 'see' ),
				'show'             => __( 'Show', 'see' ),
				'hide'             => __( 'Hide', 'see' ),
			),
		) );
	}

	/**
	 * Add Settings link to plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function plugin_action_links( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=see-settings' ) ) . '">'
			. esc_html__( 'Settings', 'see' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'see' ) );
		}
		include SEE_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render the management page.
	 */
	public function render_management_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'see' ) );
		}
		include SEE_PLUGIN_DIR . 'admin/views/management-page.php';
	}

	/**
	 * Add Short URL column to post/page list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_shorturl_column( array $columns ): array {
		$columns['see_short_url'] = __( 'Short URL', 'see' );
		return $columns;
	}

	/**
	 * Render Short URL column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_shorturl_column( string $column, int $post_id ): void {
		if ( 'see_short_url' !== $column ) {
			return;
		}

		$short_url = get_post_meta( $post_id, '_see_short_url', true );
		if ( ! empty( $short_url ) ) {
			echo '<a href="' . esc_url( $short_url ) . '" target="_blank" class="see-short-url-link" title="'
				. esc_attr( $short_url ) . '">' . esc_html( $short_url ) . '</a>';
			echo ' <button type="button" class="button-link see-copy-btn" data-url="'
				. esc_attr( $short_url ) . '" title="' . esc_attr__( 'Copy', 'see' ) . '">';
			echo '<span class="dashicons dashicons-clipboard"></span></button>';
		} else {
			echo '<span class="see-no-url">—</span>';
		}
	}
}
