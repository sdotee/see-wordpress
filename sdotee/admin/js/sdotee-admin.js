/**
 * S.EE Admin JavaScript
 *
 * Handles AJAX interactions, clipboard, and UI logic.
 *
 * @package SDOTEE
 */

/* global jQuery, sdoteeAdmin */
(function ($) {
	'use strict';

	var SDOTEE = {
		/**
		 * Initialize all handlers.
		 */
		init: function () {
			this.bindSettings();
			this.bindShortUrl();
			this.bindTextShare();
			this.bindFileUpload();
			this.bindSidebarFileUpload();
			this.bindCopyButtons();
			this.bindTabs();
			this.bindHistory();
		},

		/**
		 * Settings page handlers.
		 */
		bindSettings: function () {
			// Toggle API key visibility.
			$('#sdotee-toggle-key').on('click', function () {
				var $input = $('#sdotee_api_key');
				var $btn = $(this);
				if ($input.attr('type') === 'password') {
					$input.attr('type', 'text');
					$btn.text(sdoteeAdmin.i18n.hide);
				} else {
					$input.attr('type', 'password');
					$btn.text(sdoteeAdmin.i18n.show);
				}
			});

			// Test connection.
			$('#sdotee-test-connection').on('click', function () {
				var $btn = $(this);
				var $status = $('#sdotee-connection-status');
				var apiKey = $('#sdotee_api_key').val();
				var baseUrl = $('#sdotee_api_base_url').val();

				$btn.prop('disabled', true);
				$status.removeClass('sdotee-status-success sdotee-status-error')
					.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.testing);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_test_connection',
					nonce: sdoteeAdmin.nonce,
					api_key: apiKey,
					base_url: baseUrl
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.addClass('sdotee-status-success').text(response.data.message);
						// Update domain selects if domains returned.
						if (response.data.domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_domain', response.data.domains);
						}
						if (response.data.text_domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_text_domain', response.data.text_domains);
						}
						if (response.data.file_domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_file_domain', response.data.file_domains);
						}
					} else {
						$status.addClass('sdotee-status-error').text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.addClass('sdotee-status-error').text(sdoteeAdmin.i18n.error);
				});
			});

			// Refresh domains.
			$('#sdotee-refresh-domains').on('click', function () {
				var $btn = $(this);
				var $status = $('#sdotee-domains-status');

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.refreshing);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_fetch_domains',
					nonce: sdoteeAdmin.nonce
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.text(response.data.message);
						if (response.data.domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_domain', response.data.domains);
						}
						if (response.data.text_domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_text_domain', response.data.text_domains);
						}
						if (response.data.file_domains) {
							SDOTEE.updateDomainSelect('#sdotee_default_file_domain', response.data.file_domains);
						}
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(sdoteeAdmin.i18n.error);
				});
			});
		},

		/**
		 * Update a domain select dropdown.
		 *
		 * @param {string} selector jQuery selector for the select element.
		 * @param {Array} domains List of domain strings.
		 */
		updateDomainSelect: function (selector, domains) {
			var $select = $(selector);
			var current = $select.val();
			$select.find('option:not(:first)').remove();
			$.each(domains, function (i, domain) {
				$select.append(
					$('<option>').val(domain).text(domain)
				);
			});
			if (current) {
				$select.val(current);
			}
		},

		/**
		 * Short URL meta box handlers.
		 */
		bindShortUrl: function () {
			// Generate short URL.
			$(document).on('click', '.sdotee-generate-shorturl-btn', function () {
				var $metabox = $(this).closest('.sdotee-shorturl-metabox');
				var postId = $metabox.data('post-id');
				var domain = $metabox.find('#sdotee-shorturl-domain').val();
				var slug = $metabox.find('#sdotee-shorturl-slug').val();
				var $btn = $(this);
				var $status = $metabox.find('.sdotee-shorturl-status');

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.generating);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_create_shorturl',
					nonce: sdoteeAdmin.nonce,
					post_id: postId,
					domain: domain,
					slug: slug
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.text('');
						// Replace form with result.
						var url = response.data.short_url;
						var html = '<div class="sdotee-shorturl-result">' +
							'<p class="sdotee-shorturl-display">' +
							'<a href="' + url + '" target="_blank" class="sdotee-short-url-link">' + url + '</a>' +
							'</p>' +
							'<div class="sdotee-shorturl-actions">' +
							'<button type="button" class="button button-small sdotee-copy-btn" data-url="' + url + '">' +
							'<span class="dashicons dashicons-clipboard"></span> ' + sdoteeAdmin.i18n.copied.replace(sdoteeAdmin.i18n.copied, 'Copy') +
							'</button>' +
							' <button type="button" class="button button-small sdotee-delete-shorturl-btn">' +
							'<span class="dashicons dashicons-trash"></span> Delete' +
							'</button>' +
							'</div></div>';
						$metabox.find('.sdotee-shorturl-form').replaceWith(html);
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(sdoteeAdmin.i18n.error);
				});
			});

			// Delete short URL.
			$(document).on('click', '.sdotee-delete-shorturl-btn', function () {
				if (!confirm(sdoteeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.sdotee-shorturl-metabox');
				var postId = $metabox.data('post-id');
				var $btn = $(this);

				$btn.prop('disabled', true).text(sdoteeAdmin.i18n.deleting);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_delete_shorturl',
					nonce: sdoteeAdmin.nonce,
					post_id: postId
				}, function (response) {
					if (response.success) {
						// Reload the page to show the form again.
						location.reload();
					} else {
						$btn.prop('disabled', false).text('Delete');
						alert(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false).text('Delete');
					alert(sdoteeAdmin.i18n.error);
				});
			});
		},

		/**
		 * Text share handlers.
		 */
		bindTextShare: function () {
			// Meta box: Create text share.
			$(document).on('click', '.sdotee-create-text-btn', function () {
				var $metabox = $(this).closest('.sdotee-text-metabox');
				var postId = $metabox.data('post-id');
				var content = $metabox.find('#sdotee-text-content').val();
				var title = $metabox.find('#sdotee-text-title').val();
				var textType = $metabox.find('#sdotee-text-type').val();
				var $btn = $(this);
				var $status = $metabox.find('.sdotee-text-status');

				if (!content) {
					$status.text(sdoteeAdmin.i18n.error);
					return;
				}

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.sharing);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_create_text',
					nonce: sdoteeAdmin.nonce,
					post_id: postId,
					content: content,
					title: title,
					text_type: textType
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						// Replace form with result.
						location.reload();
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(sdoteeAdmin.i18n.error);
				});
			});

			// Meta box: Delete text share.
			$(document).on('click', '.sdotee-delete-text-btn', function () {
				if (!confirm(sdoteeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.sdotee-text-metabox');
				var postId = $metabox.data('post-id');
				var domain = $(this).data('domain');
				var slug = $(this).data('slug');
				var $btn = $(this);

				$btn.prop('disabled', true).text(sdoteeAdmin.i18n.deleting);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_delete_text',
					nonce: sdoteeAdmin.nonce,
					post_id: postId,
					domain: domain,
					slug: slug
				}, function (response) {
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
						alert(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					alert(sdoteeAdmin.i18n.error);
				});
			});

			// Management page: Create text share.
			$('#sdotee-mgmt-create-text').on('click', function () {
				var content = $('#sdotee-mgmt-text-content').val();
				var title = $('#sdotee-mgmt-text-title').val();
				var textType = $('#sdotee-mgmt-text-type').val();
				var $btn = $(this);
				var $status = $('#sdotee-mgmt-text-status');

				if (!content) {
					$status.text(sdoteeAdmin.i18n.error);
					return;
				}

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.sharing);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_create_text',
					nonce: sdoteeAdmin.nonce,
					post_id: 0,
					content: content,
					title: title,
					text_type: textType
				}, function (response) {
					$btn.prop('disabled', false);
					$status.text('');
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(sdoteeAdmin.i18n.error);
				});
			});
		},

		/**
		 * File upload handlers.
		 */
		bindFileUpload: function () {
			// Media library: Upload to S.EE.
			$(document).on('click', '.sdotee-upload-file-btn', function () {
				var $btn = $(this);
				var attachmentId = $btn.data('attachment-id');
				var $status = $('.sdotee-upload-status[data-attachment-id="' + attachmentId + '"]');

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.uploading);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_upload_file',
					nonce: sdoteeAdmin.nonce,
					attachment_id: attachmentId
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.text(response.data.message);
						// Reload to show the new URL.
						if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
							wp.media.frame.content.get().collection.props.set({ ignore: (+new Date()) });
						} else {
							location.reload();
						}
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(sdoteeAdmin.i18n.error);
				});
			});

			// Media library: Delete from S.EE.
			$(document).on('click', '.sdotee-delete-file-btn', function () {
				if (!confirm(sdoteeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $btn = $(this);
				var attachmentId = $btn.data('attachment-id');
				var deleteKey = $btn.data('delete-key');

				$btn.prop('disabled', true).text(sdoteeAdmin.i18n.deleting);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_delete_file',
					nonce: sdoteeAdmin.nonce,
					attachment_id: attachmentId,
					delete_key: deleteKey
				}, function (response) {
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
						alert(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					alert(sdoteeAdmin.i18n.error);
				});
			});

			// Management page: File upload dropzone.
			var $dropzone = $('#sdotee-file-dropzone');
			var $fileInput = $('#sdotee-file-input');

			if ($dropzone.length && $fileInput.length) {
				$dropzone.on('click', function (e) {
					if (e.target === this || $(e.target).is('p')) {
						$fileInput[0].click();
					}
				});

				$('#sdotee-file-browse').on('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$fileInput[0].click();
				});

				$dropzone.on('dragover', function (e) {
					e.preventDefault();
					$(this).addClass('sdotee-dragover');
				}).on('dragleave drop', function (e) {
					e.preventDefault();
					$(this).removeClass('sdotee-dragover');
				}).on('drop', function (e) {
					var files = e.originalEvent.dataTransfer.files;
					if (files.length) {
						SDOTEE.uploadStandaloneFile(files[0]);
					}
				});

				$fileInput.on('change', function () {
					if (this.files.length) {
						SDOTEE.uploadStandaloneFile(this.files[0]);
					}
				});
			}
		},

		/**
		 * Sidebar file upload meta box handlers.
		 */
		bindSidebarFileUpload: function () {
			// Upload file from sidebar meta box.
			$(document).on('click', '.sdotee-upload-sidebar-file-btn', function () {
				var $metabox = $(this).closest('.sdotee-file-metabox');
				var postId = $metabox.data('post-id');
				var $fileInput = $metabox.find('.sdotee-sidebar-file-input');
				var $btn = $(this);
				var $status = $metabox.find('.sdotee-sidebar-file-status');

				if (!$fileInput[0].files.length) {
					$status.text(sdoteeAdmin.i18n.error);
					return;
				}

				var formData = new FormData();
				formData.append('action', 'sdotee_upload_sidebar_file');
				formData.append('nonce', sdoteeAdmin.nonce);
				formData.append('post_id', postId);
				formData.append('file', $fileInput[0].files[0]);

				$btn.prop('disabled', true);
				$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.uploading);

				$.ajax({
					url: sdoteeAdmin.ajaxUrl,
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function (response) {
						if (response.success) {
							location.reload();
						} else {
							$btn.prop('disabled', false);
							$status.text(response.data.message);
						}
					},
					error: function () {
						$btn.prop('disabled', false);
						$status.text(sdoteeAdmin.i18n.error);
					}
				});
			});

			// Delete sidebar file.
			$(document).on('click', '.sdotee-delete-sidebar-file-btn', function () {
				if (!confirm(sdoteeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.sdotee-file-metabox');
				var postId = $metabox.data('post-id');
				var $btn = $(this);

				$btn.prop('disabled', true).text(sdoteeAdmin.i18n.deleting);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: 'sdotee_delete_sidebar_file',
					nonce: sdoteeAdmin.nonce,
					post_id: postId
				}, function (response) {
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
						alert(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					alert(sdoteeAdmin.i18n.error);
				});
			});

			// Format copy buttons (URL, HTML, Markdown, BBCode).
			$(document).on('click', '.sdotee-format-copy-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var format = $(this).data('format');
				var url = $(this).data('url');
				var filename = $(this).data('filename') || '';
				var $btn = $(this);
				var text = '';

				switch (format) {
					case 'url':
						text = url;
						break;
					case 'html':
						text = '<img src="' + url + '" alt="' + filename + '" />';
						break;
					case 'markdown':
						text = '![' + filename + '](' + url + ')';
						break;
					case 'bbcode':
						text = '[img]' + url + '[/img]';
						break;
					default:
						text = url;
				}

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						SDOTEE.showCopyFeedback($btn, true);
					}).catch(function () {
						SDOTEE.fallbackCopy(text, $btn);
					});
				} else {
					SDOTEE.fallbackCopy(text, $btn);
				}
			});
		},

		/**
		 * Upload a file from the standalone management page.
		 * First uploads to WordPress media library, then triggers S.EE upload.
		 *
		 * @param {File} file File object to upload.
		 */
		uploadStandaloneFile: function (file) {
			var $status = $('#sdotee-file-upload-status');
			$status.html('<span class="sdotee-spinner"></span> ' + sdoteeAdmin.i18n.uploading);

			// Upload via WordPress media API first.
			var formData = new FormData();
			formData.append('async-upload', file);
			formData.append('name', file.name);
			formData.append('action', 'upload-attachment');
			formData.append('_wpnonce', sdoteeAdmin.nonce);

			// First we need to create a WP attachment, then upload to S.EE.
			// For simplicity, use AJAX to upload directly via the SDK.
			// We'll send the file via FormData to a custom AJAX endpoint.
			var sdoteeFormData = new FormData();
			sdoteeFormData.append('action', 'sdotee_upload_standalone_file');
			sdoteeFormData.append('nonce', sdoteeAdmin.nonce);
			sdoteeFormData.append('file', file);

			$.ajax({
				url: sdoteeAdmin.ajaxUrl,
				type: 'POST',
				data: sdoteeFormData,
				processData: false,
				contentType: false,
				success: function (response) {
					if (response.success) {
						window.location.hash = 'sdotee-tab-file';
						location.reload();
					} else {
						$status.text(response.data.message);
					}
				},
				error: function () {
					$status.text(sdoteeAdmin.i18n.error);
				}
			});
		},

		/**
		 * Copy to clipboard handlers.
		 */
		bindCopyButtons: function () {
			$(document).on('click', '.sdotee-copy-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var url = $(this).data('url') || $(this).attr('data-url');
				var $btn = $(this);

				if (!url) {
					return;
				}

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(function () {
						SDOTEE.showCopyFeedback($btn, true);
					}).catch(function () {
						SDOTEE.fallbackCopy(url, $btn);
					});
				} else {
					SDOTEE.fallbackCopy(url, $btn);
				}
			});
		},

		/**
		 * Fallback copy using a temporary textarea.
		 *
		 * @param {string} text Text to copy.
		 * @param {jQuery} $btn Button element.
		 */
		fallbackCopy: function (text, $btn) {
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(text).select();
			try {
				document.execCommand('copy');
				SDOTEE.showCopyFeedback($btn, true);
			} catch (e) {
				SDOTEE.showCopyFeedback($btn, false);
			}
			$temp.remove();
		},

		/**
		 * Show copy feedback on button.
		 *
		 * @param {jQuery} $btn Button element.
		 * @param {boolean} success Whether copy was successful.
		 */
		showCopyFeedback: function ($btn, success) {
			var originalText = $btn.html();
			$btn.text(success ? sdoteeAdmin.i18n.copied : sdoteeAdmin.i18n.copyFailed);
			setTimeout(function () {
				$btn.html(originalText);
			}, 1500);
		},

		/**
		 * History remove handlers.
		 */
		bindHistory: function () {
			$(document).on('click', '.sdotee-remove-history-btn', function () {
				if (!confirm(sdoteeAdmin.i18n.confirm_remove_history)) {
					return;
				}

				var $btn = $(this);
				var action = $btn.data('action');
				var entryId = $btn.data('entry-id');

				$btn.prop('disabled', true).text(sdoteeAdmin.i18n.deleting);

				$.post(sdoteeAdmin.ajaxUrl, {
					action: action,
					nonce: sdoteeAdmin.nonce,
					entry_id: entryId
				}, function (response) {
					if (response.success) {
						$btn.closest('tr').fadeOut(300, function () {
							$(this).remove();
						});
					} else {
						$btn.prop('disabled', false).text(sdoteeAdmin.i18n.error);
					}
				}).fail(function () {
					$btn.prop('disabled', false).text(sdoteeAdmin.i18n.error);
				});
			});
		},

		/**
		 * Tab navigation on management page.
		 */
		bindTabs: function () {
			$('.sdotee-management-tabs .nav-tab').on('click', function (e) {
				e.preventDefault();
				var tabId = $(this).data('tab');

				$('.sdotee-management-tabs .nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');

				$('.sdotee-tab-content').hide().removeClass('sdotee-tab-active');
				$('#' + tabId).show().addClass('sdotee-tab-active');

				window.location.hash = tabId;
			});

			// Restore active tab from URL hash on page load.
			var hash = window.location.hash.replace('#', '');
			if (hash && $('#' + hash).length) {
				$('.sdotee-management-tabs .nav-tab').removeClass('nav-tab-active');
				$('.sdotee-management-tabs .nav-tab[data-tab="' + hash + '"]').addClass('nav-tab-active');
				$('.sdotee-tab-content').hide().removeClass('sdotee-tab-active');
				$('#' + hash).show().addClass('sdotee-tab-active');
			}
		}
	};

	$(document).ready(function () {
		SDOTEE.init();
	});

})(jQuery);
