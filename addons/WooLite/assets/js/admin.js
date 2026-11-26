jQuery(function ($) {
	'use strict';

	var cfg = typeof Licenser !== 'undefined' ? Licenser : {};
	var i18n = cfg.i18n || {};

	function filterProductsByType($container, isInit) {
		var type = $container.find('.licenser_product_type').val();
		var $prodSelect = $container.find('.licenser_product_id');
		var currentVal = $prodSelect.val();

		$prodSelect.find('option').each(function () {
			var $opt = $(this);
			var optType = $opt.attr('data-product-type');
			if (!optType) {
				$opt.prop('disabled', false).show();
				return;
			}
			var match = !type || optType === type;
			$opt.prop('disabled', !match).toggle(match);
		});

		if (!isInit && currentVal) {
			var $selected = $prodSelect.find('option:selected');
			if ($selected.prop('disabled')) {
				$prodSelect.val('').trigger('change');
			}
		}
	}

	$(document).on('change', '.licenser_product_type', function (e, isInit) {
		filterProductsByType($(this).closest('.licenser_product_data'), !!isInit);
	});

	$(document).on('change', '.licenser_load_package', function (e, isEdit) {
		e.preventDefault();
		var $this = $(this);
		var $packageSelect = $this.closest('.licenser_product_data').find('.licenser_select_package');

		if (!$this.val()) {
			$packageSelect
				.html('<option value="" class="blank">' + $('<div>').text(i18n.select_package || 'Select Package').html() + '</option>')
				.prop('disabled', true);
			return;
		}

		var selectedPackage = $packageSelect.attr('data-selected-val');

		$.ajax({
			type: 'GET',
			data: {
				product_id: $this.val()
			},
			url: cfg.rest_url + 'products/packages',
			beforeSend: function (xhr) {
				xhr.setRequestHeader('X-WP-Nonce', cfg.nonce);
			},
			success: function (packages) {
				var options = '';
				if (Array.isArray(packages) && packages.length) {
					options = '<option value="" class="blank">' + $('<div>').text(i18n.select_package || 'Select Package').html() + '</option>';
					packages.forEach(function (pkg) {
						var label = $('<div>').text(pkg.label).html();
						options += '<option value="' + pkg.id + '">' + label + '</option>';
					});
					$packageSelect.prop('disabled', false);
				} else {
					options = '<option value="" class="blank">' + $('<div>').text(i18n.no_package_found || 'No Package Found').html() + '</option>';
					$packageSelect.prop('disabled', true);
				}

				$packageSelect.html(options);

				if (isEdit && selectedPackage) {
					$packageSelect.val(selectedPackage);
				}
			}
		});
	});

	function triggerFieldsInit() {
		$('.licenser_wcaddon_checkbox').trigger('change');
		$('.licenser_product_type').trigger('change', [true]);
		$('.licenser_load_package').trigger('change', [true]);
	}

	$('#woocommerce-product-data').on('woocommerce_variations_loaded', function () {
		triggerFieldsInit();
	});

	$(document).on('change', '.licenser_wcaddon_checkbox', function () {
		var $this = $(this);
		var $fields = $this.closest('.licenser_product_data').find('.licenser_wcaddon_product_fields');
		if ($this.is(':checked')) {
			$fields.show();
		} else {
			$fields.hide();
		}
	});

	// Order Meta Box Copy Key Button
	$(document).on('click', '.licenser-wc-copy-btn', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var text = $btn.attr('data-copy');
		if (!text) {
			return;
		}
		var done = function () {
			$btn.text(i18n.copied || 'Copied!');
			window.setTimeout(function () {
				$btn.text(i18n.copy || 'Copy');
			}, 1600);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done);
		}
	});

	triggerFieldsInit();
});
