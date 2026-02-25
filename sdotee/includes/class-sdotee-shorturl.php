<?php
/**
 * Short URL functionality.
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SDOTEE_ShortUrl
 *
 * Handles meta box, auto-shortening, and AJAX for short URL operations.
 */
class SDOTEE_ShortUrl {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'wp_ajax_sdotee_create_shorturl', array( $this, 'ajax_create' ) );
		add_action( 'wp_ajax_sdotee_delete_shorturl', array( $this, 'ajax_delete' ) );

		// Auto-shorten on publish.
		add_action( 'transition_post_status', array( $this, 'maybe_auto_shorten' ), 10, 3 );
	}

	/**
	 * Register the Short URL meta box for posts and pages.
	 */
	public function register_meta_box(): void {
		$post_types = array( 'post', 'page' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'sdotee-shorturl-metabox',
				__( 'S.EE Short URL', 'sdotee' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the Short URL meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		include SDOTEE_PLUGIN_DIR . 'admin/views/shorturl-metabox.php';
	}

	/**
	 * Auto-shorten URL on post publish if enabled.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function maybe_auto_shorten( string $new_status, string $old_status, \WP_Post $post ): void {
		// Only trigger when transitioning to 'publish'.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Only for posts and pages.
		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		// Check if auto-shorten is enabled.
		if ( '1' !== get_option( 'sdotee_auto_shorten', '' ) ) {
			return;
		}

		// Don't re-shorten if already exists.
		$existing = get_post_meta( $post->ID, '_sdotee_short_url', true );
		if ( ! empty( $existing ) ) {
			return;
		}

		$this->create_short_url( $post->ID );
	}

	/**
	 * Create a short URL for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $custom_slug Optional custom slug.
	 * @param string $domain Optional domain override.
	 * @return array|WP_Error Result array or WP_Error on failure.
	 */
	public function create_short_url( int $post_id, string $custom_slug = '', string $domain = '' ): array|\WP_Error {
		$client = SDOTEE_Helpers::get_client();
		if ( null === $client ) {
			return new \WP_Error( 'sdotee_no_client', __( 'S.EE client not configured. Please set your API key.', 'sdotee' ) );
		}

		$permalink = get_permalink( $post_id );
		if ( ! $permalink ) {
			return new \WP_Error( 'sdotee_no_permalink', __( 'Could not get post permalink.', 'sdotee' ) );
		}

		if ( empty( $domain ) ) {
			$domain = get_option( 'sdotee_default_domain', '' );
		}

		if ( empty( $domain ) ) {
			// Try to get the first available domain.
			$domains = SDOTEE_Helpers::get_domains();
			if ( ! empty( $domains ) ) {
				$domain = $domains[0];
			} else {
				return new \WP_Error( 'sdotee_no_domain', __( 'No domain available. Please configure a default domain.', 'sdotee' ) );
			}
		}

		$options = array(
			'title' => get_the_title( $post_id ),
		);

		if ( ! empty( $custom_slug ) ) {
			$options['custom_slug'] = $custom_slug;
		}

		try {
			$result = $client->shortUrl->create( $permalink, $domain, $options );

			// Store the result in post meta.
			$short_url = $result['short_url'] ?? '';
			$slug      = $result['slug'] ?? '';

			if ( ! empty( $short_url ) ) {
				update_post_meta( $post_id, '_sdotee_short_url', $short_url );
				update_post_meta( $post_id, '_sdotee_short_slug', $slug );
				update_post_meta( $post_id, '_sdotee_short_domain', $domain );
			}

			return $result;
		} catch ( \See\Exception\SeeException $e ) {
			SDOTEE_Helpers::log_error( 'Short URL creation failed: ' . $e->getMessage() );
			return new \WP_Error( 'sdotee_api_error', $e->getMessage() );
		}
	}

	/**
	 * AJAX: Create short URL.
	 */
	public function ajax_create(): void {
		check_ajax_referer( 'sdotee_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$custom_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$domain      = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'sdotee' ) ) );
		}

		$result = $this->create_short_url( $post_id, $custom_slug, $domain );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'   => __( 'Short URL created successfully!', 'sdotee' ),
			'short_url' => $result['short_url'] ?? '',
			'slug'      => $result['slug'] ?? '',
			'domain'    => $domain,
		) );
	}

	/**
	 * AJAX: Delete short URL.
	 */
	public function ajax_delete(): void {
		check_ajax_referer( 'sdotee_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'sdotee' ) ) );
		}

		$domain = get_post_meta( $post_id, '_sdotee_short_domain', true );
		$slug   = get_post_meta( $post_id, '_sdotee_short_slug', true );

		if ( empty( $domain ) || empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'No short URL found for this post.', 'sdotee' ) ) );
		}

		$client = SDOTEE_Helpers::get_client();
		if ( null === $client ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'sdotee' ) ) );
		}

		try {
			$client->shortUrl->delete( $domain, $slug );

			// Remove post meta.
			delete_post_meta( $post_id, '_sdotee_short_url' );
			delete_post_meta( $post_id, '_sdotee_short_slug' );
			delete_post_meta( $post_id, '_sdotee_short_domain' );

			wp_send_json_success( array(
				'message' => __( 'Short URL deleted successfully!', 'sdotee' ),
			) );
		} catch ( \See\Exception\SeeException $e ) {
			SDOTEE_Helpers::log_error( 'Short URL deletion failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
}
