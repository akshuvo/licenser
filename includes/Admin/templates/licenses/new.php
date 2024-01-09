<?php
// Default args
$defaults_args = array (
    'id' => '',
    'status' => '',
    'license_key' => '',
    'package_id' => '',
    'order_id' => '',
    'end_date' => '',
    'is_lifetime' => '',
    'domain_limit' => '',
);

// License Class
$license_handler = new Licenser\Licenses();

// Product Class
$product_handler = new Licenser\Products();

$get_product = array();
$get_packages = null;

// License ID
$license_id = isset( $_GET['id'] ) ? intval( sanitize_text_field( $_GET['id'] ) ) : 0;

// Submit button label for Add
$submit_button_label = __( 'Add License', 'lmfwppt' );

if ( isset( $_GET['action'] ) && $_GET['action'] == "edit" ) {

    // Get Product date 
    $get_product = $license_handler->get_license( $license_id );

    // Get packages data
    $get_packages = $product_handler->get_packages( $license_id );

    // Submit button label for Edit
    $submit_button_label = __( 'Edit License', 'lmfwppt' );

}

// Parse incoming $args into an array and merge it with $defaults
$get_product = wp_parse_args( $get_product, $defaults_args );
// Let's extract the array to variable
extract( $get_product );

// If lifetime the remove end date
if ( $is_lifetime || empty( $end_date ) || $end_date == "0000-00-00 00:00:00" ) {
    $end_date = '';
} else {
    $end_date = date('Y-m-d', strtotime($end_date));
}

//license edit
$product_details = $product_handler->get_product_details_by_package_id($package_id);
$product_type = isset( $product_details['product_type'] ) ? $product_details['product_type'] : '' ;
$product_id = isset( $product_details['product_id'] ) ? $product_details['product_id'] : '';

// Get Domains 
$get_domains = $license_handler->get_domains( $license_id );

?>
<div class="wrap">

    <div class="lmwppt-wrap">

        <div class="lmwppt-inner-card card-shameless">
            <?php if( isset( $_GET['id'] ) ) : ?>
                <h1><?php _e( 'Edit License', 'lmfwppt' ); ?></h1>
            <?php else : ?>
                <h1><?php _e( 'Add New License', 'lmfwppt' ); ?></h1>
            <?php endif; ?>
        </div>

        <form action="" method="post" id="license-add-form">
            
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Product Information', 'lmfwppt' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="download_link"><?php esc_html_e( 'License Key', 'lmfwppt' ); ?></label>
                        <div class="lmfwppt-file-field">
                            <input type="text" name="license_key" id="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'License Key', 'lmfwppt' ); ?>" value="<?php echo esc_attr( $license_key );?>" readonly required />

                            <button class="button" type="button" id="generate_key">
                            <span class="generate-key-label"><?php esc_html_e( 'Generate Key', 'lmfwppt' ); ?></span>
                            <span class="spinner key-spinner"></span>
                            </button>
                            
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="order_id"><?php esc_html_e( 'Order ID', 'lmfwppt' ); ?></label>
                         
                        <input type="number" name="order_id" id="order_id" class="regular-text" placeholder="Order ID" value="<?php echo esc_attr( $order_id ); ?>">
                    </div>
                    <div class="lmfwppt-form-field">
                        <label for="product_type"><?php esc_html_e( 'Product Type', 'lmfwppt' ); ?></label>
                        <select name="product_type" id="product_type" >
                            <option value=""><?php esc_html_e( 'Select Product Type', 'lmfwppt' ); ?></option>
                            <option value="theme" <?php selected( $product_type, 'theme' ); ?> ><?php esc_html_e( 'Theme', 'lmfwppt' ); ?></option>
                            <option value="plugin" <?php selected( $product_type, 'plugin' ); ?> ><?php esc_html_e( 'Plugin', 'lmfwppt' ); ?></option>
                        </select>
                    </div>
                    
                    <!-- Select Product -->
                    <div class="lmfwppt-form-field lmfwppt_theme_products">
                        <label for="product_theme_list"><?php esc_html_e( 'Select Product', 'lmfwppt' ); ?></label>
                        <select name="product_list" class="products_list" id="product_theme_list" >
                            <option value="" class="blank">Select Product</option>
                            <?php
                                $items = lmfwppt_get_product_list("theme");
                                foreach ($items as $products_list):?>   
                                <option value="<?php echo $products_list->id; ?>" class="theme-opt" <?php selected( $product_id, $products_list->id ); ?>><?php echo $products_list->name; ?></option>
                               <?php endforeach; ?>
                           <?php
                                $items = lmfwppt_get_product_list("plugin");
                                foreach ($items as $products_list):?>
                                <option value="<?php echo $products_list->id; ?>" class="plugin-opt" <?php selected( $product_id, $products_list->id ); ?>><?php echo $products_list->name; ?></option>
                                <?php endforeach; ?> 
                        </select>
                    </div>
                     
                    <!--  License Package -->
                    <div class="lmfwppt-form-field lmfwppt_license_package" id="lmfwppt_license_package">
                        <label for="lmfwppt_package_list"><?php esc_html_e( 'Select Package', 'lmfwppt' ); ?></label>
                        <select name="package_id" id="lmfwppt_package_list" data-pack_value="<?php esc_attr_e( $package_id, 'lmfwppt' ); ?>" >
                             <option value="" class="blank"><?php esc_html_e( 'Select Package', 'lmfwppt' ); ?></option>
                             
                        </select>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="end_date"><?php esc_html_e( 'License End Date', 'lmfwppt' ); ?></label>
                        <input type="text" name="end_date" id="end_date" class="regular-text product_name_input" placeholder="License End Date" value="<?php echo esc_attr( $end_date ); ?>">
                        <div><?php esc_html_e( 'Leave empty for lifetime updates.', 'lmfwppt' ); ?></div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="end_date"><?php esc_html_e( 'License Domain Limit', 'lmfwppt' ); ?></label>
                        <input type="number" name="domain_limit" id="domain_limit" class="regular-text product_name_input" placeholder="Enter Domain Limit" value="<?php echo esc_attr( $domain_limit ); ?>">
                        <div><?php esc_html_e( 'Leave empty for lifetime updates.', 'lmfwppt' ); ?></div>
                    </div>
                </div>
            </div>
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="license-information">
                    <h2>Activated Domains</h2>
                    <div id="lmfwppt_domains_fields">
                        <?php $license_handler::get_domains_html( $get_domains ); ?>
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
</script>