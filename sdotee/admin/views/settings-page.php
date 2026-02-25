<?php
/**
 * Settings page template.
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sdotee-settings-page">
	<h1><?php esc_html_e( 'S.EE Settings', 'sdotee' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'sdotee_options' );
		do_settings_sections( 'sdotee-settings' );
		submit_button();
		?>
	</form>
</div>
