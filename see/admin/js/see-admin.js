/**
 * S.EE Admin JavaScript
 *
 * Handles AJAX interactions, clipboard, and UI logic.
 *
 * @package SEE
 */

/* global jQuery, seeAdmin */
(function ($) {
	'use strict';

	var SEE = {
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
			$('#see-toggle-key').on('click', function () {
				var $input = $('#see_api_key');
				var $btn = $(this);
				if ($input.attr('type') === 'password') {
					$input.attr('type', 'text');
					$btn.text(seeAdmin.i18n.hide);
				} else {
					$input.attr('type', 'password');
					$btn.text(seeAdmin.i18n.show);
				}
			});

			// Test connection.
			$('#see-test-connection').on('click', function () {
				var $btn = $(this);
				var $status = $('#see-connection-status');
				var apiKey = $('#see_api_key').val();
				var baseUrl = $('#see_api_base_url').val();

				$btn.prop('disabled', true);
				$status.removeClass('see-status-success see-status-error')
					.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.testing);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_test_connection',
					nonce: seeAdmin.nonce,
					api_key: apiKey,
					base_url: baseUrl
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.addClass('see-status-success').text(response.data.message);
						// Update domain selects if domains returned.
						if (response.data.domains) {
							SEE.updateDomainSelect('#see_default_domain', response.data.domains);
						}
						if (response.data.text_domains) {
							SEE.updateDomainSelect('#see_default_text_domain', response.data.text_domains);
						}
						if (response.data.file_domains) {
							SEE.updateDomainSelect('#see_default_file_domain', response.data.file_domains);
						}
					} else {
						$status.addClass('see-status-error').text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.addClass('see-status-error').text(seeAdmin.i18n.error);
				});
			});

			// Refresh domains.
			$('#see-refresh-domains').on('click', function () {
				var $btn = $(this);
				var $status = $('#see-domains-status');

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.refreshing);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_fetch_domains',
					nonce: seeAdmin.nonce
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.text(response.data.message);
						if (response.data.domains) {
							SEE.updateDomainSelect('#see_default_domain', response.data.domains);
						}
						if (response.data.text_domains) {
							SEE.updateDomainSelect('#see_default_text_domain', response.data.text_domains);
						}
						if (response.data.file_domains) {
							SEE.updateDomainSelect('#see_default_file_domain', response.data.file_domains);
						}
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(seeAdmin.i18n.error);
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
			$(document).on('click', '.see-generate-shorturl-btn', function () {
				var $metabox = $(this).closest('.see-shorturl-metabox');
				var postId = $metabox.data('post-id');
				var domain = $metabox.find('#see-shorturl-domain').val();
				var slug = $metabox.find('#see-shorturl-slug').val();
				var $btn = $(this);
				var $status = $metabox.find('.see-shorturl-status');

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.generating);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_create_shorturl',
					nonce: seeAdmin.nonce,
					post_id: postId,
					domain: domain,
					slug: slug
				}, function (response) {
					$btn.prop('disabled', false);
					if (response.success) {
						$status.text('');
						// Replace form with result.
						var url = response.data.short_url;
						var html = '<div class="see-shorturl-result">' +
							'<p class="see-shorturl-display">' +
							'<a href="' + url + '" target="_blank" class="see-short-url-link">' + url + '</a>' +
							'</p>' +
							'<div class="see-shorturl-actions">' +
							'<button type="button" class="button button-small see-copy-btn" data-url="' + url + '">' +
							'<span class="dashicons dashicons-clipboard"></span> ' + seeAdmin.i18n.copied.replace(seeAdmin.i18n.copied, 'Copy') +
							'</button>' +
							' <button type="button" class="button button-small see-delete-shorturl-btn">' +
							'<span class="dashicons dashicons-trash"></span> Delete' +
							'</button>' +
							'</div></div>';
						$metabox.find('.see-shorturl-form').replaceWith(html);
					} else {
						$status.text(response.data.message);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$status.text(seeAdmin.i18n.error);
				});
			});

			// Delete short URL.
			$(document).on('click', '.see-delete-shorturl-btn', function () {
				if (!confirm(seeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.see-shorturl-metabox');
				var postId = $metabox.data('post-id');
				var $btn = $(this);

				$btn.prop('disabled', true).text(seeAdmin.i18n.deleting);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_delete_shorturl',
					nonce: seeAdmin.nonce,
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
					alert(seeAdmin.i18n.error);
				});
			});
		},

		/**
		 * Text share handlers.
		 */
		bindTextShare: function () {
			// Meta box: Create text share.
			$(document).on('click', '.see-create-text-btn', function () {
				var $metabox = $(this).closest('.see-text-metabox');
				var postId = $metabox.data('post-id');
				var content = $metabox.find('#see-text-content').val();
				var title = $metabox.find('#see-text-title').val();
				var textType = $metabox.find('#see-text-type').val();
				var $btn = $(this);
				var $status = $metabox.find('.see-text-status');

				if (!content) {
					$status.text(seeAdmin.i18n.error);
					return;
				}

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.sharing);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_create_text',
					nonce: seeAdmin.nonce,
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
					$status.text(seeAdmin.i18n.error);
				});
			});

			// Meta box: Delete text share.
			$(document).on('click', '.see-delete-text-btn', function () {
				if (!confirm(seeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.see-text-metabox');
				var postId = $metabox.data('post-id');
				var domain = $(this).data('domain');
				var slug = $(this).data('slug');
				var $btn = $(this);

				$btn.prop('disabled', true).text(seeAdmin.i18n.deleting);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_delete_text',
					nonce: seeAdmin.nonce,
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
					alert(seeAdmin.i18n.error);
				});
			});

			// Management page: Create text share.
			$('#see-mgmt-create-text').on('click', function () {
				var content = $('#see-mgmt-text-content').val();
				var title = $('#see-mgmt-text-title').val();
				var textType = $('#see-mgmt-text-type').val();
				var $btn = $(this);
				var $status = $('#see-mgmt-text-status');

				if (!content) {
					$status.text(seeAdmin.i18n.error);
					return;
				}

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.sharing);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_create_text',
					nonce: seeAdmin.nonce,
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
					$status.text(seeAdmin.i18n.error);
				});
			});
		},

		/**
		 * File upload handlers.
		 */
		bindFileUpload: function () {
			// Media library: Upload to S.EE.
			$(document).on('click', '.see-upload-file-btn', function () {
				var $btn = $(this);
				var attachmentId = $btn.data('attachment-id');
				var $status = $('.see-upload-status[data-attachment-id="' + attachmentId + '"]');

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.uploading);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_upload_file',
					nonce: seeAdmin.nonce,
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
					$status.text(seeAdmin.i18n.error);
				});
			});

			// Media library: Delete from S.EE.
			$(document).on('click', '.see-delete-file-btn', function () {
				if (!confirm(seeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $btn = $(this);
				var attachmentId = $btn.data('attachment-id');
				var deleteKey = $btn.data('delete-key');

				$btn.prop('disabled', true).text(seeAdmin.i18n.deleting);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_delete_file',
					nonce: seeAdmin.nonce,
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
					alert(seeAdmin.i18n.error);
				});
			});

			// Management page: File upload dropzone.
			var $dropzone = $('#see-file-dropzone');
			var $fileInput = $('#see-file-input');

			if ($dropzone.length && $fileInput.length) {
				$dropzone.on('click', function (e) {
					if (e.target === this || $(e.target).is('p')) {
						$fileInput[0].click();
					}
				});

				$('#see-file-browse').on('click', function (e) {
					e.preventDefault();
					e.stopPropagation();
					$fileInput[0].click();
				});

				$dropzone.on('dragover', function (e) {
					e.preventDefault();
					$(this).addClass('see-dragover');
				}).on('dragleave drop', function (e) {
					e.preventDefault();
					$(this).removeClass('see-dragover');
				}).on('drop', function (e) {
					var files = e.originalEvent.dataTransfer.files;
					if (files.length) {
						SEE.uploadStandaloneFile(files[0]);
					}
				});

				$fileInput.on('change', function () {
					if (this.files.length) {
						SEE.uploadStandaloneFile(this.files[0]);
					}
				});
			}
		},

		/**
		 * Sidebar file upload meta box handlers.
		 */
		bindSidebarFileUpload: function () {
			// Upload file from sidebar meta box.
			$(document).on('click', '.see-upload-sidebar-file-btn', function () {
				var $metabox = $(this).closest('.see-file-metabox');
				var postId = $metabox.data('post-id');
				var $fileInput = $metabox.find('.see-sidebar-file-input');
				var $btn = $(this);
				var $status = $metabox.find('.see-sidebar-file-status');

				if (!$fileInput[0].files.length) {
					$status.text(seeAdmin.i18n.error);
					return;
				}

				var formData = new FormData();
				formData.append('action', 'see_upload_sidebar_file');
				formData.append('nonce', seeAdmin.nonce);
				formData.append('post_id', postId);
				formData.append('file', $fileInput[0].files[0]);

				$btn.prop('disabled', true);
				$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.uploading);

				$.ajax({
					url: seeAdmin.ajaxUrl,
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
						$status.text(seeAdmin.i18n.error);
					}
				});
			});

			// Delete sidebar file.
			$(document).on('click', '.see-delete-sidebar-file-btn', function () {
				if (!confirm(seeAdmin.i18n.confirm_delete)) {
					return;
				}

				var $metabox = $(this).closest('.see-file-metabox');
				var postId = $metabox.data('post-id');
				var $btn = $(this);

				$btn.prop('disabled', true).text(seeAdmin.i18n.deleting);

				$.post(seeAdmin.ajaxUrl, {
					action: 'see_delete_sidebar_file',
					nonce: seeAdmin.nonce,
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
					alert(seeAdmin.i18n.error);
				});
			});

			// Format copy buttons (URL, HTML, Markdown, BBCode).
			$(document).on('click', '.see-format-copy-btn', function (e) {
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
						SEE.showCopyFeedback($btn, true);
					}).catch(function () {
						SEE.fallbackCopy(text, $btn);
					});
				} else {
					SEE.fallbackCopy(text, $btn);
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
			var $status = $('#see-file-upload-status');
			$status.html('<span class="see-spinner"></span> ' + seeAdmin.i18n.uploading);

			// Upload via WordPress media API first.
			var formData = new FormData();
			formData.append('async-upload', file);
			formData.append('name', file.name);
			formData.append('action', 'upload-attachment');
			formData.append('_wpnonce', seeAdmin.nonce);

			// First we need to create a WP attachment, then upload to S.EE.
			// For simplicity, use AJAX to upload directly via the SDK.
			// We'll send the file via FormData to a custom AJAX endpoint.
			var seeFormData = new FormData();
			seeFormData.append('action', 'see_upload_standalone_file');
			seeFormData.append('nonce', seeAdmin.nonce);
			seeFormData.append('file', file);

			$.ajax({
				url: seeAdmin.ajaxUrl,
				type: 'POST',
				data: seeFormData,
				processData: false,
				contentType: false,
				success: function (response) {
					if (response.success) {
						window.location.hash = 'see-tab-file';
						location.reload();
					} else {
						$status.text(response.data.message);
					}
				},
				error: function () {
					$status.text(seeAdmin.i18n.error);
				}
			});
		},

		/**
		 * Copy to clipboard handlers.
		 */
		bindCopyButtons: function () {
			$(document).on('click', '.see-copy-btn', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var url = $(this).data('url') || $(this).attr('data-url');
				var $btn = $(this);

				if (!url) {
					return;
				}

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(function () {
						SEE.showCopyFeedback($btn, true);
					}).catch(function () {
						SEE.fallbackCopy(url, $btn);
					});
				} else {
					SEE.fallbackCopy(url, $btn);
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
				SEE.showCopyFeedback($btn, true);
			} catch (e) {
				SEE.showCopyFeedback($btn, false);
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
			$btn.text(success ? seeAdmin.i18n.copied : seeAdmin.i18n.copyFailed);
			setTimeout(function () {
				$btn.html(originalText);
			}, 1500);
		},

		/**
		 * History remove handlers.
		 */
		bindHistory: function () {
			$(document).on('click', '.see-remove-history-btn', function () {
				if (!confirm(seeAdmin.i18n.confirm_remove_history)) {
					return;
				}

				var $btn = $(this);
				var action = $btn.data('action');
				var entryId = $btn.data('entry-id');

				$btn.prop('disabled', true).text(seeAdmin.i18n.deleting);

				$.post(seeAdmin.ajaxUrl, {
					action: action,
					nonce: seeAdmin.nonce,
					entry_id: entryId
				}, function (response) {
					if (response.success) {
						$btn.closest('tr').fadeOut(300, function () {
							$(this).remove();
						});
					} else {
						$btn.prop('disabled', false).text(seeAdmin.i18n.error);
					}
				}).fail(function () {
					$btn.prop('disabled', false).text(seeAdmin.i18n.error);
				});
			});
		},

		/**
		 * Tab navigation on management page.
		 */
		bindTabs: function () {
			$('.see-management-tabs .nav-tab').on('click', function (e) {
				e.preventDefault();
				var tabId = $(this).data('tab');

				$('.see-management-tabs .nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');

				$('.see-tab-content').hide().removeClass('see-tab-active');
				$('#' + tabId).show().addClass('see-tab-active');

				window.location.hash = tabId;
			});

			// Restore active tab from URL hash on page load.
			var hash = window.location.hash.replace('#', '');
			if (hash && $('#' + hash).length) {
				$('.see-management-tabs .nav-tab').removeClass('nav-tab-active');
				$('.see-management-tabs .nav-tab[data-tab="' + hash + '"]').addClass('nav-tab-active');
				$('.see-tab-content').hide().removeClass('see-tab-active');
				$('#' + hash).show().addClass('see-tab-active');
			}
		}
	};

	$(document).ready(function () {
		SEE.init();
	});

})(jQuery);
