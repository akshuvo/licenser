<?php
use Licenser\Models\License;
use Licenser\Models\Product;

// Product instance
$product_model = Product::instance();

// License instance
$license_model = License::instance();

// License ID
$license_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : '';

// Get License
$license = $license_model->get( $license_id );


// Products
$products = $product_model->get_all([
    'status' => 'active',
    'number' => -1,
    'inc_packages' => true,
    'columns' => 'id, name, product_type',
]);


echo "<pre>"; print_r($license); echo "</pre>";



// Submit button label for Add
$submit_button_label = __( 'Add License', 'licenser' );

if ( isset( $_GET['action'] ) && $_GET['action'] == "edit" ) {

    // Submit button label for Edit
    $submit_button_label = __( 'Edit License', 'licenser' );

}

?>
<div class="wrap">

    <div class="lmwppt-wrap">

        <div class="lmwppt-inner-card card-shameless">
            <?php if( isset( $_GET['id'] ) ) : ?>
                <h1><?php _e( 'Edit License', 'licenser' ); ?></h1>
            <?php else : ?>
                <h1><?php _e( 'Add New License', 'licenser' ); ?></h1>
            <?php endif; ?>
        </div>

        <form action="" method="post" id="license-add-form">
            
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Product Information', 'licenser' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="download_link"><?php esc_html_e( 'License Key', 'licenser' ); ?></label>
                        <div class="lmfwppt-file-field">
                            <input type="text" name="license_key" id="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'License Key', 'licenser' ); ?>" value="<?php echo esc_attr( $license->license_key );?>" readonly required />

                            <button class="button" type="button" id="generate_key">
                            <span class="generate-key-label"><?php esc_html_e( 'Generate Key', 'licenser' ); ?></span>
                            <span class="spinner key-spinner"></span>
                            </button>
                            
                        </div>
                    </div>

                   
                    <div class="lmfwppt-form-field">
                        <label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
                        <select name="product_type" id="product_type">
                            <option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
                            <?php foreach( $product_model->get_types() as $key => $value ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $product->product_type, $key ); ?> ><?php echo esc_html( $value ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Select Product -->
                    <div class="lmfwppt-form-field lmfwppt_theme_products">
                        <label for="product_list"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
                        <select name="product_list" class="products_list" id="product_list" >
                            <option value="" class="blank">Select Product</option>
                            <?php foreach ( $products as $product ): ?>   
                                <option value="<?php echo esc_attr( $product->id ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt--' ); ?>" <?php selected( $product_id, $product->id ); ?>><?php echo esc_html( $product->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     
                    <!--  License Package -->
                    <div class="lmfwppt-form-field lmfwppt_license_package" id="lmfwppt_license_package">
                        <label for="lmfwppt_package_list"><?php esc_html_e( 'Select Package', 'licenser' ); ?></label>
                        <select name="package_id" id="lmfwppt_package_list" data-pack_value="<?php echo esc_attr_e( $license->package_id ); ?>" >
                             <option value="" class="blank"><?php esc_html_e( 'Select Package', 'licenser' ); ?></option>
                             
                        </select>
                    </div>

                
                    <div class="lmfwppt-form-field">
                        <div class="d-flex">
                            <label for="end_date" class="mr-15"><?php esc_html_e( 'License End Date', 'licenser' ); ?></label>
                            <label>
                                <input type="checkbox" name="is_lifetime"  <?php checked( $license->is_lifetime, '1' ); ?>>
                                <?php esc_html_e( 'Lifetime', 'licenser' ); ?>
                            </label>
                        </div>
                        <input type="text" name="end_date" id="end_date" class="regular-text product_name_input" placeholder="License End Date" value="<?php echo esc_attr( $license->end_date ); ?>">
                    
                            
                        
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="end_date"><?php esc_html_e( 'License Domain Limit', 'licenser' ); ?></label>
                        <input type="number" name="domain_limit" id="domain_limit" class="regular-text product_name_input" placeholder="Enter Domain Limit" value="<?php echo esc_attr( $license->domain_limit ); ?>">
                        <div><?php esc_html_e( 'Leave empty for lifetime updates.', 'licenser' ); ?></div>
                    </div>
                
                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="source"><?php esc_html_e( 'Source', 'licenser' ); ?></label>
                            <input type="text" name="source" id="source" class="regular-text" value="<?php echo esc_attr( $license->source ); ?>">
                        </div>

                        <div class="lwp-col-half">
                            <label for="source_id"><?php esc_html_e( 'Source ID', 'licenser' ); ?></label>
                            <input type="number" name="source_id" id="source_id" class="regular-text" placeholder="" value="<?php echo esc_attr( $license->source_id ); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="license-information">
                    <h2>Activated Domains</h2>
                    <div id="lmfwppt_domains_fields">
                        <?php //$license_handler::get_domains_html( $get_domains ); ?>
                        <div id="postimagediv" class="postbox lmfwppt_license_field"> <!-- Wrapper Start -->
            <span id="poststuff">
                <h2 class="hndle">
                     
                    <input id="<?php echo esc_attr( $field_name ); ?>-lmfwppt_domain" class="regular-text" type="text" name="<?php echo esc_attr( $field_name ); ?>[url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php echo esc_attr__( 'Enter Domain/URL', 'lmfwppt' ); ?>" required />
                    <label class="lmfwppt_label_space">
                        <input name="<?php echo esc_attr( $field_name ); ?>[deactivate]" type="checkbox" id="<?php echo esc_attr( $field_name ); ?>-lmfwppt_deactivate" <?php checked($deactivate, "on"); ?>><?php esc_html_e( 'Deactivate', 'lmfwppt' ); ?>
                    </label>
                    <span class="delete_field">&times;</span>
                </h2>
            </span>
        </div>
                    </div>
                    <button class="button lmfwppt-domain-activate" type="button">Add Domain</button>
                </div>
            </div>
          
            <div class="lmwppt-inner-card lmfwppt-buttons card-shameless">

                <?php if( isset( $license_id ) ) : ?>
                    <input class="lmfwppt_edit_id" type="hidden" name="id" value="<?php _e( $license_id ); ?>">
                <?php endif; ?>
                
                <div class="submit_btn_area"> 
                    <?php submit_button( $submit_button_label, 'primary', 'add_license' ); ?> 
                    <span class="spinner"></span>
                </div>  
                <div class="lmfwppt-notices"></div>  
            </div>
            
        </form>

    </div>
 
</div>


<script>
    // Submit Product Form
    jQuery(document).on('submit', '#license-add-form', function(e) {
        e.preventDefault();
        let $this = jQuery(this);

        let formData = new FormData(this);

        // Convert FormData to JSON
        let jsonObject = {};
        formData.forEach(function(value, key){
            // Handle fields with square bracket notation
            if (key.includes("[") && key.includes("]")) {
                var keys = key.match(/\w+/g); // Extract keys from the name attribute
                var currentObject = jsonObject;
                for (var i = 0; i < keys.length - 1; i++) {
                    currentObject[keys[i]] = currentObject[keys[i]] || {};
                    currentObject = currentObject[keys[i]];
                }
                currentObject[keys[keys.length - 1]] = value;
            } else {
                // Handle regular fields
                jsonObject[key] = value;
            }
    
        });

        // Get Product type
        let productType = jQuery('#product_type').val();

        jQuery.ajax({
            type: 'post',
            url: Licenser.rest_url + 'licenses',
            data: JSON.stringify(jsonObject), // Convert JSON object to a string
            contentType: 'application/json', // Set content type to JSON
            beforeSend: function(xhr) {
                // Nonce
                xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);
                
                $this.find('.spinner').addClass('is-active');
                $this.find('[type="submit"]').prop('disabled', true);
                jQuery(document).trigger("lmfwppt_notice", ['', 'remove']);
            },
            complete: function(data) {
                $this.find('.spinner').removeClass('is-active');
                $this.find('[type="submit"]').prop('disabled', false);
            },
            success: function(data) {
                // Success Message and Redirection
                if (jQuery('.lmfwppt_edit_id').val()) {
                    jQuery(document).trigger("lmfwppt_notice", ['License updated successfully.', 'success']);
                } else {
                    jQuery(document).trigger("lmfwppt_notice", ['License added successfully. Redirecting...', 'success']);
                    // window.location = '/wp-admin/admin.php?page=licenser-'+productType+'s&action=edit&id='+data+'&message=1';
                }
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });
    });

    // Generate License Key
    jQuery(document).on('click', '#generate_key', function(e) {
        e.preventDefault();
        let $this = jQuery(this);

        jQuery.ajax({
            type: 'post',
            url: Licenser.rest_url + 'licenses/generate-key',
            beforeSend: function(xhr) {
                // Nonce
                xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);
                
                $this.find('.spinner').addClass('is-active');
                $this.find('.generate-key-label').hide();
            },
            complete: function(data) {
                $this.find('.spinner').removeClass('is-active');
                $this.find('.generate-key-label').show();
            },
            success: function(data) {
                jQuery('#license_key').val(data);
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });
    });

    // Product Type Change
    jQuery(document).on('change', '#product_type', function(e, is_edit) {
        let thisVal = jQuery(this).val();
        
        jQuery('.theme-opt, .plugin-opt').hide();
        
        console.log(thisVal);

        if ( !is_edit ) {
            jQuery('.products_list').val('');
            jQuery('#lmfwppt_package_list').val('');
        }

        // Show product type options
        jQuery( '.'+thisVal+'-opt').show();
           
    });

    // Add package
    jQuery(document).on('change', '.products_list', function(e, is_edit){

        e.preventDefault();
        let $this = jQuery(this);
        let packageSelect = jQuery('#lmfwppt_package_list');

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

                    // handle edit
                    if ( is_edit ) {
                        jQuery("#lmfwppt_package_list").find( 'option[value="'+selected+'"]' ).prop('selected', 1);
                    }
                } else {
                    options = '<option value="" class="blank">No Package Found</option>';

                    // Disable package select
                    packageSelect.prop('disabled', true);
                }
                packageSelect.html( options );

            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });

        // if ( !is_edit ) {
        //     jQuery('#lmfwppt_package_list').val('');
        // }

        // jQuery(".lmfwppt_license_package").show();
        // let id = jQuery(this).val();
        // if(id==''){
        //     return;
        // }
        // let selected = jQuery('#lmfwppt_package_list').attr('data-pack_value'); 

        // jQuery.ajax({
        //     type:"POST",
        //     url: ajaxurl,
        //     data:{
        //         action:'get_packages_option',
        //         id:id,
        //         selected:selected
        //     },
        //     cache:false,
        //     success:function(data){
        //          if( data ){
        //             jQuery("#lmfwppt_package_list").html( data );

        //             // handle edit
        //             if ( is_edit ) {
        //                 jQuery("#lmfwppt_package_list").find( 'option[value="'+selected+'"]' ).prop('selected', 1);
        //             }
        //          }
        //     },
        //     error:function(data){
        //         console.log(data);
        //     }
        // });
    });
  

    // Document Ready
    jQuery(document).ready(function() {
        
        // Generate license key if #license_key is empty
        if ( jQuery('#license_key').val() == '' ) {
            jQuery('#generate_key').trigger('click');
        }

    });
</script>