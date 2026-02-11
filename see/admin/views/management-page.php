<?php
/**
 * Management page template.
 *
 * @package SEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_history = SEE_Helpers::get_history( 'see_text_history' );
$file_history = SEE_Helpers::get_history( 'see_file_history' );
?>
<div class="wrap see-management-page">
	<h1><?php esc_html_e( 'S.EE Management', 'see' ); ?></h1>

	<div class="see-management-tabs">
		<nav class="nav-tab-wrapper">
			<a href="#see-tab-text" class="nav-tab nav-tab-active" data-tab="see-tab-text">
				<?php esc_html_e( 'Text Share', 'see' ); ?>
			</a>
			<a href="#see-tab-file" class="nav-tab" data-tab="see-tab-file">
				<?php esc_html_e( 'File Upload', 'see' ); ?>
			</a>
		</nav>

		<!-- Text Share Tab -->
		<div id="see-tab-text" class="see-tab-content see-tab-active">
			<div class="see-text-share-standalone">
				<h2><?php esc_html_e( 'Share Text', 'see' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="see-mgmt-text-title"><?php esc_html_e( 'Title', 'see' ); ?></label>
						</th>
						<td>
							<input type="text" id="see-mgmt-text-title" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="see-mgmt-text-type"><?php esc_html_e( 'Type', 'see' ); ?></label>
						</th>
						<td>
							<select id="see-mgmt-text-type">
								<option value="plain_text"><?php esc_html_e( 'Plain Text', 'see' ); ?></option>
								<option value="markdown"><?php esc_html_e( 'Markdown', 'see' ); ?></option>
								<option value="source_code"><?php esc_html_e( 'Source Code', 'see' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="see-mgmt-text-content"><?php esc_html_e( 'Content', 'see' ); ?></label>
						</th>
						<td>
							<textarea id="see-mgmt-text-content" rows="10" class="large-text"></textarea>
						</td>
					</tr>
				</table>
				<p>
					<button type="button" id="see-mgmt-create-text" class="button button-primary">
						<?php esc_html_e( 'Share Text', 'see' ); ?>
					</button>
					<span id="see-mgmt-text-status"></span>
				</p>
				<div id="see-mgmt-text-result" class="see-result-box" style="display:none;">
					<p>
						<strong><?php esc_html_e( 'Shared URL:', 'see' ); ?></strong>
						<a href="" id="see-mgmt-text-url" target="_blank"></a>
						<button type="button" class="button button-small see-copy-btn" data-url="">
							<?php esc_html_e( 'Copy', 'see' ); ?>
						</button>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $text_history ) ) : ?>
			<hr />
			<h2><?php esc_html_e( 'Text Share History', 'see' ); ?></h2>
			<table class="wp-list-table widefat fixed striped see-history-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'see' ); ?></th>
						<th><?php esc_html_e( 'Type', 'see' ); ?></th>
						<th><?php esc_html_e( 'URL', 'see' ); ?></th>
						<th><?php esc_html_e( 'Date', 'see' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'see' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $text_history as $entry ) : ?>
					<tr id="see-text-row-<?php echo esc_attr( $entry['id'] ); ?>">
						<td><?php echo esc_html( $entry['title'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $entry['text_type'] ?? 'plain_text' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $entry['url'] ); ?>" target="_blank">
								<?php echo esc_html( $entry['url'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $entry['created_at'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small see-copy-btn" data-url="<?php echo esc_attr( $entry['url'] ); ?>">
								<?php esc_html_e( 'Copy', 'see' ); ?>
							</button>
							<button type="button" class="button button-small see-remove-history-btn"
									data-action="see_remove_text_history"
									data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>">
								<?php esc_html_e( 'Remove', 'see' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

		<!-- File Upload Tab -->
		<div id="see-tab-file" class="see-tab-content" style="display:none;">
			<div class="see-file-upload-standalone">
				<h2><?php esc_html_e( 'Upload File', 'see' ); ?></h2>
				<div id="see-file-dropzone" class="see-dropzone">
					<p><?php esc_html_e( 'Drag & drop files here, or click to select files.', 'see' ); ?></p>
					<input type="file" id="see-file-input" style="display:none;" />
					<button type="button" class="button button-secondary" id="see-file-browse">
						<?php esc_html_e( 'Browse Files', 'see' ); ?>
					</button>
				</div>
				<div id="see-file-upload-status"></div>
				<div id="see-file-upload-result" class="see-result-box" style="display:none;">
					<p>
						<strong><?php esc_html_e( 'File URL:', 'see' ); ?></strong>
						<a href="" id="see-file-result-url" target="_blank"></a>
						<button type="button" class="button button-small see-copy-btn" data-url="">
							<?php esc_html_e( 'Copy', 'see' ); ?>
						</button>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $file_history ) ) : ?>
			<hr />
			<h2><?php esc_html_e( 'File Upload History', 'see' ); ?></h2>
			<table class="wp-list-table widefat fixed striped see-history-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Filename', 'see' ); ?></th>
						<th><?php esc_html_e( 'URL', 'see' ); ?></th>
						<th><?php esc_html_e( 'Date', 'see' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'see' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $file_history as $entry ) : ?>
					<tr id="see-file-row-<?php echo esc_attr( $entry['id'] ); ?>">
						<td><?php echo esc_html( $entry['filename'] ?? '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $entry['url'] ); ?>" target="_blank">
								<?php echo esc_html( $entry['url'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $entry['created_at'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small see-copy-btn" data-url="<?php echo esc_attr( $entry['url'] ); ?>">
								<?php esc_html_e( 'Copy', 'see' ); ?>
							</button>
							<button type="button" class="button button-small see-remove-history-btn"
									data-action="see_remove_file_history"
									data-entry-id="<?php echo esc_attr( $entry['id'] ); ?>">
								<?php esc_html_e( 'Remove', 'see' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</div>
