<?php
/**
 * Short URL meta box template.
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sdotee_short_url = get_post_meta( $post->ID, '_sdotee_short_url', true );
$sdotee_slug      = get_post_meta( $post->ID, '_sdotee_short_slug', true );
$sdotee_domain    = get_post_meta( $post->ID, '_sdotee_short_domain', true );
$sdotee_domains   = SDOTEE_Helpers::get_domains();
$sdotee_default   = get_option( 'sdotee_default_domain', '' );
?>
<div class="sdotee-shorturl-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
	<?php if ( ! empty( $sdotee_short_url ) ) : ?>
		<div class="sdotee-shorturl-result">
			<p class="sdotee-shorturl-display">
				<a href="<?php echo esc_url( $sdotee_short_url ); ?>" target="_blank" class="sdotee-short-url-link">
					<?php echo esc_html( $sdotee_short_url ); ?>
				</a>
			</p>
			<div class="sdotee-shorturl-actions">
				<button type="button" class="button button-small sdotee-copy-btn" data-url="<?php echo esc_attr( $sdotee_short_url ); ?>">
					<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'sdotee' ); ?>
				</button>
				<button type="button" class="button button-small sdotee-delete-shorturl-btn">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'sdotee' ); ?>
				</button>
			</div>
		</div>
	<?php else : ?>
		<div class="sdotee-shorturl-form">
			<p>
				<label for="sdotee-shorturl-domain"><?php esc_html_e( 'Domain:', 'sdotee' ); ?></label>
				<select id="sdotee-shorturl-domain" class="widefat">
					<?php foreach ( $sdotee_domains as $sdotee_d ) : ?>
						<option value="<?php echo esc_attr( $sdotee_d ); ?>" <?php selected( $sdotee_default, $sdotee_d ); ?>>
							<?php echo esc_html( $sdotee_d ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="sdotee-shorturl-slug"><?php esc_html_e( 'Custom slug (optional):', 'sdotee' ); ?></label>
				<input type="text" id="sdotee-shorturl-slug" class="widefat" placeholder="<?php esc_attr_e( 'Leave blank for auto-generated', 'sdotee' ); ?>" />
			</p>
			<p>
				<button type="button" class="button button-primary sdotee-generate-shorturl-btn">
					<?php esc_html_e( 'Generate Short URL', 'sdotee' ); ?>
				</button>
				<span class="sdotee-shorturl-status"></span>
			</p>
		</div>
	<?php endif; ?>
</div>
