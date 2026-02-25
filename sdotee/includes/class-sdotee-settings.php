<?php
/**
 * Settings page using WordPress Settings API.
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SDOTEE_Settings
 *
 * Handles the plugin settings page and AJAX callbacks for testing connection / fetching domains.
 */
class SDOTEE_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_sdotee_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_sdotee_fetch_domains', array( $this, 'ajax_fetch_domains' ) );
	}

	/**
	 * Register all settings fields using WP Settings API.
	 */
	public function register_settings(): void {
		// Register settings.
		register_setting( 'sdotee_options', 'sdotee_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
		) );
		register_setting( 'sdotee_options', 'sdotee_api_base_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'https://s.ee/api/v1/',
		) );
		register_setting( 'sdotee_options', 'sdotee_default_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'sdotee_options', 'sdotee_default_file_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'sdotee_options', 'sdotee_default_text_domain', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'sdotee_options', 'sdotee_auto_shorten', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'sdotee_options', 'sdotee_auto_upload', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		// API section.
		add_settings_section(
			'sdotee_api_section',
			__( 'API Configuration', 'sdotee' ),
			array( $this, 'render_api_section' ),
			'sdotee-settings'
		);

		add_settings_field(
			'sdotee_api_key',
			__( 'API Key', 'sdotee' ),
			array( $this, 'render_api_key_field' ),
			'sdotee-settings',
			'sdotee_api_section'
		);

		add_settings_field(
			'sdotee_api_base_url',
			__( 'API Base URL', 'sdotee' ),
			array( $this, 'render_api_base_url_field' ),
			'sdotee-settings',
			'sdotee_api_section'
		);

		// Defaults section.
		add_settings_section(
			'sdotee_defaults_section',
			__( 'Default Settings', 'sdotee' ),
			array( $this, 'render_defaults_section' ),
			'sdotee-settings'
		);

		add_settings_field(
			'sdotee_default_domain',
			__( 'Default Short URL Domain', 'sdotee' ),
			array( $this, 'render_default_domain_field' ),
			'sdotee-settings',
			'sdotee_defaults_section'
		);

		add_settings_field(
			'sdotee_default_text_domain',
			__( 'Default Text Share Domain', 'sdotee' ),
			array( $this, 'render_default_text_domain_field' ),
			'sdotee-settings',
			'sdotee_defaults_section'
		);

		add_settings_field(
			'sdotee_default_file_domain',
			__( 'Default File Upload Domain', 'sdotee' ),
			array( $this, 'render_default_file_domain_field' ),
			'sdotee-settings',
			'sdotee_defaults_section'
		);

		// Automation section.
		add_settings_section(
			'sdotee_automation_section',
			__( 'Automation', 'sdotee' ),
			array( $this, 'render_automation_section' ),
			'sdotee-settings'
		);

		add_settings_field(
			'sdotee_auto_shorten',
			__( 'Auto Shorten URLs', 'sdotee' ),
			array( $this, 'render_auto_shorten_field' ),
			'sdotee-settings',
			'sdotee_automation_section'
		);

		add_settings_field(
			'sdotee_auto_upload',
			__( 'Auto Upload Media', 'sdotee' ),
			array( $this, 'render_auto_upload_field' ),
			'sdotee-settings',
			'sdotee_automation_section'
		);
	}

	/**
	 * Sanitize API key before saving - encrypt it.
	 *
	 * @param mixed $value Raw API key input.
	 * @return string Encrypted API key.
	 */
	public function sanitize_api_key( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return get_option( 'sdotee_api_key', '' );
		}
		$value = trim( (string) $value );

		// If the value is the placeholder, keep the old value.
		if ( '••••••••' === $value || empty( $value ) ) {
			$old = get_option( 'sdotee_api_key', '' );
			if ( empty( $value ) ) {
				// Clear domains cache when key is removed.
				delete_transient( 'sdotee_domains_cache' );
				delete_transient( 'sdotee_file_domains_cache' );
				SDOTEE_Helpers::reset_client();
				return '';
			}
			return $old;
		}

		// Reset client and domains cache when key changes.
		delete_transient( 'sdotee_domains_cache' );
		delete_transient( 'sdotee_file_domains_cache' );
		SDOTEE_Helpers::reset_client();

		return SDOTEE_Helpers::encrypt( $value );
	}

	/**
	 * Render API section description.
	 */
	public function render_api_section(): void {
		echo '<p>' . esc_html__( 'Configure your S.EE API credentials. You can get your API key from your S.EE account settings.', 'sdotee' ) . '</p>';
		if ( defined( 'SDOTEE_API_KEY' ) ) {
			echo '<p class="description"><strong>' . esc_html__( 'Note: API key is defined in wp-config.php via SDOTEE_API_KEY constant and takes priority over the setting below.', 'sdotee' ) . '</strong></p>';
		}
	}

	/**
	 * Render defaults section description.
	 */
	public function render_defaults_section(): void {
		echo '<p>' . esc_html__( 'Choose default domains for short URLs, text sharing, and file uploads. Domain lists are cached and will not trigger API requests on page load.', 'sdotee' ) . '</p>';
		echo '<p>';
		echo '<button type="button" id="sdotee-refresh-domains" class="button button-secondary">'
			. esc_html__( 'Refresh Domains', 'sdotee' ) . '</button> ';
		echo '<span id="sdotee-domains-status"></span> ';
		echo '<span class="description">' . esc_html__( 'Click to fetch the latest domain lists from your S.EE account.', 'sdotee' ) . '</span>';
		echo '</p>';
	}

	/**
	 * Render automation section description.
	 */
	public function render_automation_section(): void {
		echo '<p>' . esc_html__( 'Enable automatic actions when publishing posts or uploading media.', 'sdotee' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field(): void {
		$has_key  = ! empty( SDOTEE_Helpers::get_api_key() );
		$disabled = defined( 'SDOTEE_API_KEY' ) ? 'disabled' : '';
		$value    = $has_key ? '••••••••' : '';
		?>
		<div class="sdotee-api-key-wrap">
			<input type="password"
				   id="sdotee_api_key"
				   name="sdotee_api_key"
				   value="<?php echo esc_attr( $value ); ?>"
				   class="regular-text"
				   autocomplete="off"
				   <?php echo esc_attr( $disabled ); ?>
			/>
			<button type="button" id="sdotee-toggle-key" class="button button-secondary">
				<?php esc_html_e( 'Show', 'sdotee' ); ?>
			</button>
			<button type="button" id="sdotee-test-connection" class="button button-secondary">
				<?php esc_html_e( 'Test Connection', 'sdotee' ); ?>
			</button>
			<span id="sdotee-connection-status"></span>
		</div>
		<p class="description">
			<?php
			printf(
				/* translators: %s: URL to S.EE developer page */
				esc_html__( 'Enter your S.EE API key. It will be stored encrypted. %s', 'sdotee' ),
				'<a href="https://s.ee/user/developers/" target="_blank">' . esc_html__( 'Get API Token', 'sdotee' ) . '</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render API base URL field.
	 */
	public function render_api_base_url_field(): void {
		$value    = SDOTEE_Helpers::get_api_base_url();
		$disabled = defined( 'SDOTEE_API_BASE_URL' ) ? 'disabled' : '';
		?>
		<input type="url"
			   id="sdotee_api_base_url"
			   name="sdotee_api_base_url"
			   value="<?php echo esc_url( $value ); ?>"
			   class="regular-text"
			   placeholder="https://s.ee/api/v1/"
			   <?php echo esc_attr( $disabled ); ?>
		/>
		<p class="description">
			<?php esc_html_e( 'Default: https://s.ee/api/v1/', 'sdotee' ); ?>
		</p>
		<?php
	}

	/**
	 * Render default domain dropdown.
	 */
	public function render_default_domain_field(): void {
		$current = get_option( 'sdotee_default_domain', '' );
		$domains = get_transient( 'sdotee_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="sdotee_default_domain" name="sdotee_default_domain" class="sdotee-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'sdotee' ); ?></option>
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
		$current = get_option( 'sdotee_default_text_domain', '' );
		$domains = get_transient( 'sdotee_text_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="sdotee_default_text_domain" name="sdotee_default_text_domain" class="sdotee-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'sdotee' ); ?></option>
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
		$current = get_option( 'sdotee_default_file_domain', '' );
		$domains = get_transient( 'sdotee_file_domains_cache' );
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}
		?>
		<select id="sdotee_default_file_domain" name="sdotee_default_file_domain" class="sdotee-domain-select">
			<option value=""><?php esc_html_e( '— Select Domain —', 'sdotee' ); ?></option>
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
		$value = get_option( 'sdotee_auto_shorten', '' );
		?>
		<label>
			<input type="checkbox"
				   name="sdotee_auto_shorten"
				   value="1"
				   <?php checked( $value, '1' ); ?>
			/>
			<?php esc_html_e( 'Automatically generate a short URL when a post or page is published.', 'sdotee' ); ?>
		</label>
		<?php
	}

	/**
	 * Render auto upload checkbox.
	 */
	public function render_auto_upload_field(): void {
		$value = get_option( 'sdotee_auto_upload', '' );
		?>
		<label>
			<input type="checkbox"
				   name="sdotee_auto_upload"
				   value="1"
				   <?php checked( $value, '1' ); ?>
			/>
			<?php esc_html_e( 'Automatically upload media files to S.EE when added to the Media Library.', 'sdotee' ); ?>
		</label>
		<?php
	}

	/**
	 * AJAX: Test API connection.
	 */
	public function ajax_test_connection(): void {
		check_ajax_referer( 'sdotee_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

		// If placeholder, use stored key.
		if ( '••••••••' === $api_key || empty( $api_key ) ) {
			$api_key = SDOTEE_Helpers::get_api_key();
		}

		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter an API key.', 'sdotee' ) ) );
		}

		$base_url = isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
		if ( empty( $base_url ) ) {
			$base_url = SDOTEE_Helpers::get_api_base_url();
		}

		try {
			$client       = SDOTEE_Helpers::get_test_client( $api_key, $base_url );
			$domains      = $client->common->getDomains();
			$file_domains = $client->file->getDomains();
			$text_domains = SDOTEE_Helpers::fetch_text_domains( $api_key, $base_url );
			wp_send_json_success( array(
				'message'      => __( 'Connection successful!', 'sdotee' ),
				'domains'      => $domains,
				'file_domains' => $file_domains,
				'text_domains' => $text_domains,
			) );
		} catch ( \See\Exception\SeeException $e ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'sdotee' ),
					$e->getMessage()
				),
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Unexpected error: %s', 'sdotee' ),
					$e->getMessage()
				),
			) );
		}
	}

	/**
	 * AJAX: Fetch available domains.
	 */
	public function ajax_fetch_domains(): void {
		check_ajax_referer( 'sdotee_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sdotee' ) ) );
		}

		$domains      = SDOTEE_Helpers::get_domains( true );
		$file_domains = SDOTEE_Helpers::get_file_domains( true );
		$text_domains = SDOTEE_Helpers::get_text_domains( true );

		if ( empty( $domains ) && empty( $file_domains ) && empty( $text_domains ) ) {
			wp_send_json_error( array(
				'message' => __( 'No domains found. Please check your API key.', 'sdotee' ),
			) );
		}

		wp_send_json_success( array(
			'domains'      => $domains,
			'file_domains' => $file_domains,
			'text_domains' => $text_domains,
			'message'      => __( 'Domains refreshed.', 'sdotee' ),
		) );
	}
}
