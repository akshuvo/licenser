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


    // let selected = $this.closest('.lmfwpptwcext_product_data').find('.select_package').attr('data-pack_value'); 

    // jQuery(this).closest('.lmfwpptwcext_product_data').find('p[class*="select_package"]').show();
    // var id = jQuery(this).val();
    // if( id == ''){
    //     return;
    // }

    // console.log(selected);
    
    // jQuery.ajax({
    //     type: 'POST',
    //     url: ajaxurl,
    //     data: {
    //         action:"get_product_package_wc",
    //         id:id
    //     },
    //     cache: false,
    //     success:function( data ){
    //         if( data ){
    //             $this.closest('.lmfwpptwcext_product_data').find('select[class*="select_package"]').html(data);
                
    //             // handle edit
    //             if ( is_edit ) {
    //                 $this.closest('.lmfwpptwcext_product_data').find('select[class*="select_package"]').find( 'option[value="'+selected+'"]' ).prop('selected', 1);
    //             }
    //         }

    //     },
    //     error:function( data ){
    //         console.log("error");
    //     }

    // });

});

function licenser_trigger_fields_change(){
    jQuery('select.licenser_load_package').trigger('change', [true]);

    console.log('data loaded');
}

// Document ready
jQuery(document).ready(function(){
    // Variation Load
    jQuery( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
        licenser_trigger_fields_change();
    } );

    // Simple Product
    licenser_trigger_fields_change();

});

(function($) {
    "use strict";
    jQuery(document).ready(function(){

       // jQuery('div[class*="lmfwpptwcext_product_fields"]').hide();
        jQuery(document).on('change', 'input[class*="lmfwpptwcext_checkbox"]', function(e){
            if(jQuery(this).is(":checked")) {
                jQuery(this).closest('.lmfwpptwcext_product_data').find('div[class*="lmfwpptwcext_product_fields"]').show();
            } else {
                jQuery(this).closest('.lmfwpptwcext_product_data').find('div[class*="lmfwpptwcext_product_fields"]').hide();
            }
        });

        jQuery(document).on( 'change', 'select[class*="licenser_product_type"]', function(e) {
            var product_type = jQuery(this).val();
            if(product_type == "theme"){
                jQuery(this).closest('.lmfwpptwcext_product_data').find('p[class*="theme_product_list"]').show();
                jQuery(this).closest('.lmfwpptwcext_product_data').find('p[class*="plugin_product_list"]').hide();
            }
            else if(product_type == "plugin"){
                jQuery(this).closest('.lmfwpptwcext_product_data').find('p[class*="plugin_product_list"]').show()
                jQuery(this).closest('.lmfwpptwcext_product_data').find('p[class*="theme_product_list"]').hide()
            }

        });



        // variations

        // function lmfwpptwc_fields_change(){
        //     jQuery('input[class*="lmfwpptwcext_checkbox"]').trigger('change', true);
        //     jQuery('select[class*="licenser_product_type"]').trigger('change', true);
        //     jQuery('select[class*="select_product_list"]').trigger('change', true);

        //     console.log('data loaded');
        // }

        // lmfwpptwc_fields_change();

        // jQuery( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
        //     lmfwpptwc_fields_change();
        // } );

    });
    
    
})(jQuery);
 