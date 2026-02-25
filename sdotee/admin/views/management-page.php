<?php
/**
 * Management page template.
 *
 * @package SDOTEE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sdotee_text_history = SDOTEE_Helpers::get_history( 'sdotee_text_history' );
$sdotee_file_history = SDOTEE_Helpers::get_history( 'sdotee_file_history' );
?>
<div class="wrap sdotee-management-page">
	<h1><?php esc_html_e( 'S.EE Management', 'sdotee' ); ?></h1>

	<div class="sdotee-management-tabs">
		<nav class="nav-tab-wrapper">
			<a href="#sdotee-tab-text" class="nav-tab nav-tab-active" data-tab="sdotee-tab-text">
				<?php esc_html_e( 'Text Share', 'sdotee' ); ?>
			</a>
			<a href="#sdotee-tab-file" class="nav-tab" data-tab="sdotee-tab-file">
				<?php esc_html_e( 'File Upload', 'sdotee' ); ?>
			</a>
		</nav>

		<!-- Text Share Tab -->
		<div id="sdotee-tab-text" class="sdotee-tab-content sdotee-tab-active">
			<div class="sdotee-text-share-standalone">
				<h2><?php esc_html_e( 'Share Text', 'sdotee' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="sdotee-mgmt-text-title"><?php esc_html_e( 'Title', 'sdotee' ); ?></label>
						</th>
						<td>
							<input type="text" id="sdotee-mgmt-text-title" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sdotee-mgmt-text-type"><?php esc_html_e( 'Type', 'sdotee' ); ?></label>
						</th>
						<td>
							<select id="sdotee-mgmt-text-type">
								<option value="plain_text"><?php esc_html_e( 'Plain Text', 'sdotee' ); ?></option>
								<option value="markdown"><?php esc_html_e( 'Markdown', 'sdotee' ); ?></option>
								<option value="source_code"><?php esc_html_e( 'Source Code', 'sdotee' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sdotee-mgmt-text-content"><?php esc_html_e( 'Content', 'sdotee' ); ?></label>
						</th>
						<td>
							<textarea id="sdotee-mgmt-text-content" rows="10" class="large-text"></textarea>
						</td>
					</tr>
				</table>
				<p>
					<button type="button" id="sdotee-mgmt-create-text" class="button button-primary">
						<?php esc_html_e( 'Share Text', 'sdotee' ); ?>
					</button>
					<span id="sdotee-mgmt-text-status"></span>
				</p>
				<div id="sdotee-mgmt-text-result" class="sdotee-result-box" style="display:none;">
					<p>
						<strong><?php esc_html_e( 'Shared URL:', 'sdotee' ); ?></strong>
						<a href="" id="sdotee-mgmt-text-url" target="_blank"></a>
						<button type="button" class="button button-small sdotee-copy-btn" data-url="">
							<?php esc_html_e( 'Copy', 'sdotee' ); ?>
						</button>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $sdotee_text_history ) ) : ?>
			<hr />
			<h2><?php esc_html_e( 'Text Share History', 'sdotee' ); ?></h2>
			<table class="wp-list-table widefat fixed striped sdotee-history-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Type', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'URL', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Date', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'sdotee' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sdotee_text_history as $sdotee_entry ) : ?>
					<tr id="sdotee-text-row-<?php echo esc_attr( $sdotee_entry['id'] ); ?>">
						<td><?php echo esc_html( $sdotee_entry['title'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $sdotee_entry['text_type'] ?? 'plain_text' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $sdotee_entry['url'] ); ?>" target="_blank">
								<?php echo esc_html( $sdotee_entry['url'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $sdotee_entry['created_at'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small sdotee-copy-btn" data-url="<?php echo esc_attr( $sdotee_entry['url'] ); ?>">
								<?php esc_html_e( 'Copy', 'sdotee' ); ?>
							</button>
							<button type="button" class="button button-small sdotee-remove-history-btn"
									data-action="sdotee_remove_text_history"
									data-entry-id="<?php echo esc_attr( $sdotee_entry['id'] ); ?>">
								<?php esc_html_e( 'Remove', 'sdotee' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

		<!-- File Upload Tab -->
		<div id="sdotee-tab-file" class="sdotee-tab-content" style="display:none;">
			<div class="sdotee-file-upload-standalone">
				<h2><?php esc_html_e( 'Upload File', 'sdotee' ); ?></h2>
				<div id="sdotee-file-dropzone" class="sdotee-dropzone">
					<p><?php esc_html_e( 'Drag & drop files here, or click to select files.', 'sdotee' ); ?></p>
					<input type="file" id="sdotee-file-input" style="display:none;" />
					<button type="button" class="button button-secondary" id="sdotee-file-browse">
						<?php esc_html_e( 'Browse Files', 'sdotee' ); ?>
					</button>
				</div>
				<div id="sdotee-file-upload-status"></div>
				<div id="sdotee-file-upload-result" class="sdotee-result-box" style="display:none;">
					<p>
						<strong><?php esc_html_e( 'Direct Link:', 'sdotee' ); ?></strong>
						<a href="" id="sdotee-file-result-url" target="_blank"></a>
						<button type="button" class="button button-small sdotee-copy-btn" data-url="">
							<?php esc_html_e( 'Copy', 'sdotee' ); ?>
						</button>
					</p>
					<p id="sdotee-file-result-page-row" style="display:none;">
						<strong><?php esc_html_e( 'Share Page:', 'sdotee' ); ?></strong>
						<a href="" id="sdotee-file-result-page" target="_blank"></a>
						<button type="button" class="button button-small sdotee-copy-btn" data-url="">
							<?php esc_html_e( 'Copy', 'sdotee' ); ?>
						</button>
					</p>
				</div>
			</div>

			<?php if ( ! empty( $sdotee_file_history ) ) : ?>
			<hr />
			<h2><?php esc_html_e( 'File Upload History', 'sdotee' ); ?></h2>
			<table class="wp-list-table widefat fixed striped sdotee-history-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Filename', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Direct Link', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Share Page', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Date', 'sdotee' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'sdotee' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sdotee_file_history as $sdotee_entry ) : ?>
					<tr id="sdotee-file-row-<?php echo esc_attr( $sdotee_entry['id'] ); ?>">
						<td><?php echo esc_html( $sdotee_entry['filename'] ?? '—' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $sdotee_entry['url'] ); ?>" target="_blank">
								<?php echo esc_html( $sdotee_entry['url'] ); ?>
							</a>
						</td>
						<td>
							<?php if ( ! empty( $sdotee_entry['page'] ) ) : ?>
							<a href="<?php echo esc_url( $sdotee_entry['page'] ); ?>" target="_blank">
								<?php echo esc_html( $sdotee_entry['page'] ); ?>
							</a>
							<?php else : ?>
							—
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $sdotee_entry['created_at'] ?? '' ); ?></td>
						<td>
							<button type="button" class="button button-small sdotee-copy-btn" data-url="<?php echo esc_attr( $sdotee_entry['url'] ); ?>">
								<?php esc_html_e( 'Copy', 'sdotee' ); ?>
							</button>
							<button type="button" class="button button-small sdotee-remove-history-btn"
									data-action="sdotee_remove_file_history"
									data-entry-id="<?php echo esc_attr( $sdotee_entry['id'] ); ?>">
								<?php esc_html_e( 'Remove', 'sdotee' ); ?>
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
