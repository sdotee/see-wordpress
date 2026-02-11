<?php
/**
 * Settings page template.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap see-settings-page">
	<h1><?php esc_html_e( 'S.EE Settings', 'sdotee' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'see_options' );
		do_settings_sections( 'see-settings' );
		submit_button();
		?>
	</form>
</div>
