/**
 * Licenser — Admin UI & Interactions Controller
 */

function licenserGenerateUniqueId() {
	if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
		var randomBytes = new Uint8Array(16);
		crypto.getRandomValues(randomBytes);

		randomBytes[6] = (randomBytes[6] & 0x0f) | 0x40;
		randomBytes[8] = (randomBytes[8] & 0x3f) | 0x80;

		return Array.from(randomBytes)
			.map(function (byte) {
				return byte.toString(16).padStart(2, '0');
			})
			.join('');
	}

	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
		var r = (Math.random() * 16) | 0;
		var v = c === 'x' ? r : (r & 0x3) | 0x8;
		return v.toString(16);
	});
}

jQuery(function ($) {
	'use strict';

	var cfg = typeof Licenser !== 'undefined' ? Licenser : {};
	var i18n = cfg.i18n || {};

	function msg(key, fallback) {
		return i18n[key] || fallback;
	}

	function escapeHtml(str) {
		return $('<div>').text(str == null ? '' : String(str)).html();
	}

	function escapeSingleQuote(str) {
		return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
	}

	function formDataToJson(formEl) {
		var formData = new FormData(formEl);
		var jsonObject = {};

		formData.forEach(function (value, key) {
			if (key.indexOf('[') !== -1 && key.indexOf(']') !== -1) {
				var keys = key.match(/\w+/g);
				if (!keys || !keys.length) {
					return;
				}
				var current = jsonObject;
				for (var i = 0; i < keys.length - 1; i++) {
					current[keys[i]] = current[keys[i]] || {};
					current = current[keys[i]];
				}
				current[keys[keys.length - 1]] = value;
			} else {
				jsonObject[key] = value;
			}
		});

		return jsonObject;
	}

	function renderTemplate(templateId, data) {
		var el = document.getElementById(templateId);
		if (!el) {
			return '';
		}

		var html = el.innerHTML;
		Object.keys(data || {}).forEach(function (key) {
			var pattern = new RegExp('\\{\\{\\{\\s*data\\.' + key + '\\s*\\}\\}\\}', 'g');
			html = html.replace(pattern, escapeHtml(data[key]));
		});

		return html;
	}

	function formatPackageSummary(days, sites) {
		var d = parseInt(days, 10) || 0;
		var s = parseInt(sites, 10) || 0;
		var periodText = d > 0 ? d + ' ' + msg('days_suffix', 'Days') : msg('lifetime', 'Lifetime');
		var sitesText =
			s > 0
				? s + ' ' + (s === 1 ? msg('site_singular', 'Site') : msg('sites_suffix', 'Sites'))
				: msg('unlimited', 'Unlimited');
		return periodText + ' \u2022 ' + sitesText;
	}

	function extractErrorMessage(xhr, fallback) {
		if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
			return xhr.responseJSON.message;
		}
		return fallback || msg('generic_error', 'Something went wrong. Please try again.');
	}

	// -------------------------------------------------------------------------
	// Frosted Toast & Inline Notices
	// -------------------------------------------------------------------------
	function getToastStack() {
		var $stack = $('#licenser-toast-stack');
		if (!$stack.length) {
			$stack = $('<div id="licenser-toast-stack" class="licenser-toast-stack" aria-live="polite" aria-atomic="true"></div>');
			$('body').append($stack);
		}
		return $stack;
	}

	function showToast(text, type) {
		if (!text) {
			return;
		}
		var variant = type || 'success';
		var toastRole = variant === 'error' ? 'alert' : 'status';
		var toastLive = variant === 'error' ? 'assertive' : 'polite';
		var $toast = $(
			'<div class="licenser-toast licenser-toast--' +
				escapeHtml(variant) +
				'" role="' +
				toastRole +
				'" aria-live="' +
				toastLive +
				'">' +
				'<span class="licenser-toast__dot" aria-hidden="true"></span>' +
				'<span class="licenser-toast__text"></span>' +
				'<button type="button" class="licenser-toast__close" aria-label="Dismiss">&times;</button>' +
				'</div>'
		);
		$toast.find('.licenser-toast__text').text(text);
		getToastStack().append($toast);

		window.requestAnimationFrame(function () {
			$toast.addClass('is-visible');
		});

		var removeToast = function () {
			$toast.removeClass('is-visible');
			window.setTimeout(function () {
				$toast.remove();
			}, 220);
		};

		var timer = window.setTimeout(removeToast, 3600);
		$toast.on('click', '.licenser-toast__close', function () {
			window.clearTimeout(timer);
			removeToast();
		});
	}

	function showInlineNotice(text, type) {
		var $host = $('.licenser-notices');
		if (type === 'remove') {
			$host.empty();
			return;
		}
		if ($host.length) {
			var kind = type || 'info';
			var $notice = $('<div class="notice is-dismissible inline"></div>')
				.addClass('notice-' + kind)
				.attr('role', kind === 'error' ? 'alert' : 'status')
				.append($('<p></p>').text(text));
			$host.empty().append($notice);
			$(document).trigger('wp-updates-notice-added');
		}
	}

	$(document).on('licenser_notice lmfwppt_notice', function (event, noticeText, type) {
		if (type === 'remove') {
			showInlineNotice('', 'remove');
			return;
		}
		showToast(noticeText, type || 'success');
		if (type === 'error') {
			showInlineNotice(noticeText, 'error');
		}
	});

	// -------------------------------------------------------------------------
	// Accessible Confirmation Modal & Focus Trap (Zero browser confirm()/alert())
	// -------------------------------------------------------------------------
	var activeModalCleanup = null;

	function handleModalFocusTrap(e, $modalRoot) {
		if (e.key !== 'Tab') {
			return;
		}
		var focusables = $modalRoot
			.find('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')
			.filter(':visible');
		if (!focusables.length) {
			return;
		}
		var first = focusables[0];
		var last = focusables[focusables.length - 1];
		if (e.shiftKey) {
			if (document.activeElement === first || !$modalRoot[0].contains(document.activeElement)) {
				e.preventDefault();
				last.focus();
			}
		} else if (document.activeElement === last || !$modalRoot[0].contains(document.activeElement)) {
			e.preventDefault();
			first.focus();
		}
	}

	function closeModal() {
		var $backdrop = $('#licenser-confirm-modal');
		if ($backdrop.length) {
			$backdrop.removeClass('is-open');
			window.setTimeout(function () {
				$backdrop.remove();
			}, 180);
		}
		$(document).off('keydown.licenserModal');
		if (typeof activeModalCleanup === 'function') {
			activeModalCleanup();
			activeModalCleanup = null;
		}
	}

	function openConfirmModal(options) {
		closeModal();

		var opts = $.extend(
			{
				title: msg('confirm_delete_title', 'Confirm Deletion'),
				message: '',
				details: [],
				checkboxLabel: '',
				checkboxChecked: true,
				confirmLabel: msg('delete_label', 'Delete'),
				confirmVariant: 'danger-solid',
				cancelLabel: msg('cancel_label', 'Cancel'),
				secondaryAction: null,
				onConfirm: null
			},
			options || {}
		);

		var previousFocus = document.activeElement;
		activeModalCleanup = function () {
			if (previousFocus && typeof previousFocus.focus === 'function') {
				previousFocus.focus();
			}
		};

		var $backdrop = $(
			'<div id="licenser-confirm-modal" class="licenser-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="licenser-modal-title">' +
				'<div class="licenser-modal">' +
				'<div class="licenser-modal__header">' +
				'<h3 id="licenser-modal-title"></h3>' +
				'<button type="button" class="licenser-icon-btn licenser-modal-close" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button>' +
				'</div>' +
				'<div class="licenser-modal__body"></div>' +
				'<div class="licenser-modal__footer"></div>' +
				'</div>' +
				'</div>'
		);

		$backdrop.find('#licenser-modal-title').text(opts.title);

		var $body = $backdrop.find('.licenser-modal__body');
		if (opts.message) {
			$body.append($('<p></p>').text(opts.message));
		}

		if (Array.isArray(opts.details) && opts.details.length) {
			var $details = $('<div class="licenser-modal__details"></div>');
			opts.details.forEach(function (item) {
				$details.append(
					$('<span class="licenser-badge"></span>')
						.addClass(item.variant ? 'licenser-badge--' + item.variant : '')
						.text(item.label)
				);
			});
			$body.append($details);
		}

		if (opts.checkboxLabel) {
			var $cbWrap = $('<label class="licenser-modal__checkbox"></label>');
			var $cb = $('<input type="checkbox" id="licenser-modal-checkbox" />').prop('checked', !!opts.checkboxChecked);
			$cbWrap.append($cb).append($('<span></span>').text(opts.checkboxLabel));
			$body.append($cbWrap);
		}

		var $footer = $backdrop.find('.licenser-modal__footer');
		var $cancelBtn = $('<button type="button" class="licenser-btn licenser-btn--secondary licenser-modal-close"></button>').text(opts.cancelLabel);
		$footer.append($cancelBtn);

		if (opts.secondaryAction && opts.secondaryAction.label) {
			var $secBtn = $(
				'<button type="button" class="licenser-btn licenser-btn--' +
					escapeHtml(opts.secondaryAction.variant || 'primary') +
					'"></button>'
			).text(opts.secondaryAction.label);
			$secBtn.on('click', function () {
				closeModal();
				if (typeof opts.secondaryAction.onClick === 'function') {
					opts.secondaryAction.onClick();
				}
			});
			$footer.append($secBtn);
		}

		var $confirmBtn = $(
			'<button type="button" class="licenser-btn licenser-btn--' + escapeHtml(opts.confirmVariant) + '"></button>'
		).text(opts.confirmLabel);
		$confirmBtn.on('click', function () {
			var checked = $backdrop.find('#licenser-modal-checkbox').is(':checked');
			closeModal();
			if (typeof opts.onConfirm === 'function') {
				opts.onConfirm({ checked: checked });
			}
		});
		$footer.append($confirmBtn);

		$backdrop.on('click', '.licenser-modal-close', closeModal);
		$backdrop.on('click', function (e) {
			if (e.target === $backdrop[0]) {
				closeModal();
			}
		});

		$(document).on('keydown.licenserModal', function (e) {
			if (e.key === 'Escape') {
				closeModal();
				return;
			}
			handleModalFocusTrap(e, $backdrop);
		});

		var $host = $('.licenser-app').first();
		($host.length ? $host : $('body')).append($backdrop);
		window.requestAnimationFrame(function () {
			$backdrop.addClass('is-open');
			// Focus Cancel safely on destructive dialogs to prevent accidental Enter confirmation.
			var $initialFocus = opts.confirmVariant === 'danger-solid' ? $cancelBtn : $confirmBtn;
			$initialFocus.trigger('focus');
		});
	}

	// -------------------------------------------------------------------------
	// Universal One-Click Clipboard Copy
	// -------------------------------------------------------------------------
	function copyTextToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}

		return new Promise(function (resolve, reject) {
			var $temp = $('<textarea></textarea>')
				.val(text)
				.css({ position: 'fixed', top: '-9999px', left: '-9999px' })
				.appendTo('body');
			$temp[0].select();
			try {
				var ok = document.execCommand('copy');
				$temp.remove();
				if (ok) {
					resolve();
				} else {
					reject(new Error('copy_failed'));
				}
			} catch (err) {
				$temp.remove();
				reject(err);
			}
		});
	}

	$(document).on('click', '[data-licenser-copy], [data-licenser-copy-target]', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var targetSelector = $btn.attr('data-licenser-copy-target');
		var value = targetSelector ? $(targetSelector).val() || $(targetSelector).text() : $btn.attr('data-licenser-copy');

		if (!value) {
			return;
		}

		copyTextToClipboard(String(value))
			.then(function () {
				var $icon = $btn.find('.dashicons');
				var $label = $btn.find('.licenser-copy-label');
				var origIcon = $btn.data('orig-icon') || ($icon.length ? $icon.attr('class') : '');
				var origLabel = $btn.data('orig-label') || ($label.length ? $label.text() : '');

				if (!$btn.data('orig-icon') && origIcon) {
					$btn.data('orig-icon', origIcon);
				}
				if (!$btn.data('orig-label') && origLabel) {
					$btn.data('orig-label', origLabel);
				}

				$btn.addClass('is-copied');
				if ($icon.length) {
					$icon.attr('class', 'dashicons dashicons-yes');
				}
				if ($label.length) {
					$label.text(msg('copied', 'Copied!'));
				}

				showToast(msg('copied_toast', 'Copied to clipboard.'), 'success');

				window.clearTimeout($btn.data('copy-timer'));
				$btn.data(
					'copy-timer',
					window.setTimeout(function () {
						$btn.removeClass('is-copied');
						if ($icon.length && $btn.data('orig-icon')) {
							$icon.attr('class', $btn.data('orig-icon'));
						}
						if ($label.length && $btn.data('orig-label')) {
							$label.text($btn.data('orig-label'));
						}
					}, 1800)
				);
			})
			.catch(function () {
				showToast(msg('copy_failed', 'Could not copy automatically.'), 'error');
			});
	});

	// -------------------------------------------------------------------------
	// Accordions & Scroll-Spy Side Navigation
	// -------------------------------------------------------------------------
	function toggleItemCard($card) {
		var isOpen = !$card.hasClass('is-open');
		$card.toggleClass('is-open', isOpen);
		$card.find('.licenser-item-toggle-btn').attr('aria-expanded', isOpen ? 'true' : 'false');
	}

	$(document).on('click', '.licenser-item-card__head', function (e) {
		if ($(e.target).closest('input, button, a, .licenser-no-toggle').length) {
			return;
		}
		toggleItemCard($(this).closest('.licenser-item-card'));
	});

	$(document).on('click', '.licenser-item-toggle-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		toggleItemCard($(this).closest('.licenser-item-card'));
	});

	var $sidenavLinks = $('.licenser-sidenav a[href^="#"]');
	if ($sidenavLinks.length) {
		$sidenavLinks.first().addClass('is-active');

		$sidenavLinks.on('click', function () {
			$sidenavLinks.removeClass('is-active');
			$(this).addClass('is-active');
		});

		var sectionIds = $sidenavLinks
			.map(function () {
				return $(this).attr('href');
			})
			.get();

		$(window).on('scroll.licenserSpy', function () {
			var scrollTop = $(window).scrollTop() + 150;
			var activeHref = sectionIds[0];

			sectionIds.forEach(function (href) {
				var $sec = $(href);
				if ($sec.length && $sec.offset().top <= scrollTop) {
					activeHref = href;
				}
			});

			$sidenavLinks.removeClass('is-active');
			$sidenavLinks.filter('[href="' + activeHref + '"]').addClass('is-active');
		});
	}

	// Topbar external Save button triggers target form submission.
	$(document).on('click', '[data-licenser-submit-form]', function (e) {
		e.preventDefault();
		var formSelector = $(this).attr('data-licenser-submit-form');
		var $form = $(formSelector);
		if ($form.length) {
			if (typeof $form[0].requestSubmit === 'function') {
				$form[0].requestSubmit();
			} else {
				$form.trigger('submit');
			}
		}
	});

	// -------------------------------------------------------------------------
	// Media Frame File Picker + Live Image Previews
	// -------------------------------------------------------------------------
	var fileFrame;
	var activeMediaBtn = null;

	function setMediaState(state) {
		if (!cfg.media_state_nonce) {
			return;
		}
		$.post(cfg.ajaxurl || ajaxurl, {
			action: 'licenser_media_frame_state',
			nonce: cfg.media_state_nonce,
			state: state || 'close'
		});
	}

	function updateMediaPreview($input) {
		var previewSelector = $input.attr('data-preview-target');
		if (!previewSelector) {
			return;
		}
		var $box = $(previewSelector);
		if (!$box.length) {
			return;
		}

		var url = String($input.val() || '').trim();
		if (url && /^https?:\/\//i.test(url)) {
			$box.find('.licenser-media-preview__empty').hide();
			var $img = $box.find('img');
			if (!$img.length) {
				$img = $('<img alt="" />');
				$box.append($img);
			}
			$img.attr('src', url).show();
		} else {
			$box.find('img').hide();
			$box.find('.licenser-media-preview__empty').show();
		}
	}

	$(document).on('input change', '[data-preview-target]', function () {
		updateMediaPreview($(this));
	});

	$(document).on('click', '.licenser-clear-media', function (e) {
		e.preventDefault();
		var selector = $(this).attr('data-target');
		if (selector) {
			$(selector).val('').trigger('change');
		}
	});

	$(document).on('click', '.trigger_media_frame', function (e) {
		e.preventDefault();
		activeMediaBtn = $(this);
		var isReleaseZip = activeMediaBtn.attr('data-media-scope') === 'release';

		if (isReleaseZip) {
			setMediaState('open');
		}

		if (undefined !== fileFrame) {
			fileFrame.open();
			return;
		}

		fileFrame = wp.media.frames.file_frame = wp.media({
			title: msg('select_media_title', 'Select File'),
			multiple: false,
			library: {},
			button: { text: msg('select_media_button', 'Use File') }
		});

		fileFrame.on('select', function () {
			if (!activeMediaBtn) {
				return;
			}
			var attachment = fileFrame.state().get('selection').first().toJSON();
			var pushSelector = activeMediaBtn.attr('data-push_selector');
			var filenameSelector = activeMediaBtn.attr('data-filename_selector');

			if (pushSelector && attachment && attachment.url) {
				$(pushSelector).val(attachment.url).trigger('change');
			}
			if (filenameSelector && attachment && attachment.filename && !$(filenameSelector).val()) {
				$(filenameSelector).val(attachment.filename);
			}
		});

		fileFrame.on('close', function () {
			setMediaState('close');
		});

		fileFrame.open();
	});

	// -------------------------------------------------------------------------
	// Products List: Safe Delete & Quick Archive / Restore
	// -------------------------------------------------------------------------
	$(document).on('click', '.licenser-delete-product', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var productId = parseInt($btn.attr('data-id'), 10);
		var productName = $btn.attr('data-name') || '';
		var releases = parseInt($btn.attr('data-releases'), 10) || 0;
		var packages = parseInt($btn.attr('data-packages'), 10) || 0;
		var licenses = parseInt($btn.attr('data-licenses'), 10) || 0;
		var $row = $btn.closest('tr');

		if (!productId) {
			return;
		}

		var details = [
			{ label: releases + ' Releases', variant: 'neutral' },
			{ label: packages + ' Packages', variant: 'neutral' },
			{ label: licenses + ' Licenses', variant: licenses > 0 ? 'warning' : 'neutral' }
		];

		var modalOpts = {
			title: productName ? msg('confirm_delete_title', 'Confirm Deletion') + ': ' + productName : msg('confirm_delete_title', 'Confirm Deletion'),
			message: licenses > 0 ? msg('confirm_delete_product_warn', 'This product has issued licenses.') : msg('confirm_delete_product', 'Are you sure you want to delete this product?'),
			details: details,
			checkboxLabel: releases > 0 || packages > 0 ? msg('cascade_checkbox', 'Also delete linked releases and license packages') : '',
			checkboxChecked: licenses === 0,
			confirmLabel: licenses > 0 ? msg('delete_anyway', 'Delete Anyway') : msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function (res) {
				var cascadeParam = res && res.checked ? '?cascade=1' : '';
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'products/' + productId + cascadeParam,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$row.css('opacity', '0.5');
					},
					success: function () {
						showToast(msg('product_deleted', 'Product deleted successfully.'), 'success');
						$row.fadeOut(180, function () {
							$row.remove();
						});
					},
					error: function (xhr) {
						$row.css('opacity', '1');
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		};

		if (licenses > 0) {
			modalOpts.secondaryAction = {
				label: msg('archive_instead', 'Archive Instead'),
				variant: 'primary',
				onClick: function () {
					$.ajax({
						type: 'POST',
						url: cfg.rest_url + 'products/' + productId + '/status',
						contentType: 'application/json',
						data: JSON.stringify({ status: 'draft' }),
						beforeSend: function (xhr) {
							xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
							$row.css('opacity', '0.6');
						},
						success: function () {
							showToast(msg('product_archived', 'Product archived (set to Draft).'), 'success');
							window.location.reload();
						},
						error: function (xhr) {
							$row.css('opacity', '1');
							showToast(extractErrorMessage(xhr), 'error');
						}
					});
				}
			};
		}

		openConfirmModal(modalOpts);
	});

	$(document).on('click', '.licenser-toggle-product-status', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var productId = parseInt($btn.attr('data-id'), 10);
		var targetStatus = $btn.attr('data-status') || 'draft';

		if (!productId) {
			return;
		}

		var isRestore = targetStatus === 'active';
		openConfirmModal({
			title: isRestore ? msg('restore_label', 'Restore') : msg('archive_label', 'Archive'),
			message: isRestore
				? msg('confirm_restore_product', 'Restore this product to Active status?')
				: msg('confirm_archive_product', 'Archive this product (set to Draft)?'),
			confirmLabel: isRestore ? msg('restore_label', 'Restore') : msg('archive_label', 'Archive'),
			confirmVariant: isRestore ? 'primary' : 'danger-solid',
			onConfirm: function () {
				$.ajax({
					type: 'POST',
					url: cfg.rest_url + 'products/' + productId + '/status',
					contentType: 'application/json',
					data: JSON.stringify({ status: targetStatus }),
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
					},
					success: function () {
						showToast(
							isRestore
								? msg('product_restored', 'Product restored to Active.')
								: msg('product_archived', 'Product archived (set to Draft).'),
							'success'
						);
						window.location.reload();
					},
					error: function (xhr) {
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	// -------------------------------------------------------------------------
	// Product Editor: Save, Releases CRUD, Packages CRUD
	// -------------------------------------------------------------------------
	$(document).on('input', '#product-form #slug', function () {
		var value = $(this).val().replace(/\s+/g, '-').replace(/[^a-zA-Z0-9-_]/g, '');
		$(this).val(value);
	});

	$(document).on('submit', '#product-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var jsonObject = formDataToJson(this);
		var productType = $('#product_type').val() || 'plugin';
		var $saveBtns = $form.find('[type="submit"]').add('[data-licenser-submit-form="#product-form"]');

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'products',
			data: JSON.stringify(jsonObject),
			contentType: 'application/json',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$saveBtns.prop('disabled', true).addClass('is-loading');
				$form.find('.licenser-inline-spinner').removeAttr('hidden');
				showInlineNotice('', 'remove');
			},
			complete: function () {
				$saveBtns.prop('disabled', false).removeClass('is-loading');
				$form.find('.licenser-inline-spinner').attr('hidden', true);
			},
			success: function (res) {
				if ($('.licenser-edit-id').val()) {
					showToast(msg('product_updated', 'Product updated successfully.'), 'success');
					if (res && Array.isArray(res.releases) && $('.licenser-add-release-form').children().length) {
						renderReleasesList(res.releases);
						$('.licenser-add-release-form').empty();
					}
					if (res && Array.isArray(res.packages)) {
						hydratePackages(res.packages);
					}
				} else if (res && res.id) {
					showToast(msg('product_added', 'Product created successfully. Redirecting...'), 'success');
					window.location = cfg.admin_url + '?page=licenser-' + productType + 's&action=edit&id=' + res.id + '&message=1';
				}
			},
			error: function (xhr) {
				var errMsg = extractErrorMessage(xhr);
				showToast(errMsg, 'error');
				showInlineNotice(errMsg, 'error');
			}
		});
	});

	// Packages hydration & interactive live summary
	function hydratePackages(packages) {
		var $container = $('#license-packages-fields');
		if (!$container.length) {
			return;
		}
		$container.empty();
		if (!Array.isArray(packages) || !packages.length) {
			$('.licenser-packages-empty').show();
			return;
		}
		$('.licenser-packages-empty').hide();
		packages.forEach(function (pkg) {
			var html = renderTemplate('licenser-tmpl-license-package-field', {
				field_id: pkg.id || licenserGenerateUniqueId(),
				id: pkg.id || '',
				package_id: pkg.package_id || '',
				label: pkg.label || '',
				update_period: pkg.update_period != null ? pkg.update_period : '',
				domain_limit: pkg.domain_limit != null ? pkg.domain_limit : '',
				summary: formatPackageSummary(pkg.update_period, pkg.domain_limit)
			});
			var $card = $(html);
			if (!pkg.package_id) {
				$card.find('.licenser-package-uuid-wrap').hide();
			}
			$container.append($card);
		});
	}

	var $pkgHost = $('#license-packages-fields');
	if ($pkgHost.length) {
		var rawPackages = $pkgHost.attr('data-packages');
		if (rawPackages) {
			try {
				hydratePackages(JSON.parse(rawPackages));
			} catch (err) {
				// Ignore malformed JSON.
			}
		}
	}

	$(document).on('click', '.licenser-add-package-btn', function (e) {
		e.preventDefault();
		$('.licenser-packages-empty').hide();
		var html = renderTemplate('licenser-tmpl-license-package-field', {
			field_id: licenserGenerateUniqueId(),
			id: '',
			package_id: '',
			label: '',
			update_period: '365',
			domain_limit: '1',
			summary: formatPackageSummary(365, 1)
		});
		var $card = $(html).addClass('is-open');
		$card.find('.licenser-package-uuid-wrap').hide();
		$('#license-packages-fields').append($card);
		$card.find('.licenser-package-name').trigger('focus');
	});

	$(document).on('input change', '.licenser-pkg-period, .licenser-pkg-limit', function () {
		var $card = $(this).closest('.licenser-item-card');
		var days = $card.find('.licenser-pkg-period').val();
		var sites = $card.find('.licenser-pkg-limit').val();
		$card.find('.licenser-pkg-summary').text(formatPackageSummary(days, sites));
	});

	$(document).on('click', '.licenser-remove-package-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $card = $(this).closest('.licenser-item-card');
		var pkgId = $card.find('.license-package-id').val();
		var pkgLabel = $card.find('.licenser-package-name').val() || '';

		openConfirmModal({
			title: pkgLabel ? msg('confirm_delete_title', 'Confirm Deletion') + ': ' + pkgLabel : msg('confirm_delete_title', 'Confirm Deletion'),
			message: msg('confirm_delete_package', 'Remove this license package?'),
			confirmLabel: msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function () {
				if (!pkgId) {
					$card.remove();
					if (!$('#license-packages-fields .licenser-item-card').length) {
						$('.licenser-packages-empty').show();
					}
					return;
				}
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'products/packages/' + pkgId,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$card.css('opacity', '0.5');
					},
					success: function () {
						$card.remove();
						if (!$('#license-packages-fields .licenser-item-card').length) {
							$('.licenser-packages-empty').show();
						}
						showToast(msg('package_deleted', 'License package removed.'), 'success');
					},
					error: function (xhr) {
						$card.css('opacity', '1');
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	// Releases Full History CRUD
	function formatReleaseDateShort(dateStr) {
		if (!dateStr) {
			return '';
		}
		var normalized = String(dateStr).replace(' ', 'T');
		var parsed = new Date(normalized);
		if (isNaN(parsed.getTime())) {
			return String(dateStr);
		}
		return parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
	}

	function renderReleasesList(releases) {
		var $list = $('#licenser-releases-list');
		if (!$list.length) {
			return;
		}
		$list.empty();
		if (!Array.isArray(releases) || !releases.length) {
			$('.licenser-releases-empty').show();
			return;
		}
		$('.licenser-releases-empty').hide();

		releases.forEach(function (rel, idx) {
			var isStable = idx === 0;
			var rawDate = rel.release_date ? String(rel.release_date).slice(0, 10) : '';
			var html = renderTemplate('licenser-tmpl-release-item', {
				id: rel.id || '',
				version: rel.version || '',
				badge_class: isStable ? 'licenser-badge--active' : 'licenser-badge--inactive',
				badge_label: isStable ? msg('stable', 'Stable') : msg('previous', 'Previous'),
				date_formatted: formatReleaseDateShort(rel.release_date),
				release_date_iso: rawDate,
				file_name: rel.file_name || '',
				download_link: rel.download_link || '',
				changelog: rel.changelog || ''
			});
			var $item = $(html);
			if (isStable) {
				$item.addClass('is-open');
			}
			$list.append($item);
		});
	}

	function openReleaseEditorForm(data) {
		var today = new Date().toISOString().slice(0, 10);
		var d = $.extend(
			{
				id: '',
				form_title: data && data.id ? 'Edit Release' : 'Add New Release',
				version: '',
				release_date: today,
				file_name: '',
				download_link: '',
				changelog: ''
			},
			data || {}
		);

		var html = renderTemplate('licenser-tmpl-release-form', d);
		$('.licenser-add-release-form').html(html);
		$('#licenser-rel-version').trigger('focus');
	}

	$(document).on('click', '.licenser-add-release-btn', function (e) {
		e.preventDefault();
		openReleaseEditorForm({});
	});

	$(document).on('click', '.licenser-edit-release-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $btn = $(this);
		openReleaseEditorForm({
			id: $btn.attr('data-id') || '',
			version: $btn.attr('data-version') || '',
			release_date: $btn.attr('data-date') || '',
			file_name: $btn.attr('data-filename') || '',
			download_link: $btn.attr('data-url') || '',
			changelog: $btn.closest('.licenser-item-card').find('.licenser-changelog-box').text() || ''
		});
	});

	$(document).on('click', '.licenser-cancel-release-btn', function (e) {
		e.preventDefault();
		$('.licenser-add-release-form').empty();
	});

	$(document).on('click', '.licenser-save-release-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var productId = parseInt($('.licenser-edit-id').val(), 10) || 0;
		var payload = {
			id: parseInt($('#licenser-rel-id').val(), 10) || 0,
			product_id: productId,
			version: String($('#licenser-rel-version').val() || '').trim(),
			release_date: String($('#licenser-rel-date').val() || '').trim(),
			file_name: String($('#licenser-rel-filename').val() || '').trim(),
			download_link: String($('#licenser-rel-url').val() || '').trim(),
			changelog: String($('#licenser-rel-changelog').val() || '')
		};

		if (!payload.version || !payload.download_link) {
			showToast('Version and File URL are required.', 'error');
			return;
		}

		// For brand-new unsaved products, keep fields in main form so saving the product creates both.
		if (!productId) {
			showToast('Release ready — click Add Product to save.', 'info');
			return;
		}

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'products/releases',
			contentType: 'application/json',
			data: JSON.stringify(payload),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function (res) {
				if (res && Array.isArray(res.releases)) {
					renderReleasesList(res.releases);
				}
				$('.licenser-add-release-form').empty();
				showToast(msg('release_saved', 'Release saved successfully.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	$(document).on('click', '.licenser-delete-release-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $btn = $(this);
		var releaseId = parseInt($btn.attr('data-id'), 10);
		var version = $btn.attr('data-version') || '';
		var $card = $btn.closest('.licenser-item-card');

		if (!releaseId) {
			return;
		}

		openConfirmModal({
			title: version ? msg('confirm_delete_title', 'Confirm Deletion') + ': v' + version : msg('confirm_delete_title', 'Confirm Deletion'),
			message: msg('confirm_delete_release', 'Delete this release entry?'),
			confirmLabel: msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function () {
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'products/releases/' + releaseId,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$card.css('opacity', '0.5');
					},
					success: function () {
						$card.remove();
						var $first = $('#licenser-releases-list .licenser-item-card').first();
						if ($first.length) {
							$first
								.find('.licenser-release-badge')
								.removeClass('licenser-badge--inactive')
								.addClass('licenser-badge--active')
								.find('.licenser-release-badge-text')
								.text(msg('stable', 'Stable'));
						} else {
							$('.licenser-releases-empty').show();
						}
						showToast(msg('release_deleted', 'Release deleted successfully.'), 'success');
					},
					error: function (xhr) {
						$card.css('opacity', '1');
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	// -------------------------------------------------------------------------
	// Licenses List: Delete, Quick Toggle & Bulk Generator Modal
	// -------------------------------------------------------------------------
	$(document).on('click', '.licenser-delete-license', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var licenseId = parseInt($btn.attr('data-id'), 10);
		var redirectOnSuccess = $btn.attr('data-redirect') || '';
		var $row = $btn.closest('tr');

		if (!licenseId) {
			return;
		}

		openConfirmModal({
			title: msg('confirm_delete_title', 'Confirm Deletion'),
			message: msg('confirm_delete_license', 'Delete this license key?'),
			confirmLabel: msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function () {
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'licenses/' + licenseId,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						if ($row.length) {
							$row.css('opacity', '0.5');
						}
					},
					success: function () {
						showToast(msg('license_deleted', 'License deleted successfully.'), 'success');
						if (redirectOnSuccess) {
							window.location = redirectOnSuccess;
						} else if ($row.length) {
							$row.fadeOut(180, function () {
								$row.remove();
							});
						}
					},
					error: function (xhr) {
						if ($row.length) {
							$row.css('opacity', '1');
						}
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	// Bulk License Generator Modal (with focus trap & focus restoration)
	var bulkModalReturnFocus = null;

	function closeBulkModal() {
		var $modal = $('#licenser-bulk-modal');
		$modal.removeClass('is-open');
		$(document).off('keydown.licenserBulkModal');
		if (bulkModalReturnFocus && typeof bulkModalReturnFocus.focus === 'function') {
			bulkModalReturnFocus.focus();
			bulkModalReturnFocus = null;
		}
	}

	$(document).on('click', '.licenser-open-bulk-modal', function (e) {
		e.preventDefault();
		var $modal = $('#licenser-bulk-modal');
		if (!$modal.length) {
			return;
		}
		bulkModalReturnFocus = document.activeElement;
		$modal.find('.licenser-bulk-results').attr('hidden', true);
		$modal.find('.licenser-bulk-results__list').text('');
		$modal.addClass('is-open');
		window.setTimeout(function () {
			$('#licenser-bulk-product').trigger('focus');
		}, 30);
		$(document).on('keydown.licenserBulkModal', function (ev) {
			if (ev.key === 'Escape') {
				closeBulkModal();
				return;
			}
			handleModalFocusTrap(ev, $modal);
		});
	});

	$(document).on('click', '#licenser-bulk-modal .licenser-modal-close', closeBulkModal);

	$(document).on('click', '#licenser-bulk-modal', function (e) {
		if (e.target === this) {
			closeBulkModal();
		}
	});

	$(document).on('change', '#licenser-bulk-lifetime', function () {
		$('#licenser-bulk-end-date').prop('disabled', $(this).is(':checked'));
	});

	$(document).on('change', '#licenser-bulk-product', function () {
		var productId = $(this).val();
		var $pkgSelect = $('#licenser-bulk-package');
		if (!productId) {
			$pkgSelect.html('<option value="">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>').prop('disabled', true);
			return;
		}
		$.ajax({
			type: 'GET',
			url: cfg.rest_url + 'products/packages',
			data: { product_id: productId },
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
			},
			success: function (packages) {
				var opts = '<option value="">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>';
				if (Array.isArray(packages) && packages.length) {
					packages.forEach(function (pkg) {
						opts +=
							'<option value="' +
							escapeHtml(pkg.id) +
							'" data-period="' +
							escapeHtml(pkg.update_period) +
							'" data-limit="' +
							escapeHtml(pkg.domain_limit) +
							'">' +
							escapeHtml(pkg.label) +
							' (' +
							escapeHtml(formatPackageSummary(pkg.update_period, pkg.domain_limit)) +
							')</option>';
					});
					$pkgSelect.prop('disabled', false);
				} else {
					opts = '<option value="">' + escapeHtml(msg('no_package_found', 'No Package Found')) + '</option>';
					$pkgSelect.prop('disabled', true);
				}
				$pkgSelect.html(opts);
			}
		});
	});

	$(document).on('submit', '#licenser-bulk-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $submit = $form.find('[type="submit"]');
		var payload = {
			product_id: parseInt($('#licenser-bulk-product').val(), 10) || 0,
			package_id: parseInt($('#licenser-bulk-package').val(), 10) || 0,
			quantity: parseInt($('#licenser-bulk-qty').val(), 10) || 10,
			status: parseInt($('#licenser-bulk-status').val(), 10),
			domain_limit: $('#licenser-bulk-limit').val(),
			is_lifetime: $('#licenser-bulk-lifetime').is(':checked') ? 1 : 0,
			end_date: $('#licenser-bulk-end-date').val() || '',
			source: $('#licenser-bulk-source').val() || 'bulk'
		};

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'licenses/bulk',
			contentType: 'application/json',
			data: JSON.stringify(payload),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$submit.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$submit.prop('disabled', false).removeClass('is-loading');
			},
			success: function (res) {
				var keys = (res && Array.isArray(res.licenses) ? res.licenses : []).map(function (item) {
					return item.license_key;
				});
				var keysText = keys.join('\n');
				var $results = $('.licenser-bulk-results');
				$results.find('.licenser-bulk-results__list').text(keysText);
				$results.find('.licenser-bulk-copy-all').attr('data-licenser-copy', keysText);
				$results.find('.licenser-bulk-count').text(keys.length);
				$results.removeAttr('hidden');
				showToast(msg('bulk_created', 'Licenses generated successfully.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	$(document).on('click', '.licenser-bulk-download-csv', function (e) {
		e.preventDefault();
		var text = $('.licenser-bulk-results__list').text() || '';
		if (!text) {
			return;
		}
		var csv = 'license_key\n' + text;
		var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
		var url = URL.createObjectURL(blob);
		var link = document.createElement('a');
		link.href = url;
		link.download = 'licenser-bulk-keys.csv';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		URL.revokeObjectURL(url);
	});

	// -------------------------------------------------------------------------
	// License Editor: Save, Key Gen, Package Autofill, Domains & Meta CRUD
	// -------------------------------------------------------------------------
	function syncLifetimeToggle() {
		var isLife = $('#is_lifetime').is(':checked');
		$('#end_date').prop('disabled', isLife);
		$('.licenser-preset-row').toggle(!isLife);
	}

	$(document).on('change', '#is_lifetime', syncLifetimeToggle);

	$(document).on('click', '.licenser-preset-btn', function (e) {
		e.preventDefault();
		var days = parseInt($(this).attr('data-days'), 10) || 365;
		var target = new Date(Date.now() + days * 86400000);
		var iso =
			target.getFullYear() +
			'-' +
			String(target.getMonth() + 1).padStart(2, '0') +
			'-' +
			String(target.getDate()).padStart(2, '0') +
			'T' +
			String(target.getHours()).padStart(2, '0') +
			':' +
			String(target.getMinutes()).padStart(2, '0');
		$('#is_lifetime').prop('checked', false);
		syncLifetimeToggle();
		$('#end_date').val(iso);
	});

	$(document).on('submit', '#license-add-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var jsonObject = formDataToJson(this);
		jsonObject.is_lifetime = $('#is_lifetime').is(':checked') ? 1 : 0;
		var $saveBtns = $form.find('[type="submit"]').add('[data-licenser-submit-form="#license-add-form"]');

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'licenses',
			data: JSON.stringify(jsonObject),
			contentType: 'application/json',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$saveBtns.prop('disabled', true).addClass('is-loading');
				$form.find('.licenser-inline-spinner').removeAttr('hidden');
				showInlineNotice('', 'remove');
			},
			complete: function () {
				$saveBtns.prop('disabled', false).removeClass('is-loading');
				$form.find('.licenser-inline-spinner').attr('hidden', true);
			},
			success: function (res) {
				if ($('.licenser-edit-id').val()) {
					showToast(msg('license_updated', 'License updated successfully.'), 'success');
				} else if (res && res.id) {
					showToast(msg('license_added', 'License issued successfully. Redirecting...'), 'success');
					window.location = cfg.admin_url + '?page=licenser-licenses&action=edit&id=' + res.id + '&message=1';
				}
			},
			error: function (xhr) {
				var errMsg = extractErrorMessage(xhr);
				showToast(errMsg, 'error');
				showInlineNotice(errMsg, 'error');
			}
		});
	});

	$(document).on('click', '#generate_key', function (e) {
		e.preventDefault();
		var $btn = $(this);

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'licenses/generate-key',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function (data) {
				$('#license_key').val(data);
				$('.licenser-copy-key-btn').attr('data-licenser-copy', data);
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	$(document).on('change', '#license-add-form #product_type', function (e, isEdit) {
		var thisVal = $(this).val();
		$('#license-add-form .theme-opt, #license-add-form .plugin-opt').hide();

		if (!isEdit) {
			$('#license-add-form .licenser-products-select').val('');
			$('#licenser_package_list').val('');
		}

		if (thisVal) {
			$('#license-add-form .' + thisVal + '-opt').show();
		} else {
			$('#license-add-form .theme-opt, #license-add-form .plugin-opt').show();
		}
	});

	$(document).on('change', '#license-add-form .licenser-products-select', function (e, isEdit) {
		var $this = $(this);
		var $packageSelect = $('#licenser_package_list');
		var selected = $packageSelect.attr('data-pack_value') || '';

		if (!$this.val()) {
			$packageSelect
				.html('<option value="" class="blank">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>')
				.prop('disabled', true);
			return;
		}

		$.ajax({
			type: 'GET',
			data: { product_id: $this.val() },
			url: cfg.rest_url + 'products/packages',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
			},
			success: function (packages) {
				var options = '';
				if (Array.isArray(packages) && packages.length) {
					options = '<option value="" class="blank">' + escapeHtml(msg('select_package', 'Select Package')) + '</option>';
					packages.forEach(function (pkg) {
						options +=
							'<option value="' +
							escapeHtml(pkg.id) +
							'" data-period="' +
							escapeHtml(pkg.update_period) +
							'" data-limit="' +
							escapeHtml(pkg.domain_limit) +
							'">' +
							escapeHtml(pkg.label) +
							' (' +
							escapeHtml(formatPackageSummary(pkg.update_period, pkg.domain_limit)) +
							')</option>';
					});
					$packageSelect.prop('disabled', false);
				} else {
					options = '<option value="" class="blank">' + escapeHtml(msg('no_package_found', 'No Package Found')) + '</option>';
					$packageSelect.prop('disabled', true);
				}

				$packageSelect.html(options);
				if (isEdit && selected) {
					$packageSelect.val(selected);
				}
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	// Auto-populate defaults from selected package on new licenses.
	$(document).on('change', '#licenser_package_list', function () {
		if ($('.licenser-edit-id').val()) {
			return;
		}
		var $opt = $(this).find('option:selected');
		if (!$opt.val()) {
			return;
		}
		var period = parseInt($opt.attr('data-period'), 10) || 0;
		var limit = parseInt($opt.attr('data-limit'), 10) || 0;

		$('#domain_limit').val(limit);
		if (period > 0) {
			var target = new Date(Date.now() + period * 86400000);
			var iso =
				target.getFullYear() +
				'-' +
				String(target.getMonth() + 1).padStart(2, '0') +
				'-' +
				String(target.getDate()).padStart(2, '0') +
				'T' +
				String(target.getHours()).padStart(2, '0') +
				':' +
				String(target.getMinutes()).padStart(2, '0');
			$('#is_lifetime').prop('checked', false);
			syncLifetimeToggle();
			$('#end_date').val(iso);
		} else {
			$('#is_lifetime').prop('checked', true);
			syncLifetimeToggle();
			$('#end_date').val('');
		}
	});

	// Add Domain manually
	$(document).on('click', '#licenser-add-domain-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var licenseId = parseInt($btn.attr('data-license-id'), 10) || 0;
		var domainVal = String($('#licenser-new-domain-input').val() || '').trim();

		if (!licenseId || !domainVal) {
			showToast('Please enter a domain name or URL.', 'error');
			return;
		}

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'licenses/' + licenseId + '/domains',
			contentType: 'application/json',
			data: JSON.stringify({ domain: domainVal, status: 1 }),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function (res) {
				$('#licenser-new-domain-input').val('');
				$('.licenser-domains-empty').hide();
				var html = renderTemplate('licenser-tmpl-domain-row', {
					id: res.id,
					domain: res.domain,
					href: res.href || '',
					dated: res.dated || ''
				});
				$('#licenser-domains-list').prepend(html);
				updateActiveDomainsCountUI();
				showToast(msg('domain_added', 'Domain added successfully.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	// Toggle Domain Active / Inactive (confirmation-guarded)
	$(document).on('click', '.licenser-toggle-domain-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var domainId = parseInt($btn.attr('data-id'), 10);
		var currentStatus = parseInt($btn.attr('data-status'), 10) === 1 ? 1 : 0;
		var nextStatus = currentStatus === 1 ? 0 : 1;
		var $row = $btn.closest('.licenser-domain-row');
		var domainName = $btn.attr('data-domain') || $row.find('.licenser-domain-row__url').text().trim();

		if (!domainId) {
			return;
		}

		var isDeactivating = nextStatus === 0;
		var actionLabel = isDeactivating ? msg('deactivate', 'Deactivate') : msg('activate', 'Activate');

		openConfirmModal({
			title: domainName ? actionLabel + ': ' + domainName : msg('confirm_action_title', 'Confirm Action'),
			message: isDeactivating
				? msg('confirm_deactivate_domain', 'Deactivating this domain will block license checks and updates until reactivated.')
				: msg('confirm_activate_domain', 'Reactivate licensing and update access for this domain?'),
			confirmLabel: actionLabel,
			confirmVariant: isDeactivating ? 'danger-solid' : 'primary',
			onConfirm: function () {
				$.ajax({
					type: 'POST',
					url: cfg.rest_url + 'licenses/domains/' + domainId,
					contentType: 'application/json',
					data: JSON.stringify({ status: nextStatus }),
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$btn.prop('disabled', true);
					},
					complete: function () {
						$btn.prop('disabled', false);
					},
					success: function () {
						$btn.attr('data-status', nextStatus);
						$btn.text(nextStatus === 1 ? msg('deactivate', 'Deactivate') : msg('activate', 'Activate'));
						$row.attr('data-status', nextStatus);
						var $badge = $row.find('.licenser-domain-status-badge');
						$badge
							.toggleClass('licenser-badge--active', nextStatus === 1)
							.toggleClass('licenser-badge--inactive', nextStatus === 0)
							.find('.licenser-badge-label')
							.text(nextStatus === 1 ? msg('active', 'Active') : msg('inactive', 'Inactive'));
						updateActiveDomainsCountUI();
						showToast(msg('domain_updated', 'Domain status updated.'), 'success');
					},
					error: function (xhr) {
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	// Delete Domain
	$(document).on('click', '.licenser-delete-domain-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var domainId = parseInt($btn.attr('data-id'), 10);
		var domainName = $btn.attr('data-domain') || '';
		var $row = $btn.closest('.licenser-domain-row');

		if (!domainId) {
			return;
		}

		openConfirmModal({
			title: domainName ? msg('confirm_delete_title', 'Confirm Deletion') + ': ' + domainName : msg('confirm_delete_title', 'Confirm Deletion'),
			message: msg('confirm_delete_domain', 'Remove this activated domain from the license?'),
			confirmLabel: msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function () {
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'licenses/domains/' + domainId,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$row.css('opacity', '0.5');
					},
					success: function () {
						$row.remove();
						if (!$('#licenser-domains-list .licenser-domain-row').length) {
							$('.licenser-domains-empty').show();
						}
						updateActiveDomainsCountUI();
						showToast(msg('domain_deleted', 'Domain removed successfully.'), 'success');
					},
					error: function (xhr) {
						$row.css('opacity', '1');
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	function updateActiveDomainsCountUI() {
		var activeCount = $('#licenser-domains-list .licenser-domain-row[data-status="1"]').length;
		$('.licenser-active-domain-count').text(activeCount);
	}

	// Add Custom Meta Row
	$(document).on('click', '#licenser-add-meta-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var licenseId = parseInt($btn.attr('data-license-id'), 10) || 0;
		var metaKey = String($('#licenser-new-meta-key').val() || '').trim();
		var metaValue = String($('#licenser-new-meta-value').val() || '').trim();

		if (!licenseId || !metaKey) {
			showToast('Meta key is required.', 'error');
			return;
		}

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'licenses/' + licenseId + '/meta',
			contentType: 'application/json',
			data: JSON.stringify({ meta_key: metaKey, meta_value: metaValue }),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function (res) {
				$('#licenser-new-meta-key').val('');
				$('#licenser-new-meta-value').val('');
				$('.licenser-meta-empty').hide();

				if (res && res.id) {
					$('#licenser-meta-list .licenser-meta-row[data-id="' + res.id + '"]').remove();
					var html = renderTemplate('licenser-tmpl-meta-row', {
						id: res.id,
						meta_key: res.meta_key,
						meta_value: res.meta_value
					});
					$('#licenser-meta-list').append(html);
				}
				showToast(msg('meta_added', 'Metadata saved successfully.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	// Delete Meta Row
	$(document).on('click', '.licenser-delete-meta-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var metaId = parseInt($btn.attr('data-id'), 10);
		var $row = $btn.closest('.licenser-meta-row');

		if (!metaId) {
			return;
		}

		openConfirmModal({
			title: msg('confirm_delete_title', 'Confirm Deletion'),
			message: msg('confirm_delete_meta', 'Delete this metadata entry?'),
			confirmLabel: msg('delete_label', 'Delete'),
			confirmVariant: 'danger-solid',
			onConfirm: function () {
				$.ajax({
					type: 'DELETE',
					url: cfg.rest_url + 'licenses/meta/' + metaId,
					beforeSend: function (xhr) {
						xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
						$row.css('opacity', '0.5');
					},
					success: function () {
						$row.remove();
						if (!$('#licenser-meta-list .licenser-meta-row').length) {
							$('.licenser-meta-empty').show();
						}
						showToast(msg('meta_deleted', 'Metadata entry deleted.'), 'success');
					},
					error: function (xhr) {
						$row.css('opacity', '1');
						showToast(extractErrorMessage(xhr), 'error');
					}
				});
			}
		});
	});

	if ($('#license-add-form').length) {
		syncLifetimeToggle();
		if ($('#license_key').val() === '') {
			$('#generate_key').trigger('click');
		}
		if ($('#license-add-form #product_type').val()) {
			$('#license-add-form #product_type').trigger('change', [true]);
		}
		if ($('#license-add-form .licenser-products-select').val()) {
			$('#license-add-form .licenser-products-select').trigger('change', [true]);
		}
	}

	// -------------------------------------------------------------------------
	// Settings Form: Live Sample Key Preview & Cache Clear
	// -------------------------------------------------------------------------
	function generateSampleKeyPreview() {
		var $preview = $('#licenser-sample-key');
		if (!$preview.length) {
			return;
		}
		var prefix = String($('#license_code_prefix').val() || '');
		var method = $('input[name="license_generate_method"]:checked').val() || 'microtime';
		var limit = parseInt($('#license_code_character_limit').val(), 10) || 32;
		if (limit < 8) {
			limit = 8;
		}
		if (limit > 64) {
			limit = 64;
		}

		// Toggle character limit disabled state for UUID/microtime hint clarity.
		$('#license_code_character_limit').prop('disabled', method !== 'wp_generate');

		var sample = '';
		if (method === 'uuid') {
			var hex = licenserGenerateUniqueId();
			sample =
				hex.slice(0, 8) +
				'-' +
				hex.slice(8, 12) +
				'-4' +
				hex.slice(13, 16) +
				'-a' +
				hex.slice(17, 20) +
				'-' +
				hex.slice(20, 32);
		} else if (method === 'wp_generate') {
			var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
			for (var i = 0; i < limit; i++) {
				sample += chars.charAt(Math.floor(Math.random() * chars.length));
			}
		} else {
			sample = licenserGenerateUniqueId().slice(0, 32);
		}

		$preview.text(prefix + sample);
	}

	if ($('#setting-add-form').length) {
		generateSampleKeyPreview();
	}

	$(document).on('input change', '#license_code_prefix, input[name="license_generate_method"], #license_code_character_limit', generateSampleKeyPreview);
	$(document).on('click', '#licenser-regen-sample', function (e) {
		e.preventDefault();
		generateSampleKeyPreview();
	});

	$(document).on('submit', '#setting-add-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $btn = $form.find('[type="submit"]').add('[data-licenser-submit-form="#setting-add-form"]');

		// Re-enable character limit briefly so FormData captures its value.
		var wasDisabled = $('#license_code_character_limit').prop('disabled');
		$('#license_code_character_limit').prop('disabled', false);
		var jsonObject = formDataToJson(this);
		$('#license_code_character_limit').prop('disabled', wasDisabled);

		['hide_wclm_info_from_cart', 'hide_wclm_info_from_ordermeta', 'allow_customer_domain_deactivation', 'auto_suspend_on_refund'].forEach(
			function (toggleKey) {
				jsonObject[toggleKey] = $form.find('[name="' + toggleKey + '"]').is(':checked') ? 'on' : '';
			}
		);

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'settings',
			data: JSON.stringify(jsonObject),
			contentType: 'application/json',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
				$form.find('.licenser-inline-spinner').removeAttr('hidden');
				showInlineNotice('', 'remove');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
				$form.find('.licenser-inline-spinner').attr('hidden', true);
			},
			success: function () {
				showToast(msg('settings_saved', 'Settings saved successfully.'), 'success');
			},
			error: function (xhr) {
				var errMsg = extractErrorMessage(xhr);
				showToast(errMsg, 'error');
				showInlineNotice(errMsg, 'error');
			}
		});
	});

	// Clear Product Cache button (Dashboard & Settings)
	$(document).on('click', '.licenser-clear-cache-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'settings/clear-cache',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function () {
				showToast(msg('cache_cleared', 'Product cache cleared successfully.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	// -------------------------------------------------------------------------
	// Addons & Integrations: Enable / Disable Store Integrations
	// -------------------------------------------------------------------------
	function submitIntegrationToggle($input, slug, enabled) {
		var $card = $input.closest('.licenser-addon-card');
		var depName = $card.attr('data-dependency-name') || '';

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'settings/integrations',
			contentType: 'application/json',
			data: JSON.stringify({
				integration: slug,
				enabled: !!enabled
			}),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$input.prop('disabled', true);
			},
			complete: function () {
				$input.prop('disabled', false);
			},
			success: function (res) {
				var isEnabled = !!(res && res.enabled);
				var status = (res && res.status) || (isEnabled ? 'active' : 'disabled');
				var $badge = $card.find('.licenser-addon-status-badge');
				var $label = $card.find('.licenser-addon-toggle__label');

				$input.prop('checked', isEnabled);
				$card.toggleClass('licenser-addon-card--disabled', !isEnabled);
				$label.text(isEnabled ? msg('enabled_label', 'Enabled') : msg('disabled_label', 'Disabled'));

				if (isEnabled) {
					$card.find('.licenser-addon-configure-btn').removeAttr('hidden');
					$card.find('.licenser-addon-unloaded-note').attr('hidden', true);
				} else {
					$card.find('.licenser-addon-configure-btn').attr('hidden', true);
					$card.find('.licenser-addon-unloaded-note').removeAttr('hidden');
				}

				$badge.removeClass('licenser-badge--active licenser-badge--inactive licenser-badge--warning');
				if (status === 'active') {
					$badge.addClass('licenser-badge--active').find('.licenser-badge-label').text(msg('active', 'Active'));
				} else if (status === 'dependency_missing') {
					var reqLabel = depName ? msg('requires_prefix', 'Requires') + ' ' + depName : msg('inactive', 'Inactive');
					$badge.addClass('licenser-badge--warning').find('.licenser-badge-label').text(reqLabel);
				} else {
					$badge.addClass('licenser-badge--inactive').find('.licenser-badge-label').text(msg('disabled_label', 'Disabled'));
				}

				showToast(
					isEnabled
						? msg('integration_enabled', 'Integration enabled.')
						: msg('integration_disabled', 'Integration disabled. Related integration files will no longer be loaded.'),
					'success'
				);
			},
			error: function (xhr) {
				$input.prop('checked', !enabled);
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	}

	$(document).on('change', '.licenser-integration-toggle', function () {
		var $input = $(this);
		var slug = $input.attr('data-integration') || '';
		var name = $input.attr('data-name') || slug;
		var nextEnabled = $input.is(':checked');

		if (!slug) {
			return;
		}

		if (!nextEnabled) {
			// Hold the switch visually on until confirmed, so cancelling leaves it cleanly on.
			$input.prop('checked', true);
			openConfirmModal({
				title: msg('disable_label', 'Disable') + ': ' + name,
				message: msg(
					'confirm_disable_integration',
					'Disabling this integration stops loading its PHP files, admin hooks, and storefront features until re-enabled. Existing licenses remain untouched.'
				),
				confirmLabel: msg('disable_label', 'Disable'),
				confirmVariant: 'danger-solid',
				onConfirm: function () {
					submitIntegrationToggle($input, slug, false);
				}
			});
			return;
		}

		submitIntegrationToggle($input, slug, true);
	});

	// One-click "Enable WooCommerce Lite" button inside Settings → WooCommerce tab callout
	$(document).on('click', '.licenser-quick-enable-integration', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var slug = $btn.attr('data-integration') || '';
		if (!slug) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: cfg.rest_url + 'settings/integrations',
			contentType: 'application/json',
			data: JSON.stringify({
				integration: slug,
				enabled: true
			}),
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
				$btn.prop('disabled', true).addClass('is-loading');
			},
			complete: function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			},
			success: function (res) {
				var status = (res && res.status) || 'active';
				$('#licenser-wc-disabled-callout').attr('hidden', true);

				var $badge = $('#licenser-wc-settings-badge');
				$badge.removeClass('licenser-badge--active licenser-badge--inactive licenser-badge--warning');
				var $dot = $('#licenser-wc-tab-dot');
				$dot.removeClass('licenser-tab__dot--active licenser-tab__dot--inactive licenser-tab__dot--warning');

				if (status === 'active') {
					$badge.addClass('licenser-badge--active').find('.licenser-badge-label').text('WooCommerce Active');
					$dot.addClass('licenser-tab__dot--active');
				} else {
					var depName = (res && res.dependency_name) || 'WooCommerce';
					$badge.addClass('licenser-badge--warning').find('.licenser-badge-label').text(msg('requires_prefix', 'Requires') + ' ' + depName);
					$dot.addClass('licenser-tab__dot--warning');
				}

				showToast(msg('integration_enabled', 'Integration enabled.'), 'success');
			},
			error: function (xhr) {
				showToast(extractErrorMessage(xhr), 'error');
			}
		});
	});

	// -------------------------------------------------------------------------
	// SDK Studio, Settings Tabs & Live API Diagnostics Tester
	// -------------------------------------------------------------------------
	function activateTab($tab) {
		var tab = $tab.attr('data-licenser-tab');
		var $list = $tab.closest('[role="tablist"]');
		var $tabs = $list.length ? $list.find('[data-licenser-tab]') : $('[data-licenser-tab]');
		$tabs.removeClass('is-active').attr('aria-selected', 'false');
		$tab.addClass('is-active').attr('aria-selected', 'true');
		$('.licenser-tab-panel').removeClass('is-active');
		$('#licenser-tab-' + tab).addClass('is-active');

		if ($tab.attr('data-licenser-tab-sync') === '1' && window.history && window.history.replaceState) {
			try {
				var url = new URL(window.location.href);
				url.searchParams.set('tab', tab);
				window.history.replaceState({}, '', url.toString());
			} catch (err) {
				// Ignore URL API failures in restricted environments.
			}
		}
	}

	$(document).on('click', '[data-licenser-tab]', function (e) {
		e.preventDefault();
		activateTab($(this));
	});

	// WAI-ARIA keyboard navigation for tablists (ArrowLeft, ArrowRight, Home, End)
	$(document).on('keydown', '[role="tablist"] [data-licenser-tab]', function (e) {
		var $tabs = $(this).closest('[role="tablist"]').find('[data-licenser-tab]');
		var idx = $tabs.index(this);
		var nextIdx = -1;
		if (e.key === 'ArrowRight') {
			nextIdx = (idx + 1) % $tabs.length;
		} else if (e.key === 'ArrowLeft') {
			nextIdx = (idx - 1 + $tabs.length) % $tabs.length;
		} else if (e.key === 'Home') {
			nextIdx = 0;
		} else if (e.key === 'End') {
			nextIdx = $tabs.length - 1;
		}
		if (nextIdx >= 0) {
			e.preventDefault();
			var $target = $tabs.eq(nextIdx);
			activateTab($target);
			$target.trigger('focus');
		}
	});

	var sdkCodeMirror = null;

	function updateSdkModeVisibility() {
		var $menuSelect = $('#licenser_menu_select');
		if (!$menuSelect.length) {
			return;
		}
		var incLicensing = $('#inc-licensing').is(':checked');
		$('.licenser-sdk-licensing-options').toggle(incLicensing);

		var mode = $menuSelect.val();
		var $hint = $('#licenser-sdk-mode-hint');
		var hints = {
			menu: $hint.attr('data-hint-menu') || '',
			submenu: $hint.attr('data-hint-submenu') || '',
			options: $hint.attr('data-hint-options') || '',
			section: $hint.attr('data-hint-section') || ''
		};

		$hint.text(hints[mode] || '');
		$('.licenser-sdk-parent-slug-field').toggle(mode === 'submenu');
		$('.licenser-sdk-title-fields').toggle(mode !== 'section');
	}

	function renderSdkCode() {
		var $form = $('#sdk-generator-form');
		if (!$form.length) {
			return;
		}

		var productType = $('#sdk-generator-form #product_type').val() || 'plugin';
		var $selectedOpt = $('#select_product option:selected');
		var productId = $('#select_product').val() || '';
		var productName = productId ? $selectedOpt.text().trim() : 'My Awesome Product';
		var productSlug = productName.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'my_product';
		var menuType = $('#licenser_menu_select').val() || 'submenu';
		var parentSlug = $('#licenser_parent_menu_slug').val() || 'options-general.php';
		var pageTitle = $('#licenser_page_title').val() || productName + ' License Activation';
		var menuTitle = $('#licenser_menu_title').val() || productName + ' License';
		var apiUrl = cfg.sdk_api_url || window.location.origin;
		var incLicensing = $('#inc-licensing').is(':checked');
		var incUpdater = $('#inc-updater').is(':checked');
		var incInsights = $('#inc-insights').is(':checked');

		var displayUuid = productId || 'YOUR-PRODUCT-UUID-HERE';
		var stepHeading =
			productType === 'theme'
				? msg('sdk_step_theme', 'functions.php')
				: msg('sdk_step_plugin', 'your-plugin.php');

		$('#licenser-sdk-step-heading').text(stepHeading);

		var output =
			String.fromCharCode(60) +
			'?php\n' +
			'/**\n' +
			' * Initialize Licenser client.\n' +
			' *\n' +
			' * @return void\n' +
			' */\n' +
			'function ' +
			productSlug +
			'_licenser_client_init() {\n' +
			"\tif ( ! class_exists( 'Licenser\\\\Client' ) ) {\n" +
			"\t\trequire_once __DIR__ . '/licenser/src/Client.php';\n" +
			'\t}\n\n' +
			"\t$client = new Licenser\\Client(\n" +
			"\t\t'" +
			escapeSingleQuote(displayUuid) +
			"',\n" +
			"\t\t'" +
			escapeSingleQuote(productName) +
			"',\n" +
			'\t\t__FILE__,\n' +
			"\t\t'" +
			escapeSingleQuote(apiUrl) +
			"'\n" +
			'\t);\n';

		if (incLicensing) {
			output += '\n\t// Activate license page and checker.\n\t$license = $client->license();\n';
			if (menuType === 'section') {
				output += "\t$license->add_settings_page( [\n\t\t'type' => 'section',\n\t] );\n";
			} else if (menuType === 'submenu') {
				output +=
					"\t$license->add_settings_page( [\n" +
					"\t\t'type'        => 'submenu',\n" +
					"\t\t'menu_title'  => '" +
					escapeSingleQuote(menuTitle) +
					"',\n" +
					"\t\t'page_title'  => '" +
					escapeSingleQuote(pageTitle) +
					"',\n" +
					"\t\t'menu_slug'   => '" +
					escapeSingleQuote(productSlug) +
					"_settings',\n" +
					"\t\t'parent_slug' => '" +
					escapeSingleQuote(parentSlug) +
					"',\n" +
					'\t] );\n';
			} else {
				output +=
					"\t$license->add_settings_page( [\n" +
					"\t\t'type'       => '" +
					escapeSingleQuote(menuType) +
					"',\n" +
					"\t\t'menu_title' => '" +
					escapeSingleQuote(menuTitle) +
					"',\n" +
					"\t\t'page_title' => '" +
					escapeSingleQuote(pageTitle) +
					"',\n" +
					"\t\t'menu_slug'  => '" +
					escapeSingleQuote(productSlug) +
					"_settings',\n" +
					'\t] );\n';
			}
		}

		if (incUpdater) {
			output += '\n\t// Activate automatic updater.\n\t$client->updater()->init( $client );\n';
		}

		if (incInsights) {
			output += '\n\t// Activate usage insights.\n\t$client->insights()->init();\n';
		}

		output += "}\nadd_action( 'init', '" + productSlug + "_licenser_client_init' );";

		$('#licenser-copy-sdk-btn').attr('data-licenser-copy', output);

		var $textarea = $('#licenser-sdk-code-output');
		$textarea.val(output);

		if (sdkCodeMirror && sdkCodeMirror.codemirror) {
			sdkCodeMirror.codemirror.setValue(output);
		} else if (typeof wp !== 'undefined' && wp.codeEditor && typeof licenser_cm !== 'undefined') {
			sdkCodeMirror = wp.codeEditor.initialize($textarea[0], licenser_cm);
		}
	}

	$(document).on('change', '#sdk-generator-form #product_type', function () {
		var type = $(this).val();
		var $select = $('#select_product');
		$select.find('option').not('.blank').hide();
		if (type) {
			$select.find('option.' + type + '-opt').show();
		} else {
			$select.find('option').show();
		}
		var $firstVisible = $select.find('option.' + type + '-opt').first();
		$select.val($firstVisible.length ? $firstVisible.val() : '').trigger('change');
	});

	$(document).on('change', '#sdk-generator-form #select_product', function () {
		var val = $(this).val();
		var productName = $('#select_product option:selected').text().trim();
		if (!val) {
			$('#licenser_page_title').val('');
			$('#licenser_menu_title').val('License');
			$('#licenser-sdk-uuid-badge').text('—');
			$('#licenser-sdk-uuid-copy').attr('data-licenser-copy', '');
		} else {
			$('#licenser_page_title').val(productName + ' License');
			$('#licenser_menu_title').val('License');
			$('#licenser-sdk-uuid-badge').text(val);
			$('#licenser-sdk-uuid-copy').attr('data-licenser-copy', val);
		}
		renderSdkCode();
	});

	$(document).on('change input', '#sdk-generator-form #licenser_menu_select, #licenser_parent_menu_slug, #licenser_page_title, #licenser_menu_title, #inc-licensing, #inc-updater, #inc-insights', function () {
		updateSdkModeVisibility();
		renderSdkCode();
	});

	$(document).on('submit', '#sdk-generator-form', function (e) {
		e.preventDefault();
		renderSdkCode();
	});

	if ($('#sdk-generator-form').length) {
		updateSdkModeVisibility();
		renderSdkCode();
	}

	// API Diagnostics Tester
	$(document).on('change', '#licenser-diag-action', function () {
		var action = $(this).val();
		var isWrite = action === 'activate' || action === 'deactivate';
		$('#licenser-diag-write-warn').prop('hidden', !isWrite);
		$('#licenser-diag-url-wrap').toggle(action !== 'product');
	});

	$(document).on('submit', '#licenser-diag-form', function (e) {
		e.preventDefault();
		var action = $('#licenser-diag-action').val() || 'check';
		var uuid = $('#licenser-diag-product').val() || '';
		var key = String($('#licenser-diag-key').val() || '').trim();
		var siteUrl = String($('#licenser-diag-url').val() || '').trim();
		var $btn = $(this).find('[type="submit"]');

		if (!uuid) {
			showToast('Please select a product.', 'error');
			return;
		}

		var endpoint =
			action === 'product'
				? cfg.public_api_base + '/products/' + encodeURIComponent(uuid)
				: cfg.public_api_base + '/license/' + encodeURIComponent(uuid) + '/' + encodeURIComponent(action);

		var payload = {
			license_key: key,
			url: siteUrl,
			version: '1.0.0',
			client: '2.0.1'
		};

		var startTime = performance.now();
		$btn.prop('disabled', true).addClass('is-loading');
		$('#licenser-diag-output').text(msg('running_request', 'Running request...'));

		fetch(endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify(payload)
		})
			.then(function (response) {
				var status = response.status;
				var ok = response.ok;
				return response.text().then(function (rawText) {
					var elapsed = Math.round(performance.now() - startTime);
					var pretty = rawText;
					try {
						pretty = JSON.stringify(JSON.parse(rawText), null, 2);
					} catch (parseErr) {
						// Keep raw text if non-JSON.
					}

					var $statusBadge = $('#licenser-diag-status');
					$statusBadge
						.removeClass('licenser-badge--active licenser-badge--expired licenser-badge--inactive')
						.addClass(ok ? 'licenser-badge--active' : 'licenser-badge--expired')
						.text('HTTP ' + status);

					$('#licenser-diag-time').text(elapsed + ' ms');
					$('#licenser-diag-endpoint').text('POST ' + endpoint);
					$('#licenser-diag-output').text(pretty);
					$('#licenser-copy-diag-btn').attr('data-licenser-copy', pretty);
				});
			})
			.catch(function (err) {
				$('#licenser-diag-status')
					.removeClass('licenser-badge--active')
					.addClass('licenser-badge--expired')
					.text('Network Error');
				$('#licenser-diag-output').text(String(err));
			})
			.finally(function () {
				$btn.prop('disabled', false).removeClass('is-loading');
			});
	});
});
