jQuery(document).on('change', '.licenser_load_package', function(e, isEdit) {
    e.preventDefault();
    var $this = jQuery(this);
    var packageSelect = $this.closest('.licenser_product_data').find('.licenser_select_package');

    if (!$this.val()) {
        packageSelect.html('<option value="" class="blank">Select Package</option>');
        packageSelect.prop('disabled', true);
        return;
    }

    var selectedPackage = packageSelect.attr('data-selected-val');

    jQuery.ajax({
        type: 'get',
        data: {
            product_id: $this.val()
        },
        url: Licenser.rest_url + 'products/packages',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', Licenser.nonce);
        },
        success: function(packages) {
            var options = '';
            if (packages && packages.length) {
                options = '<option value="" class="blank">Select Package</option>';
                packages.forEach(function(pkg) {
                    var label = jQuery('<div>').text(pkg.label).html();
                    options += '<option value="' + pkg.id + '">' + label + '</option>';
                });
                packageSelect.prop('disabled', false);
            } else {
                options = '<option value="" class="blank">No Package Found</option>';
                packageSelect.prop('disabled', true);
            }

            packageSelect.html(options);

            if (isEdit && selectedPackage) {
                packageSelect.val(selectedPackage);
            }
        },
        error: function() {
            jQuery(document).trigger('licenser_notice', ['Something went wrong. Try again.', 'error']);
        }
    });
});

function licenserTriggerFieldsChange() {
    jQuery('.licenser_wcaddon_checkbox').trigger('change');
    jQuery('.licenser_load_package').trigger('change', [true]);
}

jQuery(document).ready(function() {
    jQuery('#woocommerce-product-data').on('woocommerce_variations_loaded', function() {
        licenserTriggerFieldsChange();
    });

    jQuery(document).on('change', '.licenser_wcaddon_checkbox', function() {
        var $this = jQuery(this);
        if ($this.is(':checked')) {
            $this.closest('.licenser_product_data').find('.licenser_wcaddon_product_fields').fadeIn('fast');
        } else {
            $this.closest('.licenser_product_data').find('.licenser_wcaddon_product_fields').hide();
        }
    });

    licenserTriggerFieldsChange();
});
