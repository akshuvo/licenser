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
$license = !empty( $license_id ) ? $license_model->get( $license_id ) : (object) $license_model->default_fields;

// Products
$products = $product_model->get_all([
    'status' => 'active',
    'number' => -1,
    'inc_packages' => false,
    'columns' => 'id, name, product_type',
]);

// echo "<pre>"; print_r($products); echo "</pre>";

// Get Domains 
$get_domains = $license_model->get_domains([
    'license_id' => $license_id,
]);

// Submit button label for Add
$submit_button_label = __( 'Add License', 'licenser' );

if ( isset( $_GET['action'] ) && $_GET['action'] == "edit" ) {

    // Submit button label for Edit
    $submit_button_label = __( 'Edit License', 'licenser' );

}

?>

<div class="wrap">
    <div class="licenser-root">
        <!-- Header  -->
        <div class="licenser-header">
            <div class="licenser-header__title">
                <?php if( isset( $_GET['id'] ) ) : ?>
                    <h2><?php esc_html_e( 'Edit License', 'licenser' ); ?></h2>
                <?php else : ?>
                    <h2><?php esc_html_e( 'Add New License', 'licenser' ); ?></h2>
                <?php endif; ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New License', 'licenser' ); ?></a>
            </div>
        </div>
        <!-- Header  -->
        <!-- Content  -->
        <div class="licenser-content">

            <div class="d-flex">
                <div class="licenser-sidenav">
                    <ul>
                        <li><a href="#general"><?php esc_html_e('General', 'licenser'); ?></a></li>
                        <li><a href="#general"><?php esc_html_e('General', 'licenser'); ?></a></li>
                        <li><a href="#general"><?php esc_html_e('General', 'licenser'); ?></a></li>
                        <li><a href="#general"><?php esc_html_e('General', 'licenser'); ?></a></li>
                    </ul>
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
                                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Select Product -->
                            <div class="lmfwppt-form-field lmfwppt_theme_products">
                                <label for="product_list"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
                                <select name="product_id" class="products_list" id="product_list" >
                                    <option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
                                    <?php foreach ( $products as $product ): ?>   
                                        <option data-product_type="<?php echo esc_attr( $product->product_type ); ?>" value="<?php echo esc_attr( $product->id ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt' ); ?>" <?php selected( $license->product_id, $product->id ); ?>><?php echo esc_html( $product->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!--  License Package -->
                            <div class="lmfwppt-form-field lmfwppt_license_package" id="lmfwppt_license_package">
                                <label for="lmfwppt_package_list"><?php esc_html_e( 'Select Package', 'licenser' ); ?></label>
                                <select name="package_id" id="lmfwppt_package_list" data-pack_value="<?php echo esc_attr( $license->package_id ); ?>" >
                                    <option value="<?php echo esc_attr( $license->package_id ); ?>" class="blank"><?php esc_html_e( 'Select Package', 'licenser' ); ?></option>
                                    
                                </select>
                            </div>

                        
                            <div class="lmfwppt-form-field">
                                <div class="d-flex">
                                    <label for="end_date" class="me-1"><?php esc_html_e( 'License End Date', 'licenser' ); ?></label>
                                    <label>
                                        <input type="checkbox" name="is_lifetime"  <?php checked( $license->is_lifetime, '1' ); ?>>
                                        <?php esc_html_e( 'Lifetime', 'licenser' ); ?>
                                    </label>
                                </div>
                                <input type="text" name="end_date" id="end_date" class="regular-text product_name_input" placeholder="License End Date" value="<?php echo esc_attr( $license->end_date ); ?>">
                            </div>

                            <div class="lmfwppt-form-field">
                                <label for="domain_limit"><?php esc_html_e( 'License Domain Limit', 'licenser' ); ?></label>
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

                                <?php if( !empty( $get_domains ) ) : ?>
                                    <?php foreach( $get_domains as $domain ):
                                        $domain_id = isset( $domain->id ) ? sanitize_text_field( $domain->id ) : '';
                                        $url = isset( $domain->domain ) ? sanitize_text_field( $domain->domain ) : '';
                                        $status = isset( $domain->status ) && $domain->status == 1 ? __('Active', 'licenser') : __('Inactive', 'licenser');
                                        $status_tag_class = isset( $domain->status ) && $domain->status == 1 ? 'lwp-tag-success' : '';
                                        // $dated = isset( $domain->dated ) ? licenser_date('Y-m-d H:i:s', $domain->dated) : '';
                                        ?>
                                        <div class="postbox">
                                            <h4>
                                                <span class="lwp-tag ms-1 me-1 <?php echo esc_attr( $status_tag_class ); ?>"><?php echo esc_html( $status ); ?></span>
                                                <?php echo esc_html( $url . ' - (id:'.$domain_id.')' ); ?> 
                                                <div class="lwp-postbox-actions">
                                                    <a class="lwp-action-item lwp-tooltip" target="_blank" href="<?php echo esc_url( $url ); ?>" data-title="<?php esc_html_e( 'Visit Domain', 'licenser' ); ?>">
                                                        <span class="dashicons dashicons-external"></span>
                                                    </a>
                                                    <a href="javascript:void(0);" class="lwp-action-item lwp-tooltip lwp-delete-domain" data-title="<?php esc_html_e( 'Delete Domain', 'licenser' ); ?>" data-id="<?php echo esc_attr( $domain_id ); ?>">
                                                        <span class="dashicons dashicons-trash"></span>
                                                    </a>
                                                </div>

                                            </h4>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="licenser-not-found"><?php esc_html_e( "No Domains", "licenser" ); ?></div>
                                <?php endif; ?> 
                            </div>
                        </div>
                    </div>
                
                    <div class="lmwppt-inner-card lmfwppt-buttons card-shameless">

                        <?php if( isset( $license_id ) ) : ?>
                            <input class="lmfwppt_edit_id" type="hidden" name="id" value="<?php echo esc_attr( $license_id ); ?>">
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
        <!-- Content  -->
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
            success: function(res) {
                // Success Message and Redirection
                if (jQuery('.lmfwppt_edit_id').val()) {
                    jQuery(document).trigger("lmfwppt_notice", ['License updated successfully.', 'success']);
                } else {
                    jQuery(document).trigger("lmfwppt_notice", ['License added successfully. Redirecting...', 'success']);
                    window.location = '/wp-admin/admin.php?page=licenser-licenses&action=edit&id='+res.id+'&message=1';
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

    // Delete Domain
    jQuery(document).on('click', '.lwp-delete-domain', function(e) {
        e.preventDefault();
        let $this = jQuery(this);
        let domainId = $this.data('id');
       
        // Confirm
        if ( !confirm('Are you sure you want to delete this domain?') ) {
            return;
        }

        jQuery.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'licenses/domains/' + domainId,
            beforeSend: function(xhr) {
                // Nonce
                xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);
                $this.closest('.postbox').remove();
            },
            complete: function(data) {
                
            },
            success: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Domain deleted successfully.', 'success']);
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });
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

<script type="text/javascript">
    jQuery(document).ready(function($){
  
    });
</script>