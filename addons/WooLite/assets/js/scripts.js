jQuery(function ($) {
	'use strict';

	var cfg = typeof LicenserWc !== 'undefined' ? LicenserWc : {};
	var i18n = cfg.i18n || {};

	// Toggle collapsible Activated Sites drawer per license card.
	$(document).on('click', '.licenser-portal-toggle-sites', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $card = $btn.closest('.licenser-portal-card');
		var $panel = $card.find('.licenser-portal-sites');
		var isOpen = !$panel.prop('hidden');

		$panel.prop('hidden', isOpen);
		$btn.attr('aria-expanded', isOpen ? 'false' : 'true');
	});

	// One-click Copy License Key button.
	$(document).on('click', '.licenser-portal-copy', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var key = $btn.attr('data-copy');
		if (!key) {
			return;
		}

		var markCopied = function () {
			$btn.addClass('is-copied').text(i18n.copied || 'Copied!');
			window.setTimeout(function () {
				$btn.removeClass('is-copied').text(i18n.copy || 'Copy');
			}, 1800);
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(key).then(markCopied);
		} else {
			var $temp = $('<textarea></textarea>')
				.val(key)
				.css({ position: 'fixed', top: '-9999px' })
				.appendTo('body');
			$temp[0].select();
			try {
				if (document.execCommand('copy')) {
					markCopied();
				}
			} catch (err) {
				// Ignore clipboard fallback errors.
			}
			$temp.remove();
		}
	});

	// Inline two-step confirmation for customer self-service domain deactivation.
	$(document).on('click', '.licenser-portal-deactivate-init', function (e) {
		e.preventDefault();
		var $actions = $(this).closest('.licenser-portal-site-row__actions');
		$(this).prop('hidden', true);
		$actions.find('.licenser-portal-confirm-inline').prop('hidden', false);
	});

	$(document).on('click', '.licenser-portal-deactivate-cancel', function (e) {
		e.preventDefault();
		var $actions = $(this).closest('.licenser-portal-site-row__actions');
		$actions.find('.licenser-portal-confirm-inline').prop('hidden', true);
		$actions.find('.licenser-portal-deactivate-init').prop('hidden', false);
	});

	$(document).on('click', '.licenser-portal-deactivate-confirm', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var domainId = parseInt($btn.attr('data-domain-id'), 10) || 0;
		var $row = $btn.closest('.licenser-portal-site-row');
		var $card = $btn.closest('.licenser-portal-card');
		var $notice = $card.find('.licenser-portal-sites__notice');

		if (!domainId || !cfg.ajaxurl) {
			return;
		}

		$btn.prop('disabled', true).text(i18n.deactivating || 'Deactivating...');

		$.post(cfg.ajaxurl, {
			action: 'licenser_customer_deactivate_domain',
			nonce: cfg.nonce || '',
			domain_id: domainId
		})
			.done(function (res) {
				if (res && res.success) {
					$row.remove();
					var newCount = res.data && typeof res.data.active_count !== 'undefined' ? res.data.active_count : 0;
					$card.find('.licenser-portal-active-count').text(newCount);
					var $toggleBtn = $card.find('.licenser-portal-toggle-sites');
					$toggleBtn.text($toggleBtn.text().replace(/\(\d+\)/, '(' + newCount + ')'));

					$notice
						.removeClass('is-error')
						.text((res.data && res.data.message) || 'Deactivated.')
						.prop('hidden', false);

					if (!$card.find('.licenser-portal-site-row').length) {
						$card.find('.licenser-portal-sites__list').replaceWith(
							$('<p class="licenser-portal-sites__empty"></p>').text(i18n.no_domains || 'No sites currently activated for this license.')
						);
					}
				} else {
					var errMsg = (res && res.data && res.data.message) || i18n.generic_err || 'Could not deactivate site.';
					$notice.addClass('is-error').text(errMsg).prop('hidden', false);
					$btn.prop('disabled', false).text(i18n.deactivate || 'Deactivate');
				}
			})
			.fail(function (xhr) {
				var errMsg =
					(xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ||
					i18n.generic_err ||
					'Could not deactivate site.';
				$notice.addClass('is-error').text(errMsg).prop('hidden', false);
				$btn.prop('disabled', false).text(i18n.deactivate || 'Deactivate');
			});
	});
});
