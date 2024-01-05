function lwpGenerateUniqueId() {
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        // Use crypto API if available
        const randomBytes = new Uint8Array(16);
        crypto.getRandomValues(randomBytes);

        // Set version (4) and variant bits (2 bits long)
        randomBytes[6] = (randomBytes[6] & 0x0f) | 0x40;
        randomBytes[8] = (randomBytes[8] & 0x3f) | 0x80;

        // Convert to hexadecimal representation
        return Array.from(randomBytes)
            .map(byte => byte.toString(16).padStart(2, '0'))
            .join('');
    } else {
        // Fallback to Math.random() if crypto API is not available
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}

jQuery(document).ready(function($){



    // Accordion Toggle
    $(document).on('click', '.lmfwppt-toggle-head', function(e){
    	e.preventDefault();
        $(this).parent().toggleClass('opened').find('.lmfwppt-toggle-wrap').slideToggle('fast');
        return false;
    });

    // Remove Section Field
    $(document).on('click', '.delete_field', function(e){
        e.preventDefault();
        $(this).closest('.postbox').remove();
    } );

    // // Add Section Field 
    // $(document).on('click', '.add-section-information', function(){
    //     var $this = $(this);

    //     var keyLen = jQuery('.lmfwppt_license_field').length;

    //     var data = {
    //         action: 'lmfwppt_single_section_field',
    //         key: keyLen,
    //         thiskey: keyLen,
    //     }

    //     $.ajax({
    //       url: ajaxurl,
    //       type: 'post',
    //       data: data,
    //       beforeSend : function ( xhr ) {
    //         $this.prop('disabled', true);
    //       },
    //       success: function( res ) {
    //         $this.prop('disabled', false);

    //         // Data push
    //         $('#section-information-fields').append(res);

    //         // Open last item
    //         jQuery('#section-information-fields .lmfwppt-toggle-head').last().click();
    //       },
    //       error: function( result ) {
    //         $this.prop('disabled', false);
    //         console.error( result );
    //       }
    //     });
    // });

    // // Add Domain Field
    // $(document).on('click', '.lmfwppt-domain-activate', function(){
        
    //     var $this = $(this);

    //     var keyLen = jQuery('.lmfwppt_license_field').length;

    //     var data = {
    //         action: 'lmfwppt_domain_active_field_action',
    //         key: keyLen,
    //         thiskey: keyLen,
    //     }

    //     $.ajax({
    //       url: ajaxurl,
    //       type: 'post',
    //       data: data,
    //       beforeSend : function ( xhr ) {
    //         $this.prop('disabled', true);
    //       },
    //       success: function( res ) {
    //         $this.prop('disabled', false);

    //         // Data push
    //         $('#lmfwppt_domains_fields').append(res);
    //       },
    //       error: function( result ) {
    //         $this.prop('disabled', false);
    //         console.error( result );
    //       }
    //     });
    // });

    // Add File
    var file_frame, pushSelector, getFrameTitle;
    $(document).on('click', '.trigger_media_frame', function(){
        var $this = $(this);

        // Set Selector
        pushSelector = $this.attr('data-push_selector');

        // Track State
        wp_media_state('open');

        if ( undefined !== file_frame ) {
            file_frame.open();
            return;
        }

        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select The Appropriate File',
            //frame:    'post',
            //state:    'insert',
            multiple: false,
            library: {
                //type: [ 'zip' ]
            },
            button: {text: 'Insert'}
        });

        file_frame.on( 'select', function(e) {

            var attachment = file_frame.state().get('selection').first().toJSON();

            $(pushSelector).val( attachment.url );

        });

        file_frame.on( 'close', function(e) {
            // Track State
            wp_media_state('close');

        });

        // Now display the actual file_frame
        file_frame.open();

    });

    // Track Media State
    function wp_media_state( state = 'close' ){
        jQuery(document).ready(function($) {
            var data = {
                'action': 'lmfwppt_media_frame_state',
                'state': state
            };

            jQuery.post(ajaxurl, data, function(response) {
                console.log('Media State is now: ' + response);
            });
        });
    }


    // // Add License
    // $(document).on('submit', '#license-add-form', function(e) {
    //     e.preventDefault();
    //     let $this = $(this);

    //     let formData = new FormData(this);
    //     formData.append('action', 'license_add_form');

    //     $.ajax({
    //         type: 'post',
    //         url: ajaxurl,
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    //         beforeSend: function(data) {
    //             $this.find('.spinner').addClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', true);
    //             $(document).trigger("lmfwppt_notice", ['', 'remove']);
    //         },
    //         complete: function(data) {
    //             $this.find('.spinner').removeClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', false);
    //         },
    //         success: function(data) {
    //             // Success Message and Redirection
    //             if ( jQuery('.lmfwppt_edit_id').val() ) {
    //                 $(document).trigger("lmfwppt_notice", ['License updated successfully.', 'success']);
    //             } else {
    //                 $(document).trigger("lmfwppt_notice", ['License added successfully. Redirecting...', 'success']);
    //                 //window.location = '/wp-admin/admin.php?page=licenser-licenses&action=edit&id='+data+'&message=1';
    //             }

    //         },
    //         error: function(data) {
    //             $(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);

    //         },

    //     });

    // });

    // // Add package
    // $(document).on('change', '.products_list', function(e, is_edit){

    //     if ( !is_edit ) {
    //         jQuery('#lmfwppt_package_list').val('');
    //     }

    //     $(".lmfwppt_license_package").show();
    //     let id = $(this).val();
    //     if(id==''){
    //         return;
    //     }
    //     let selected = $('#lmfwppt_package_list').attr('data-pack_value'); 

    //     $.ajax({
    //         type:"POST",
    //         url: ajaxurl,
    //         data:{
    //             action:'get_packages_option',
    //             id:id,
    //             selected:selected
    //         },
    //         cache:false,
    //         success:function(data){
    //              if( data ){
    //                 $("#lmfwppt_package_list").html( data );

    //                 // handle edit
    //                 if ( is_edit ) {
    //                     $("#lmfwppt_package_list").find( 'option[value="'+selected+'"]' ).prop('selected', 1);
    //                 }
    //              }
    //         },
    //         error:function(data){
    //             console.log(data);
    //         }
    //     });
    // });
  
    // // Generate License Key
    // $(document).on('click', '#generate_key', function(e){
    //     e.preventDefault();
    //     let $this = $(this);

    //     $.ajax({
    //         type:'POST',
    //         url:ajaxurl,
    //         data:{
    //             action:'license_key',
    //         },
    //         cache:false,
    //         beforeSend: function(data) {
    //             $this.find('.spinner').addClass('is-active').show();  
    //             $('#generate_key').prop('disabled', true).find('.generate-key-label').hide();  
    //         },
    //         complete: function(data) {
    //             $this.find('.spinner').removeClass('is-active').hide();
    //             $('#generate_key').prop('disabled', false).find('.generate-key-label').show();
    //         },
    //         success:function(data){
    //             if(data){
    //                 $("#license_key").val(data);
    //             }
    //         }
    //     })
    // });

    // //space remove dash add
    // $(document).on('keyup', '#slug', function(e) {
    //     e.preventDefault();
    //     let value = $(this).val().replace(" ", "-");
    //     $(this).val(value);
        
    // });

    // $(document).on('change', '#product_type', function(e, is_edit) {
    //     let thisVal = $(this).val();
        
    //     $(".theme-opt").hide();
    //     $(".plugin-opt").hide();

    //     if ( !is_edit ) {
    //         jQuery('.products_list').val('');
    //         jQuery('#lmfwppt_package_list').val('');
    //     }

    //     if(thisVal == "theme"){
    //         $(".theme-opt").show();
    //         $(".plugin-opt").hide();
             
    //     } else if(thisVal == "plugin"){
    //         $(".plugin-opt").show();
    //         $(".theme-opt").hide();
    //     }
    // });

    // jQuery('#product_type').trigger('change',['true']);
    // jQuery('.products_list').trigger('change',['true']);
    

    // // Add Setting
    // $(document).on('submit', '#setting-add-form', function(e) {
    //     e.preventDefault();
    //     let $this = $(this);

    //     let formData = new FormData(this);
    //     formData.append('action', 'setting_add_form');

    //     $.ajax({
    //         type: 'post',
    //         url: ajaxurl,
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    //         beforeSend: function(data) {
    //             $this.find('.spinner').addClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', true);
    //             $(document).trigger("lmfwppt_notice", ['', 'remove']);
    //         },
    //         complete: function(data) {
    //             $this.find('.spinner').removeClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', false);
    //         },
    //         success: function(data) {
    //             $(document).trigger("lmfwppt_notice", ['Setting updated', 'success']);
    //         },
    //         error: function(data) {
    //             $(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);

    //         },

    //     });

    // });

    // // Add SDK Generator
    // $(document).on('submit', '#sdk-generator-add-form', function(e) {
    //     e.preventDefault();
    //     let product_type = $('.product_type').val();
    //     let select_product = $('.select_product').val();
    //     if( (product_type == '') || (select_product == '') ){
    //         return;
    //     }
    //     var $this = $(this);
    //     var formData = new FormData(this);
    //     formData.append('action', 'sdk_generator_add_form');

    //     $.ajax({
    //         type: 'post',
    //         url: ajaxurl,
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    //         beforeSend: function(data) {
    //             $this.find('.spinner').addClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', true);
    //             $('.sdk_generator_response').addClass('hidden');
    //         },
    //         complete: function(data) {
    //             $this.find('.spinner').removeClass('is-active');
    //             $this.find('[type="submit"]').prop('disabled', false);
    //         },
    //         success: function(data) {
    //             console.log(data);

    //             $('.sdk_generator_response').html(data);
    //             $('.sdk_generator_response').removeClass('hidden');

    //         },
    //         error: function(data) {
    //             console.log(data);

    //         },

    //     });

    // });

    // // Parent slug input field hide show
    // $(document).on('change', '#lmfwppt_menu_select', function(e) {
    //     let menu = $(this).val(); 

    //     let formSection = $(this).closest('.lmfwppt-form-section');
    //     jQuery('.show-on-default-type', formSection).show();
    //     jQuery('.show-on-section-type', formSection).hide();

    //     if( menu == "sub_menu" ){
    //         jQuery('.parent-slug-menu', formSection).show();
    //     } else if( menu == "section" ){
    //         jQuery('.hide-on-section-type', formSection).hide();
    //         jQuery('.show-on-section-type', formSection).hide();
    //     } else {
    //         jQuery('.parent-slug-menu', formSection).hide();
    //     }
    // });

    // // Value set
    // $(document).on('change', '.products_list', function(e) {

    //     let product_name = $(this).find("option:selected").text();

    //     if( !$(this).val() ) {
    //         $(".lmfwppt_page_title").val('');
    //         $(".lmfwppt_menu_title").val('');
    //         return;
    //     }
    //     if( product_name ) {
    //         $(".lmfwppt_page_title").val(product_name+' License Activation');
    //         $(".lmfwppt_menu_title").val(product_name+' License');
    //     }

    // });

    // Notice Messages show script
    $(document).on("lmfwppt_notice", function(event, notice, type) {
        
        if(type == "remove"){
            $('.lmfwppt-notices').html('');
                return;
        }
        let notice_html = '<div class="notice notice-alt is-dismissible notice-'+type+'"><p>'+notice+'</p></div>';
        $('.lmfwppt-notices').html(notice_html);
        jQuery(document).trigger('wp-updates-notice-added');
    
    });

    // // Generate Package ID
    // $(document).on('click', '.generate-package-id', function(e){
    //     e.preventDefault();
    //     let $this = $(this);

    //     let product_name = jQuery('input#name').val();
        
    //     // Replace product name texts
    //     product_name = product_name.replace(/[^a-z0-9_]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    //     product_name = product_name.split("-");

    //     let short_name = '';
    //     // Get first char from words array
    //     for( let i in product_name ){
    //         short_name += product_name[i].substring(0,1);
    //     }

    //     // Package name
    //     let package_name = $this.closest('.lmfwppt_license_field').find('.license-package-name').val();

    //     // Combine the names
    //     let package_id = short_name+'-'+package_name;

    //     // Replace texts
    //     package_id = package_id.replace(/[^a-z0-9_]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();

    //     // Set value
    //     $this.closest('.lmfwppt_license_field').find('.license-package-id').val(package_id);
        
    // });

    // // Slide Toggle Prevent
    // $(document).on('click', '.prevent-toggle-head', function(e){
    //     e.preventDefault();
    //     $(this).parent().toggleClass('opened').find('.lmfwppt-toggle-wrap').slideToggle('fast');
    //     $(this).parent().toggleClass('opened').find('.lmfwppt-toggle-wrap').slideToggle('fast');
    //     return false;
    // });

});