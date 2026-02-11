<?php
/**
 * Settings page using WordPress Settings API.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_Settings
 *
 * Handles the plugin settings page and AJAX callbacks for testing connection / fetching domains.
 */
class SEE_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_see_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_see_fetch_domains', array( $this, 'ajax_fetch_domains' ) );
	}

	/**
	 * Register all settings fields using WP Settings API.
	 */
	public function register_settings(): void {
		// Register settings.
		register_setting( 'see_options', 'see_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
		) );
		register_setting( 'see_options', 'see_api_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://s.ee/api/v1/',
		) );
		register_setting( 'see_options', 'see_default_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'see_options', 'see_default_file_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'see_options', 'see_default_text_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'see_options', 'see_auto_shorten', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'see_options', 'see_auto_upload', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		// API section.
		add_settings_section(
			'see_api_section',
			__( 'API Configuration', 'see' ),
			array( $this, 'render_api_section' ),
			'see-settings'
		);

		add_settings_field(
			'see_api_key',
			__( 'API Key', 'see' ),
			array( $this, 'render_api_key_field' ),
			'see-settings',
			'see_api_section'
		);

		add_settings_field(
			'see_api_base_url',
			__( 'API Base URL', 'see' ),
			array( $this, 'render_api_base_url_field' ),
			'see-settings',
			'see_api_section'
		);

		// Defaults section.
		add_settings_section(
			'see_defaults_section',
			__( 'Default Settings', 'see' ),
			array( $this, 'render_defaults_section' ),
			'see-settings'
		);

		add_settings_field(
			'see_default_domain',
			__( 'Default Short URL Domain', 'see' ),
			array( $this, 'render_default_domain_field' ),
			'see-settings',
			'see_defaults_section'
		);

		add_settings_field(
			'see_default_text_domain',
			__( 'Default Text Share Domain', 'see' ),
			array( $this, 'render_default_text_domain_field' ),
			'see-settings',
			'see_defaults_section'
		);

		add_settings_field(
			'see_default_file_domain',
			__( 'Default File Upload Domain', 'see' ),
			array( $this, 'render_default_file_domain_field' ),
			'see-settings',
			'see_defaults_section'
		);

		// Automation section.
		add_settings_section(
			'see_automation_section',
			__( 'Automation', 'see' ),
			array( $this, 'render_automation_section' ),
			'see-settings'
		);

		add_settings_field(
			'see_auto_shorten',
			__( 'Auto Shorten URLs', 'see' ),
			array( $this, 'render_auto_shorten_field' ),
			'see-settings',
			'see_automation_section'
		);

		add_settings_field(
			'see_auto_upload',
			__( 'Auto Upload Media', 'see' ),
			array( $this, 'render_auto_upload_field' ),
			'see-settings',
			'see_automation_section'
		);
	}

	/**
	 * Sanitize API key before saving - encrypt it.
	 *
	 * @param string $value Raw API key input.
	 * @return string Encrypted API key.
	 */
	public function sanitize_api_key( string $value ): string {
		$value = sanitize_text_field( $value );

		// If the value is the placeholder, keep the old value.
		if ( '••••••••' === $value || empty( $value ) ) {
			$old = get_option( 'see_api_key', '' );
			if ( empty( $value ) ) {
				// Clear domains cache when key is removed.
				delete_transient( 'see_domains_cache' );
				delete_transient( 'see_file_domains_cache' );
				SEE_Helpers::reset_client();
				return '';
			}
			return $old;
		}

		// Reset client and domains cache when key changes.
		delete_transient( 'see_domains_cache' );
		delete_transient( 'see_file_domains_cache' );
		SEE_Helpers::reset_client();

		return SEE_Helpers::encrypt( $value );
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section(): void {
		echo '<p>' . esc_html__( 'Configure your S.EE API credentials. You can get your API key from your S.EE account settings.', 'see' ) . '</p>';
		if ( defined( 'SEE_API_KEY' ) ) {
			echo '<p class="description"><strong>' . esc_html__( 'Note: API key is defined in wp-config.php via SEE_API_KEY constant and takes priority over the setting below.', 'see' ) . '</strong></p>';
		}
	}

	/**
	 * Render defaults section description.
	 */
	public function render_defaults_section(): void {
		echo '<p>' . esc_html__( 'Choose default domains for short URLs, text sharing, and file uploads. Domain lists are cached and will not trigger API requests on page load.', 'see' ) . '</p>';
		echo '<p>';
		echo '<button type="button" id="see-refresh-domains" class="button button-secondary">'
			. esc_html__( 'Refresh Domains', 'see' ) . '</button> ';
		echo '<span id="see-domains-status"></span> ';
		echo '<span class="description">' . esc_html__( 'Click to fetch the latest domain lists from your S.EE account.', 'see' ) . '</span>';
		echo '</p>';
	}

	/**
	 * Render automation section description.
	 */
	public function render_automation_section(): void {
		echo '<p>' . esc_html__( 'Enable automatic actions when publishing posts or uploading media.', 'see' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field(): void {
		$has_key  = ! empty( SEE_Helpers::get_api_key() );
		$disabled = defined( 'SEE_API_KEY' ) ? 'disabled' : '';
		$value    = $has_key ? '••••••••' : '';
		?>
		<div class="see-api-key-wrap">
			<input type="password"
				   id="see_api_key"
				   name="see_api_key"
				   value="<?php echo esc_attr( $value ); ?>"
				   class="regular-text"
				   autocomplete="off"
				   <?php echo esc_attr( $disabled ); ?>
			/>
			<button type="button" id="see-toggle-key" class="button button-secondary">
				<?php esc_html_e( 'Show', 'see' ); ?>
			</button>
			<button type="button" id="see-test-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'see' ); ?>
			</button>
			<span id="see-connection-status"></span>
		</div>
		<p class="description">
			<?php
			printf(
				/* translators: %s: URL to S.EE developer page */
				esc_html__( 'Enter your S.EE API key. It will be stored encrypted. %s', 'see' ),
				'<a href="https://s.ee/user/developers/" target="_blank">' . esc_html__( 'Get API Token', 'see' ) . '</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render API base URL field.
	 */
	public function render_api_base_url_field(): void {
		$value    = SEE_Helpers::get_api_base_url();
		$disabled = defined( 'SEE_API_BASE_URL' ) ? 'disabled' : '';
		?>
		<input type="url"
			   id="see_api_base_url"
			   name="see_api_base_url"
			   value="<?php echo esc_url( $value ); ?>"
			   class="regular-text"
			   placeholder="https://s.ee/api/v1/"
			   <?php echo esc_attr( $disabled ); ?>
		/>
		<p class="description">
			<?php esc_html_e( 'Default: https://s.ee/api/v1/', 'see' ); ?>
		</p>
		<?php
	}

	/**
	 * Render default domain dropdown.
	 */
	public function render_default_domain_field(): void {
		$current = get_option( 'see_default_domain', '' );
		$domains = get_transient( 'see_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="see_default_domain" name="see_default_domain" class="see-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'see' ); ?></option>
			<?php foreach ( $domains as $domain ) : ?>
				<option value="<?php echo esc_attr( $domain ); ?>" <?php selected( $current, $domain ); ?>>
					<?php echo esc_html( $domain ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render default text domain dropdown.
	 */
	public function render_default_text_domain_field(): void {
		$current = get_option( 'see_default_text_domain', '' );
		$domains = get_transient( 'see_text_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="see_default_text_domain" name="see_default_text_domain" class="see-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'see' ); ?></option>
			<?php foreach ( $domains as $domain ) : ?>
				<option value="<?php echo esc_attr( $domain ); ?>" <?php selected( $current, $domain ); ?>>
					<?php echo esc_html( $domain ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render default file domain dropdown.
	 */
	public function render_default_file_domain_field(): void {
		$current = get_option( 'see_default_file_domain', '' );
		$domains = get_transient( 'see_file_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="see_default_file_domain" name="see_default_file_domain" class="see-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'see' ); ?></option>
			<?php foreach ( $domains as $domain ) : ?>
				<option value="<?php echo esc_attr( $domain ); ?>" <?php selected( $current, $domain ); ?>>
					<?php echo esc_html( $domain ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render auto shorten checkbox.
	 */
	public function render_auto_shorten_field(): void {
		$value = get_option( 'see_auto_shorten', '' );
		?>
		<label>
			<input type="checkbox"
				   name="see_auto_shorten"
				   value="1"
				   <?php checked( $value, '1' ); ?>
			/>
			<?php esc_html_e( 'Automatically generate a short URL when a post or page is published.', 'see' ); ?>
		</label>
		<?php
	}

	/**
	 * Render auto upload checkbox.
	 */
	public function render_auto_upload_field(): void {
		$value = get_option( 'see_auto_upload', '' );
		?>
		<label>
			<input type="checkbox"
				   name="see_auto_upload"
				   value="1"
				   <?php checked( $value, '1' ); ?>
			/>
			<?php esc_html_e( 'Automatically upload media files to S.EE when added to the Media Library.', 'see' ); ?>
		</label>
		<?php
	}

	/**
	 * AJAX: Test API connection.
	 */
	public function ajax_test_connection(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'see' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		// If placeholder, use stored key.
		if ( '••••••••' === $api_key || empty( $api_key ) ) {
			$api_key = SEE_Helpers::get_api_key();
		}

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter an API key.', 'see' ) ) );
		}

		$base_url = isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
		if ( empty( $base_url ) ) {
			$base_url = SEE_Helpers::get_api_base_url();
		}

		try {
			$client       = SEE_Helpers::get_test_client( $api_key, $base_url );
			$domains      = $client->common->getDomains();
			$file_domains = $client->file->getDomains();
			$text_domains = SEE_Helpers::fetch_text_domains( $api_key, $base_url );
			wp_send_json_success( array(
				'message'      => __( 'Connection successful!', 'see' ),
				'domains'      => $domains,
				'file_domains' => $file_domains,
				'text_domains' => $text_domains,
			) );
		} catch ( \See\Exception\SeeException $e ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'see' ),
					$e->getMessage()
				),
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Unexpected error: %s', 'see' ),
					$e->getMessage()
				),
			) );
		}
	}

	/**
	 * AJAX: Fetch available domains.
	 */
	public function ajax_fetch_domains(): void {
		check_ajax_referer( 'see_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'see' ) ) );
		}

		$domains      = SEE_Helpers::get_domains( true );
		$file_domains = SEE_Helpers::get_file_domains( true );
		$text_domains = SEE_Helpers::get_text_domains( true );

		if ( empty( $domains ) && empty( $file_domains ) && empty( $text_domains ) ) {
			wp_send_json_error( array(
				'message' => __( 'No domains found. Please check your API key.', 'see' ),
			) );
		}

		wp_send_json_success( array(
			'domains'      => $domains,
			'file_domains' => $file_domains,
			'text_domains' => $text_domains,
			'message'      => __( 'Domains refreshed.', 'see' ),
		) );
	}
}
