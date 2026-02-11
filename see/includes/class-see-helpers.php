<?php
/**
 * Helper functions for S.EE plugin.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SEE_Helpers
 *
 * Utility functions used across the plugin.
 */
class SEE_Helpers {

	/**
	 * Cached client instance.
	 *
	 * @var \See\Client|null
	 */
	private static ?\See\Client $client = null;

	/**
	 * Get an instance of the S.EE SDK client.
	 *
	 * @return \See\Client|null Client instance or null if not configured.
	 */
	public static function get_client(): ?\See\Client {
		if ( null !== self::$client ) {
			return self::$client;
		}

		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			return null;
		}

		$base_url = self::get_api_base_url();

		try {
			self::$client = new \See\Client( $api_key, $base_url );
			return self::$client;
		} catch ( \Exception $e ) {
			self::log_error( 'Failed to initialize S.EE client: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Get a fresh client instance with a specific API key (for testing).
	 *
	 * @param string $api_key The API key to use.
	 * @param string $base_url The base URL to use.
	 * @return \See\Client
	 */
	public static function get_test_client( string $api_key, string $base_url = '' ): \See\Client {
		if ( empty( $base_url ) ) {
			$base_url = self::get_api_base_url();
		}
		return new \See\Client( $api_key, $base_url );
	}

	/**
	 * Get the API key, supporting wp-config.php constant override.
	 *
	 * @return string Decrypted API key.
	 */
	public static function get_api_key(): string {
		// wp-config.php constant takes priority.
		if ( defined( 'SEE_API_KEY' ) && ! empty( SEE_API_KEY ) ) {
			return SEE_API_KEY;
		}

		$encrypted = get_option( 'see_api_key', '' );
		if ( empty( $encrypted ) ) {
			return '';
		}

		return self::decrypt( $encrypted );
	}

	/**
	 * Save encrypted API key.
	 *
	 * @param string $api_key Plain text API key.
	 */
	public static function save_api_key( string $api_key ): void {
		if ( empty( $api_key ) ) {
			update_option( 'see_api_key', '' );
			return;
		}
		update_option( 'see_api_key', self::encrypt( $api_key ) );
	}

	/**
	 * Get API base URL.
	 *
	 * @return string
	 */
	public static function get_api_base_url(): string {
		if ( defined( 'SEE_API_BASE_URL' ) && ! empty( SEE_API_BASE_URL ) ) {
			return SEE_API_BASE_URL;
		}
		$url = get_option( 'see_api_base_url', 'https://s.ee/api/v1/' );
		return ! empty( $url ) ? $url : 'https://s.ee/api/v1/';
	}

	/**
	 * Encrypt a string using OpenSSL.
	 *
	 * @param string $value Plain text value.
	 * @return string Encrypted value.
	 */
	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$key = self::get_encryption_key();

		if ( function_exists( 'openssl_encrypt' ) ) {
			$iv     = openssl_random_pseudo_bytes( 16 );
			$cipher = openssl_encrypt( $value, 'AES-256-CBC', $key, 0, $iv );
			if ( false === $cipher ) {
				return base64_encode( $value );
			}
			return base64_encode( $iv . '::' . $cipher );
		}

		// Fallback to base64.
		return base64_encode( $value );
	}

	/**
	 * Decrypt a string using OpenSSL.
	 *
	 * @param string $value Encrypted value.
	 * @return string Decrypted value.
	 */
	public static function decrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$key     = self::get_encryption_key();
		$decoded = base64_decode( $value, true );

		if ( false === $decoded ) {
			return $value;
		}

		if ( function_exists( 'openssl_decrypt' ) && str_contains( $decoded, '::' ) ) {
			$parts = explode( '::', $decoded, 2 );
			if ( 2 === count( $parts ) ) {
				$decrypted = openssl_decrypt( $parts[1], 'AES-256-CBC', $key, 0, $parts[0] );
				if ( false !== $decrypted ) {
					return $decrypted;
				}
			}
		}

		// Fallback: treat as base64 encoded.
		return $decoded;
	}

