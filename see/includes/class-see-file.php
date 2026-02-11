<?php
/**
 * File upload functionality.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_File
 *
 * Handles file uploads to S.EE, Media Library integration, and AJAX callbacks.
 */
class SEE_File {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_see_upload_file', array( $this, 'ajax_upload' ) );
		add_action( 'wp_ajax_see_delete_file', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_see_upload_standalone_file', array( $this, 'ajax_upload_standalone' ) );
		add_action( 'wp_ajax_see_remove_file_history', array( $this, 'ajax_remove_history' ) );

		// Sidebar meta box.
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'wp_ajax_see_upload_sidebar_file', array( $this, 'ajax_upload_sidebar' ) );
		add_action( 'wp_ajax_see_delete_sidebar_file', array( $this, 'ajax_delete_sidebar' ) );

		// Media Library integration.
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_fields' ), 10, 2 );

		// Replace attachment URL with S.EE URL when available.
		add_filter( 'wp_get_attachment_url', array( $this, 'filter_attachment_url' ), 10, 2 );

		// Auto upload on attachment add.
		add_action( 'add_attachment', array( $this, 'maybe_auto_upload' ) );
	}

	/**
	 * Register the File Upload meta box for posts and pages.
	 */
	public function register_meta_box(): void {
		$post_types = array( 'post', 'page' );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'see-file-metabox',
				__( 'S.EE File Upload', 'sdotee' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'low'
			);
		}
	}

	/**
	 * Render the File Upload meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$file_url  = get_post_meta( $post->ID, '_see_post_file_url', true );
		$file_name = get_post_meta( $post->ID, '_see_post_file_name', true );
		?>
		<div class="see-file-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php if ( ! empty( $file_url ) ) : ?>
				<div class="see-file-result">
					<p class="see-file-name">
						<strong><?php esc_html_e( 'File:', 'sdotee' ); ?></strong>
						<?php echo esc_html( $file_name ); ?>
					</p>
					<p class="see-file-url">
						<a href="<?php echo esc_url( $file_url ); ?>" target="_blank">
							<?php echo esc_html( $file_url ); ?>
						</a>
					</p>
					<div class="see-format-copy-group">
						<button type="button" class="button button-small see-format-copy-btn" data-format="url"
								data-url="<?php echo esc_attr( $file_url ); ?>"
								data-filename="<?php echo esc_attr( $file_name ); ?>">URL</button>
						<button type="button" class="button button-small see-format-copy-btn" data-format="html"
								data-url="<?php echo esc_attr( $file_url ); ?>"
								data-filename="<?php echo esc_attr( $file_name ); ?>">HTML</button>
						<button type="button" class="button button-small see-format-copy-btn" data-format="markdown"
								data-url="<?php echo esc_attr( $file_url ); ?>"
								data-filename="<?php echo esc_attr( $file_name ); ?>">Markdown</button>
						<button type="button" class="button button-small see-format-copy-btn" data-format="bbcode"
								data-url="<?php echo esc_attr( $file_url ); ?>"
								data-filename="<?php echo esc_attr( $file_name ); ?>">BBCode</button>
					</div>
					<div class="see-file-actions">
						<button type="button" class="button button-small see-delete-sidebar-file-btn">
							<?php esc_html_e( 'Delete', 'sdotee' ); ?>
						</button>
					</div>
				</div>
			<?php else : ?>
				<div class="see-sidebar-file-form">
					<p>
						<input type="file" class="see-sidebar-file-input" />
					</p>
					<p>
						<button type="button" class="button button-primary see-upload-sidebar-file-btn">
							<?php esc_html_e( 'Upload to S.EE', 'sdotee' ); ?>
						</button>
						<span class="see-sidebar-file-status"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX: Upload file from sidebar meta box.
	 */
	public function ajax_upload_sidebar(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'sdotee' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file provided.', 'sdotee' ) ) );
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- File data is validated below and sanitized via sanitize_file_name().

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => __( 'File upload error.', 'sdotee' ) ) );
		}

		$client = SEE_Helpers::get_client();
		if ( null === $client ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'sdotee' ) ) );
		}

		$tmp_path = $file['tmp_name'];
		$filename = sanitize_file_name( $file['name'] );

		try {
			$result = $client->file->upload( $tmp_path, $filename );

			$file_url   = $result['url'] ?? '';
			$delete_key = $result['delete'] ?? ( $result['hash'] ?? '' );

			if ( ! empty( $file_url ) ) {
				update_post_meta( $post_id, '_see_post_file_url', $file_url );
				update_post_meta( $post_id, '_see_post_file_name', $filename );
				update_post_meta( $post_id, '_see_post_file_delete_key', $delete_key );
			}

			wp_send_json_success( array(
				'message'  => __( 'File uploaded to S.EE successfully!', 'sdotee' ),
				'file_url' => $file_url,
				'filename' => $filename,
			) );
		} catch ( \See\Exception\SeeException $e ) {
			SEE_Helpers::log_error( 'Sidebar file upload failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Delete file uploaded from sidebar meta box.
	 */
	public function ajax_delete_sidebar(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( 0 === $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'sdotee' ) ) );
		}

		$delete_key = get_post_meta( $post_id, '_see_post_file_delete_key', true );

		if ( ! empty( $delete_key ) ) {
			$api_key = SEE_Helpers::get_api_key();
			if ( ! empty( $api_key ) ) {
				if ( str_starts_with( $delete_key, 'http' ) ) {
					$delete_url = $delete_key;
				} else {
					$base_url   = SEE_Helpers::get_api_base_url();
					$delete_url = rtrim( $base_url, '/' ) . '/file/delete/' . urlencode( $delete_key );
				}

				wp_remote_get( $delete_url, array(
					'headers' => array(
						'Authorization' => $api_key,
						'Accept'        => 'application/json',
					),
					'timeout' => 15,
				) );
			}
		}

		delete_post_meta( $post_id, '_see_post_file_url' );
		delete_post_meta( $post_id, '_see_post_file_name' );
		delete_post_meta( $post_id, '_see_post_file_delete_key' );

		wp_send_json_success( array(
			'message' => __( 'File deleted from S.EE successfully!', 'sdotee' ),
		) );
	}

	/**
	 * Add S.EE fields to attachment edit form.
	 *
	 * @param array    $form_fields Existing form fields.
	 * @param \WP_Post $post        Attachment post object.
	 * @return array Modified form fields.
	 */
	public function add_attachment_fields( array $form_fields, \WP_Post $post ): array {
		$file_url    = get_post_meta( $post->ID, '_see_file_url', true );
		$delete_key  = get_post_meta( $post->ID, '_see_file_delete_key', true );

		if ( ! empty( $file_url ) ) {
			$form_fields['see_file_url'] = array(
				'label' => __( 'S.EE URL', 'sdotee' ),
				'input' => 'html',
				'html'  => '<div class="see-attachment-field">'
					. '<a href="' . esc_url( $file_url ) . '" target="_blank">' . esc_html( $file_url ) . '</a>'
					. ' <button type="button" class="button button-small see-copy-btn" data-url="' . esc_attr( $file_url ) . '">'
					. esc_html__( 'Copy', 'sdotee' ) . '</button>'
					. ' <button type="button" class="button button-small see-delete-file-btn" data-attachment-id="' . esc_attr( $post->ID ) . '" data-delete-key="' . esc_attr( $delete_key ) . '">'
					. esc_html__( 'Delete from S.EE', 'sdotee' ) . '</button>'
					. '</div>',
			);
		} else {
			$form_fields['see_upload'] = array(
				'label' => __( 'S.EE', 'sdotee' ),
				'input' => 'html',
				'html'  => '<button type="button" class="button button-small see-upload-file-btn" data-attachment-id="' . esc_attr( $post->ID ) . '">'
					. esc_html__( 'Upload to S.EE', 'sdotee' ) . '</button>'
					. '<span class="see-upload-status" data-attachment-id="' . esc_attr( $post->ID ) . '"></span>',
			);
		}

		return $form_fields;
	}

	/**
	 * Replace the WordPress attachment URL with the S.EE URL if available.
	 *
	 * @param string $url           Original attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string Filtered URL.
	 */
	public function filter_attachment_url( string $url, int $attachment_id ): string {
		$see_url = get_post_meta( $attachment_id, '_see_file_url', true );
		if ( ! empty( $see_url ) ) {
			return $see_url;
		}
		return $url;
	}

	/**
	 * Auto-upload attachment to S.EE if enabled.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public function maybe_auto_upload( int $attachment_id ): void {
		if ( '1' !== get_option( 'see_auto_upload', '' ) ) {
			return;
		}

		// Don't auto-upload if already uploaded.
		$existing = get_post_meta( $attachment_id, '_see_file_url', true );
		if ( ! empty( $existing ) ) {
			return;
		}

		$this->upload_file( $attachment_id );
	}

	/**
	 * Upload a file to S.EE.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|WP_Error Result or error.
	 */
	public function upload_file( int $attachment_id ): array|\WP_Error {
		$client = SEE_Helpers::get_client();
		if ( null === $client ) {
			return new \WP_Error( 'see_no_client', __( 'S.EE client not configured.', 'sdotee' ) );
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new \WP_Error( 'see_no_file', __( 'Attachment file not found.', 'sdotee' ) );
		}

		$filename = basename( $file_path );

		try {
			$result = $client->file->upload( $file_path, $filename );

			SEE_Helpers::log_error( 'File upload response: ' . wp_json_encode( $result ) );

			$file_url   = $result['url'] ?? '';
			$delete_key = $result['delete'] ?? ( $result['hash'] ?? '' );

			if ( ! empty( $file_url ) ) {
				update_post_meta( $attachment_id, '_see_file_url', $file_url );
				update_post_meta( $attachment_id, '_see_file_delete_key', $delete_key );
			}

			return $result;
		} catch ( \See\Exception\SeeException $e ) {
			SEE_Helpers::log_error( 'File upload failed: ' . $e->getMessage() );
			return new \WP_Error( 'see_api_error', $e->getMessage() );
		}
	}

	/**
	 * AJAX: Upload file to S.EE.
	 */
	public function ajax_upload(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( 0 === $attachment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attachment ID.', 'sdotee' ) ) );
		}

		$result = $this->upload_file( $attachment_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'    => __( 'File uploaded to S.EE successfully!', 'sdotee' ),
			'file_url'   => $result['url'] ?? '',
			'delete_key' => $result['delete'] ?? ( $result['hash'] ?? '' ),
		) );
	}

	/**
	 * AJAX: Delete file from S.EE.
	 */
	public function ajax_delete(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$delete_key    = isset( $_POST['delete_key'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_key'] ) ) : '';
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

		if ( empty( $delete_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing delete key.', 'sdotee' ) ) );
		}

		$api_key = SEE_Helpers::get_api_key();
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'sdotee' ) ) );
		}

		// Build the delete URL. Handle both full URL and hash-only values.
		if ( str_starts_with( $delete_key, 'http' ) ) {
			// delete_key is already a full URL.
			$delete_url = $delete_key;
		} else {
			$base_url   = SEE_Helpers::get_api_base_url();
			$delete_url = rtrim( $base_url, '/' ) . '/file/delete/' . urlencode( $delete_key );
		}

		SEE_Helpers::log_error( 'File delete URL: ' . $delete_url );

		$response = wp_remote_get( $delete_url, array(
			'headers' => array(
				'Authorization' => $api_key,
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code || ! is_array( $body ) ) {
			$error_msg = $body['message'] ?? wp_remote_retrieve_body( $response );
			SEE_Helpers::log_error( 'File deletion failed: ' . $error_msg );
			wp_send_json_error( array( 'message' => $error_msg ) );
		}

		// Remove post meta.
		if ( $attachment_id > 0 ) {
			delete_post_meta( $attachment_id, '_see_file_url' );
			delete_post_meta( $attachment_id, '_see_file_delete_key' );
		}

		wp_send_json_success( array(
			'message' => __( 'File deleted from S.EE successfully!', 'sdotee' ),
		) );
	}

	/**
	 * AJAX: Upload a standalone file (from the management page).
	 *
	 * Accepts a file via $_FILES and uploads directly to S.EE.
	 */
	public function ajax_upload_standalone(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file provided.', 'sdotee' ) ) );
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- File data is validated below and sanitized via sanitize_file_name().

		// Validate upload.
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => __( 'File upload error.', 'sdotee' ) ) );
		}

		$client = SEE_Helpers::get_client();
		if ( null === $client ) {
			wp_send_json_error( array( 'message' => __( 'S.EE client not configured.', 'sdotee' ) ) );
		}

		$tmp_path = $file['tmp_name'];
		$filename = sanitize_file_name( $file['name'] );

		try {
			$result = $client->file->upload( $tmp_path, $filename );

			$file_url   = $result['url'] ?? '';
			$delete_key = $result['delete'] ?? ( $result['hash'] ?? '' );

			// Save to file history.
			if ( ! empty( $file_url ) ) {
				SEE_Helpers::add_history( 'see_file_history', array(
					'url'        => $file_url,
					'delete_key' => $delete_key,
					'filename'   => $filename,
				) );
			}

			wp_send_json_success( array(
				'message'    => __( 'File uploaded to S.EE successfully!', 'sdotee' ),
				'file_url'   => $file_url,
				'delete_key' => $delete_key,
			) );
		} catch ( \See\Exception\SeeException $e ) {
			SEE_Helpers::log_error( 'Standalone file upload failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Remove a file history entry.
	 */
	public function ajax_remove_history(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_id'] ) ) : '';

		if ( empty( $entry_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid entry.', 'sdotee' ) ) );
		}

		SEE_Helpers::remove_history( 'see_file_history', $entry_id );

		wp_send_json_success( array(
			'message' => __( 'History entry removed.', 'sdotee' ),
		) );
	}
}
