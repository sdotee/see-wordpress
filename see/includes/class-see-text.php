<?php
/**
 * Text sharing functionality.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_Text
 *
 * Handles text sharing meta box and AJAX callbacks.
 */
class SEE_Text {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'wp_ajax_see_create_text', array( $this, 'ajax_create' ) );
		add_action( 'wp_ajax_see_delete_text', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_see_remove_text_history', array( $this, 'ajax_remove_history' ) );
	}

	/**
	 * Register the Text Share meta box for posts and pages.
	 */
	public function register_meta_box(): void {
		$post_types = array( 'post', 'page' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'see-text-metabox',
				__( 'S.EE Text Share', 'see' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
	 * Render the Text Share meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$text_url    = get_post_meta( $post->ID, '_see_text_url', true );
		$text_slug   = get_post_meta( $post->ID, '_see_text_slug', true );
		$text_domain = get_post_meta( $post->ID, '_see_text_domain', true );
		?>
		<div class="see-text-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php if ( ! empty( $text_url ) ) : ?>
				<div class="see-text-result">
					<p class="see-text-url">
						<a href="<?php echo esc_url( $text_url ); ?>" target="_blank">
							<?php echo esc_html( $text_url ); ?>
						</a>
					</p>
					<div class="see-text-actions">
						<button type="button" class="button button-small see-copy-btn" data-url="<?php echo esc_attr( $text_url ); ?>">
							<?php esc_html_e( 'Copy', 'see' ); ?>
						</button>
						<button type="button" class="button button-small see-delete-text-btn"
								data-domain="<?php echo esc_attr( $text_domain ); ?>"
								data-slug="<?php echo esc_attr( $text_slug ); ?>">
							<?php esc_html_e( 'Delete', 'see' ); ?>
						</button>
					</div>
				</div>
			<?php else : ?>
				<div class="see-text-form">
					<p>
						<label for="see-text-content"><?php esc_html_e( 'Content:', 'see' ); ?></label>
						<textarea id="see-text-content" rows="5" class="widefat"></textarea>
					</p>
					<p>
						<label for="see-text-title"><?php esc_html_e( 'Title (optional):', 'see' ); ?></label>
						<input type="text" id="see-text-title" class="widefat" />
					</p>
					<p>
						<label for="see-text-type"><?php esc_html_e( 'Type:', 'see' ); ?></label>
						<select id="see-text-type" class="widefat">
							<option value="plain_text"><?php esc_html_e( 'Plain Text', 'see' ); ?></option>
							<option value="markdown"><?php esc_html_e( 'Markdown', 'see' ); ?></option>
							<option value="source_code"><?php esc_html_e( 'Source Code', 'see' ); ?></option>
						</select>
					</p>
					<p>
						<button type="button" class="button button-primary see-create-text-btn">
							<?php esc_html_e( 'Share Text', 'see' ); ?>
						</button>
						<span class="see-text-status"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX: Create text share.
	 */
	public function ajax_create(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'see' ) ) );
		}

		$content   = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$text_type = isset( $_POST['text_type'] ) ? sanitize_text_field( wp_unslash( $_POST['text_type'] ) ) : 'plain_text';
		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'Content cannot be empty.', 'see' ) ) );
		}

		// Validate text_type.
		$allowed_types = array( 'plain_text', 'markdown', 'source_code' );
		if ( ! in_array( $text_type, $allowed_types, true ) ) {
			$text_type = 'plain_text';
		}

		$client = SEE_Helpers::get_client();
		if ( null === $client ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'see' ) ) );
		}

		$options = array(
			'text_type' => $text_type,
		);

		$default_domain = get_option( 'see_default_text_domain', '' );
		if ( ! empty( $default_domain ) ) {
			$options['domain'] = $default_domain;
		}

		if ( empty( $title ) ) {
			$title = 'Untitled';
		}
		$options['title'] = $title;

		try {
			$result = $client->text->create( $content, $options );

			$text_url    = $result['short_url'] ?? '';
			$text_slug   = $result['slug'] ?? '';
			$text_domain = $result['domain'] ?? '';

			// Store in post meta if post_id provided.
			if ( $post_id > 0 && ! empty( $text_url ) ) {
				update_post_meta( $post_id, '_see_text_url', $text_url );
				update_post_meta( $post_id, '_see_text_slug', $text_slug );
				update_post_meta( $post_id, '_see_text_domain', $text_domain );
			}

			// Save to standalone history.
			if ( 0 === $post_id && ! empty( $text_url ) ) {
				SEE_Helpers::add_history( 'see_text_history', array(
					'url'       => $text_url,
					'slug'      => $text_slug,
					'domain'    => $text_domain,
					'title'     => $title,
					'text_type' => $text_type,
				) );
			}

			wp_send_json_success( array(
				'message'  => __( 'Text shared successfully!', 'see' ),
				'text_url' => $text_url,
				'slug'     => $text_slug,
				'domain'   => $text_domain,
			) );
		} catch ( \See\Exception\SeeException $e ) {
			SEE_Helpers::log_error( 'Text share creation failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Delete text share.
	 */
	public function ajax_delete(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'see' ) ) );
		}

		$domain  = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';
		$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( empty( $domain ) || empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing domain or slug.', 'see' ) ) );
		}

		$client = SEE_Helpers::get_client();
		if ( null === $client ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'see' ) ) );
		}

		try {
			$client->text->delete( $domain, $slug );

			// Remove post meta.
			if ( $post_id > 0 ) {
				delete_post_meta( $post_id, '_see_text_url' );
				delete_post_meta( $post_id, '_see_text_slug' );
				delete_post_meta( $post_id, '_see_text_domain' );
			}

			wp_send_json_success( array(
				'message' => __( 'Text share deleted successfully!', 'see' ),
			) );
		} catch ( \See\Exception\SeeException $e ) {
			SEE_Helpers::log_error( 'Text share deletion failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Remove a text share history entry.
	 */
	public function ajax_remove_history(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'see' ) ) );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_id'] ) ) : '';

		if ( empty( $entry_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entry.', 'see' ) ) );
		}

		SEE_Helpers::remove_history( 'see_text_history', $entry_id );

		wp_send_json_success( array(
			'message' => __( 'History entry removed.', 'see' ),
		) );
	}
}