	/**
	 * Get the encryption key derived from WordPress salts.
	 *
	 * @return string
	 */
	private static function get_encryption_key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'see-default-key';
		return hash( 'sha256', $salt, true );
	}

	/**
	 * Get cached domains list.
	 *
	 * @param bool $force_refresh Whether to bypass cache.
	 * @return array List of domains.
	 */
	public static function get_domains( bool $force_refresh = false ): array {
		if ( ! $force_refresh ) {
			$cached = get_transient( 'see_domains_cache' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$client = self::get_client();
		if ( null === $client ) {
			return array();
		}

		try {
			$domains = $client->common->getDomains();
			set_transient( 'see_domains_cache', $domains, DAY_IN_SECONDS );
			return $domains;
		} catch ( \See\Exception\SeeException $e ) {
			self::log_error( 'Failed to fetch domains: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get cached file domains list.
	 *
	 * @param bool $force_refresh Whether to bypass cache.
	 * @return array List of file domains.
	 */
	public static function get_file_domains( bool $force_refresh = false ): array {
		if ( ! $force_refresh ) {
			$cached = get_transient( 'see_file_domains_cache' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$client = self::get_client();
		if ( null === $client ) {
			return array();
		}

		try {
			$domains = $client->file->getDomains();
			set_transient( 'see_file_domains_cache', $domains, DAY_IN_SECONDS );
			return $domains;
		} catch ( \See\Exception\SeeException $e ) {
			self::log_error( 'Failed to fetch file domains: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get cached text domains list.
	 *
	 * Uses wp_remote_get since the SDK does not have a text->getDomains() method.
	 * API endpoint: GET {base_url}/text/domains
	 *
	 * @param bool $force_refresh Whether to bypass cache.
	 * @return array List of text domains.
	 */
	public static function get_text_domains( bool $force_refresh = false ): array {
		if ( ! $force_refresh ) {
			$cached = get_transient( 'see_text_domains_cache' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$api_key = self::get_api_key();
		if ( empty( $api_key ) ) {
			return array();
		}

		$base_url = self::get_api_base_url();
		$url      = rtrim( $base_url, '/' ) . '/text/domains';

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => $api_key,
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Failed to fetch text domains: ' . $response->get_error_message() );
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['data']['domains'] ) ) {
			self::log_error( 'Invalid text domains response.' );
			return array();
		}

		$domains = $body['data']['domains'];
		set_transient( 'see_text_domains_cache', $domains, DAY_IN_SECONDS );
		return $domains;
	}

	/**
	 * Fetch text domains with explicit credentials (for test connection).
	 *
	 * @param string $api_key  API key.
	 * @param string $base_url API base URL.
	 * @return array List of text domains.
	 */
	public static function fetch_text_domains( string $api_key, string $base_url = '' ): array {
		if ( empty( $base_url ) ) {
			$base_url = self::get_api_base_url();
		}

		$url = rtrim( $base_url, '/' ) . '/text/domains';

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => $api_key,
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			self::log_error( 'Failed to fetch text domains: ' . $response->get_error_message() );
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['data']['domains'] ) ) {
			return array();
		}

		return $body['data']['domains'];
	}

	/**
	 * Log an error to the WordPress debug log.
	 *
	 * @param string $message Error message.
	 */
	public static function log_error( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional debug logging when WP_DEBUG is enabled.
			error_log( '[S.EE] ' . $message );
		}
	}

	/**
	 * Reset the cached client (used when settings change).
	 */
	public static function reset_client(): void {
		self::$client = null;
	}

	/**
	 * Format a short URL from domain and slug.
	 *
	 * @param string $domain Domain name.
	 * @param string $slug   URL slug.
	 * @return string Full short URL.
	 */
	public static function format_short_url( string $domain, string $slug ): string {
		return 'https://' . $domain . '/' . $slug;
	}

	/**
	 * Add an entry to a history option.
	 *
	 * @param string $option_name Option name (see_text_history or see_file_history).
	 * @param array  $entry       History entry data.
	 * @param int    $max_entries Maximum entries to keep.
	 */
	public static function add_history( string $option_name, array $entry, int $max_entries = 100 ): void {
		$history = get_option( $option_name, array() );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$entry['id']         = uniqid( 'see_', true );
		$entry['created_at'] = current_time( 'mysql' );

		array_unshift( $history, $entry );

		// Trim to max entries.
		if ( count( $history ) > $max_entries ) {
			$history = array_slice( $history, 0, $max_entries );
		}

		update_option( $option_name, $history );
	}

	/**
	 * Get history entries.
	 *
	 * @param string $option_name Option name.
	 * @return array History entries.
	 */
	public static function get_history( string $option_name ): array {
		$history = get_option( $option_name, array() );
		return is_array( $history ) ? $history : array();
	}

	/**
	 * Remove a history entry by ID.
	 *
	 * @param string $option_name Option name.
	 * @param string $entry_id    Entry ID to remove.
	 */
	public static function remove_history( string $option_name, string $entry_id ): void {
		$history = get_option( $option_name, array() );
		if ( ! is_array( $history ) ) {
			return;
		}

		$history = array_filter( $history, function ( $entry ) use ( $entry_id ) {
			return ( $entry['id'] ?? '' ) !== $entry_id;
		} );

		update_option( $option_name, array_values( $history ) );
	}
}
