// package option show
jQuery(document).on( 'change', '.licenser_load_package', function(e, is_edit){

    console.log( e, is_edit );

    e.preventDefault();
    let $this = jQuery(this);
    // Package select element
    let packageSelect = $this.closest('.licenser_product_data').find('.licenser_select_package');


    // If no product is selected
    if ( ! $this.val() ) {
        packageSelect.html('<option value="" class="blank">Select Package</option>');
        packageSelect.prop('disabled', true);
        return;
    }

    // If package is already selected
    let selectedPackage = packageSelect.attr('data-selected-val');

    jQuery.ajax({
        type: 'get',
        data: {
            product_id: $this.val()
        },
        url: Licenser.rest_url + 'products/packages',
        beforeSend: function(xhr) {
            // Nonce
            xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);
        },
        complete: function(data) {
            
        },
        success: function(packages) {
            let options = '';
            if ( packages.length ) {

                options = '<option value="" class="blank">Select Package</option>';
                
                packages.forEach( function( package ) {
                    options += '<option value="'+package.id+'">'+package.label+'</option>';
                });
                
                // Disable package select
                packageSelect.prop('disabled', false);

            } else {
                options = '<option value="" class="blank">No Package Found</option>';

                // Disable package select
                packageSelect.prop('disabled', true);
            }
            packageSelect.html( options );

            // handle edit
            if ( is_edit ) {
                packageSelect.find( 'option[value="'+selectedPackage+'"]' ).prop('selected', 1);
            }

        },
        error: function(data) {
            jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
        },
    });

});

function licenser_trigger_fields_change(){
    // Hide Panel
    jQuery('.licenser_wcaddon_checkbox').trigger('change');
    
    // Trigger Product Change
    jQuery('.licenser_load_package').trigger('change', [true]);
}

// Document ready
jQuery(document).ready(function(){
    // Variation Load
    jQuery( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
        licenser_trigger_fields_change();
    } );

    jQuery(document).on('change', '.licenser_wcaddon_checkbox', function( e ) {
        let $this = jQuery(this);
        if( $this.is(":checked") ) {
            $this.closest('.licenser_product_data').find('.licenser_wcaddon_product_fields').fadeIn('fast');
        } else{
            $this.closest('.licenser_product_data').find('.licenser_wcaddon_product_fields').hide();
        }
    });

    // Simple Product
    licenser_trigger_fields_change();

});