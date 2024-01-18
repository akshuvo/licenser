(function($) {
    "use strict";
    $(document).ready(function(){

       // $('div[class*="lmfwpptwcext_product_fields"]').hide();
        $(document).on('change', 'input[class*="lmfwpptwcext_checkbox"]', function(e){
            if($(this).is(":checked")) {
                $(this).closest('.lmfwpptwcext_product_data').find('div[class*="lmfwpptwcext_product_fields"]').show();
            } else {
                $(this).closest('.lmfwpptwcext_product_data').find('div[class*="lmfwpptwcext_product_fields"]').hide();
            }
        });

        $(document).on( 'change', 'select[class*="licenser_product_type"]', function(e) {
            var product_type = $(this).val();
            if(product_type == "theme"){
                $(this).closest('.lmfwpptwcext_product_data').find('p[class*="theme_product_list"]').show();
                $(this).closest('.lmfwpptwcext_product_data').find('p[class*="plugin_product_list"]').hide();
            }
            else if(product_type == "plugin"){
                $(this).closest('.lmfwpptwcext_product_data').find('p[class*="plugin_product_list"]').show()
                $(this).closest('.lmfwpptwcext_product_data').find('p[class*="theme_product_list"]').hide()
            }

        });

        // package option show
        $(document).on( 'change', 'select[class*="select_product_list"]', function(e, is_edit){
            let $this = $(this);
            let selected = $this.closest('.lmfwpptwcext_product_data').find('.select_package').attr('data-pack_value'); 

            $(this).closest('.lmfwpptwcext_product_data').find('p[class*="select_package"]').show();
            var id = $(this).val();
            if( id == ''){
                return;
            }

            console.log(selected);
            
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action:"get_product_package_wc",
                    id:id
                },
                cache: false,
                success:function( data ){
                    if( data ){
                        $this.closest('.lmfwpptwcext_product_data').find('select[class*="select_package"]').html(data);
                        
                        // handle edit
                        if ( is_edit ) {
                            $this.closest('.lmfwpptwcext_product_data').find('select[class*="select_package"]').find( 'option[value="'+selected+'"]' ).prop('selected', 1);
                        }
                    }

                },
                error:function( data ){
                    console.log("error");
                }

            });

        });

        // variations

        function lmfwpptwc_fields_change(){
            $('input[class*="lmfwpptwcext_checkbox"]').trigger('change', true);
            $('select[class*="licenser_product_type"]').trigger('change', true);
            $('select[class*="select_product_list"]').trigger('change', true);

            console.log('data loaded');
        }

        lmfwpptwc_fields_change();

        $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', function(){
            lmfwpptwc_fields_change();
        } );

    });
    
    
})(jQuery);
 