<?php
/**
 * Short URL meta box template.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$short_url = get_post_meta( $post->ID, '_see_short_url', true );
$slug      = get_post_meta( $post->ID, '_see_short_slug', true );
$domain    = get_post_meta( $post->ID, '_see_short_domain', true );
$domains   = SEE_Helpers::get_domains();
$default   = get_option( 'see_default_domain', '' );
?>
<div class="see-shorturl-metabox" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
	<?php if ( ! empty( $short_url ) ) : ?>
		<div class="see-shorturl-result">
			<p class="see-shorturl-display">
				<a href="<?php echo esc_url( $short_url ); ?>" target="_blank" class="see-short-url-link">
					<?php echo esc_html( $short_url ); ?>
				</a>
			</p>
			<div class="see-shorturl-actions">
				<button type="button" class="button button-small see-copy-btn" data-url="<?php echo esc_attr( $short_url ); ?>">
					<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'see' ); ?>
				</button>
				<button type="button" class="button button-small see-delete-shorturl-btn">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'see' ); ?>
				</button>
			</div>
		</div>
	<?php else : ?>
		<div class="see-shorturl-form">
			<p>
				<label for="see-shorturl-domain"><?php esc_html_e( 'Domain:', 'see' ); ?></label>
				<select id="see-shorturl-domain" class="widefat">
					<?php foreach ( $domains as $d ) : ?>
						<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $default, $d ); ?>>
							<?php echo esc_html( $d ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="see-shorturl-slug"><?php esc_html_e( 'Custom slug (optional):', 'see' ); ?></label>
				<input type="text" id="see-shorturl-slug" class="widefat" placeholder="<?php esc_attr_e( 'Leave blank for auto-generated', 'see' ); ?>" />
			</p>
			<p>
				<button type="button" class="button button-primary see-generate-shorturl-btn">
					<?php esc_html_e( 'Generate Short URL', 'see' ); ?>
				</button>
				<span class="see-shorturl-status"></span>
			</p>
		</div>
	<?php endif; ?>
</div>
