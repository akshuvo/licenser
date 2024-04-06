<?php
use Licenser\Models\Product;

// Product instance
$product_model = Product::instance();

// Product ID
$product_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : '';

// Get Product
$product = !empty( $product_id ) ? $product_model->get( $product_id ) : (object) $product_model->default_fields;

// Product Packages
$product_packages = isset( $product->packages ) ? $product->packages : [];

// echo '<pre>'; print_r($product); echo '</pre>';


$submit_button_label = __( 'Add Product', 'licenser' );

if ( isset( $_GET['action'] ) && $_GET['action'] == "edit" && isset( $_GET['id'] ) ) {
    $submit_button_label = __( 'Edit Product', 'licenser' );
}


?>
<div class="wrap">
    <div class="licenser-wrap">

        <div class="lmwppt-inner-card card-shameless">
            <?php if( isset( $_GET['id'] ) ) : ?>
                <h1><?php esc_html_e( 'Edit Product', 'licenser' ); ?></h1>
            <?php else : ?>
                <h1><?php esc_html_e( 'Add New Product', 'licenser' ); ?></h1>
            <?php endif; ?>
        </div>

        <form action="" method="post" id="product-form">
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Product Information', 'licenser' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="name"><?php esc_html_e( 'Product Name', 'licenser' ); ?></label>
                        <input type="text" name="name" id="name" class="regular-text product_name_input" placeholder="Your Theme or Plugin Name" value="<?php echo esc_attr( $product->name ); ?>" required>
                    </div>
                
                    <div class="lmfwppt-form-field">
                        <label for="id"><?php esc_html_e( 'Product ID', 'licenser' ); ?></label>
                        <input type="text" id="id" class="regular-text "  value="<?php echo esc_attr( $product->uuid ); ?>" readonly>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="slug"><?php esc_html_e( 'Product Slug', 'licenser' ); ?></label>
                        <input type="text" name="slug" id="slug" class="regular-text product_slug_input" placeholder="your-theme-or-plugin-name" value="<?php echo esc_attr( $product->slug ); ?>" required>
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
                            <select name="product_type" id="product_type">
                                <option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
                                <?php foreach( $product_model->get_types() as $key => $value ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $product->product_type, $key ); ?> ><?php echo esc_html( $value ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lwp-col-half">
                            <label for="requires_php"><?php esc_html_e( 'Requires PHP Version', 'licenser' ); ?></label>
                            <input type="text" name="requires_php" id="requires_php" class="regular-text" placeholder="<?php esc_attr_e( '7.4', 'licenser' ); ?>" value="<?php echo esc_attr( $product->requires_php ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="requires"><?php esc_html_e( 'Requires WordPress Version', 'licenser' ); ?></label>
                            <input type="text" name="requires" id="requires" class="regular-text" placeholder="<?php esc_attr_e( '4.7', 'licenser' ); ?>" value="<?php echo esc_attr( $product->requires ); ?>">
                        </div>
                        <div class="lwp-col-half">
                            <label for="product_tested"><?php esc_html_e( 'Tested up to', 'licenser' ); ?></label>
                            <input type="text" name="tested" id="product_tested" class="regular-text" placeholder="<?php esc_attr_e( '5.7', 'licenser' ); ?>" value="<?php echo esc_attr( $product->tested ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="author"><?php esc_html_e( 'Author Name', 'licenser' ); ?></label>
                        <input type="text" name="author_name" id="author" class="regular-text product_name_input" placeholder="Author Name" value="<?php echo esc_attr( $product->author_name ); ?>">
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="homepage_url"><?php esc_html_e( 'Homepage URL', 'licenser' ); ?></label>
                            <input type="url" name="homepage_url" id="homepage_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $product->homepage_url ); ?>">
                        </div>

                        <div class="lwp-col-half">
                            <label for="demo_url"><?php esc_html_e( 'Demo URL', 'licenser' ); ?></label>
                            <input type="url" name="demo_url" id="demo_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $product->demo_url ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="description"><?php esc_html_e( 'Description', 'licenser' ); ?></label>
                        <textarea name="description" id="description" class="regular-text" placeholder="<?php esc_attr_e( 'Description', 'licenser' ); ?>"><?php echo esc_attr( $product->description ); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Release -->
            <div class="lmwppt-inner-card release-information">
                <div class="d-flex justify-content-between mb-15">
                    <h2 class="mb-0"><?php esc_html_e( 'Release Information', 'licenser' ); ?></h2>
                    <?php if( isset( $product->stable_release->download_link ) && !empty( $product->stable_release->download_link ) ) : ?>
                        <button class="button twp-btn twp-btn-lg add-release-btn hide-on-add-release" type="button"><?php esc_html_e( 'Add New Release', 'licenser' ); ?></button>
                    <?php endif; ?>
                </div>
                <div class="add-release-form"></div>
                <?php if( isset( $product->stable_release->download_link ) && !empty( $product->stable_release->download_link ) ) : ?>
                    <div class="lmfwppt-form-field">
                        <div class="postbox lwp-postbox"> 
                            <a class="header lmfwppt-toggle-head" data-toggle="collapse">
                                <span id="poststuff">
                                    <h2 class="hndle">
                                        <span class="release-head">
                                            
                                            <span class="lwp-tag lwp-tag-success me-1"><?php esc_html_e( 'Stable', 'licenser' ); ?></span>
                                            
                                            <span class="release-version"><?php esc_html_e( 'Version:', 'licenser' ); ?> <span><?php echo esc_html( $product->stable_release->version ); ?></span></span>
                                            
                                            <span class="release-date"><?php esc_html_e( 'Released on:', 'licenser' ); ?> <span><?php echo esc_html( licenser_date( 'M d, Y', strtotime( $product->stable_release->release_date ) ) ); ?></span></span>

                                        </span>
                                        <span class="dashicons indicator_field last-icon"></span>
                                    </h2>
                                </span>
                            </a>
                            <div class="collapse lmfwppt-toggle-wrap">
                                <div class="inside">
                                    <div class="mb-15">
                                        <strong><?php esc_html_e( 'File Name:', 'licenser' ); ?></strong> <span><?php echo esc_html( $product->stable_release->file_name ); ?></span>
                                        <span class="twp-spacer"></span>
                                        <strong><?php esc_html_e( 'Download Link:', 'licenser' ); ?></strong> <span><a target="_blank" href="<?php echo esc_url( $product->stable_release->download_link ); ?>" target="_blank"><?php echo esc_html( $product->stable_release->download_link ); ?> <span class="dashicons dashicons-external"></span></a></span>
                                    </div>
                                    <div class="mb-15">
                                        <label><?php esc_html_e( 'Changelog:', 'licenser' ); ?></label>
                                        <span class="pre"><?php echo esc_html( $product->stable_release->changelog ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="twp-not-found hide-on-add-release">
                        <span class="dashicons dashicons-info-outline"></span>
                        <h2><?php esc_html_e( 'No Release Found', 'licenser' ); ?></h2>
                        <p><?php esc_html_e( 'No release found for this product. Add a release to show here.', 'licenser' ); ?></p>
                        <button class="button twp-btn twp-btn-lg add-release-btn" type="button"><?php esc_html_e( 'Create Your First Release', 'licenser' ); ?></button>
                    </div>


                <?php endif; ?>

               
            </div>
            <!-- /Release -->

            
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="license-information">
                    <h2><?php esc_html_e( 'License Packages', 'licenser' ); ?></h2>
                    <div id="license-packages-fields">
                        
                    </div>
                    <button class="button add-license-package" type="button"><?php esc_html_e( 'Add License Package', 'licenser' ); ?></button>
                </div>
            </div>
            <!-- banner -->
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Banners and Icon', 'licenser' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="icon_url"><?php esc_html_e( 'Icon', 'licenser' ); ?></label>
                        <div class="lmfwppt-file-field">
                            <input type="url" name="icon_url" id="icon_url" class="regular-text" placeholder="<?php esc_attr_e( 'icon-128x128.png', 'licenser' ); ?>" value="<?php echo esc_attr( $product->icon_url ); ?>">
                            <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#icon_url" type="button" id="icon_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                        </div>
                    </div> 

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="banner_low"><?php esc_html_e( 'Banner Low', 'licenser' ); ?></label>
                            <div class="lmfwppt-file-field">
                                <input type="url" name="banners[low]" id="banner_low" class="regular-text" placeholder="<?php esc_attr_e( 'banner-772x250.png', 'licenser' ); ?>" value="<?php echo esc_attr( $product->banners['low'] ); ?>" >
                                <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#banner_low" type="button" id="banners_low_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                            </div>
                        </div> 
                        <div class="lwp-col-half">
                            <label for="banner_high"><?php esc_html_e( 'Banner High Resolution', 'licenser' ); ?></label>
                            <div class="lmfwppt-file-field">
                                <input type="url" name="banners[high]" id="banner_high" class="regular-text" placeholder="<?php esc_attr_e( 'banner-1544x500.png', 'licenser' ); ?>" value="<?php echo esc_attr( $product->banners['high'] ); ?>">
                                <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#banner_high" type="button" id="banners_high_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                            </div>
                        </div> 
                    </div>

                </div>
            </div>
  

            <div class="lmfwppt-buttons lmwppt-inner-card card-shameless">
                
                <?php if( isset( $product_id ) ) : ?>
                    <input class="lmfwppt_edit_id" type="hidden" name="id" value="<?php echo esc_attr( $product_id ); ?>">
                <?php endif; ?>
                
                <div class="submit_btn_area"> 
                    <?php submit_button( $submit_button_label, 'primary', 'submit_product_license' ); ?> 
                    <span class="spinner"></span>
                </div>   
                <div class="lmfwppt-notices"></div> 
            </div>
        </form>
    </div>
</div>

<script>
    // Submit Product Form
    jQuery(document).on('submit', '#product-form', function(e) {
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
            url: Licenser.rest_url + 'products',
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
                    jQuery(document).trigger("lmfwppt_notice", ['Product updated successfully.', 'success']);
                } else {
                    jQuery(document).trigger("lmfwppt_notice", ['Product added successfully. Redirecting...', 'success']);
                    // window.location = '/wp-admin/admin.php?page=licenser-'+productType+'s&action=edit&id='+data+'&message=1';
                }
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });
    });

	// Add License Package Field
    jQuery(document).on('click', '.add-license-package', function(){
        let $this = jQuery(this);
        let template = wp.template('license-package-field');
        let fieldLength = jQuery('.lmfwppt_license_field').length;

        // Data push
        jQuery('#license-packages-fields').append(template({
            field_id: lwpGenerateUniqueId(),
        }));

        // Open last item
        jQuery('#license-packages-fields .lmfwppt-toggle-head').last().click();

    });

    // Add Release Field
    jQuery(document).on('click', '.add-release-btn', function(){
        let $this = jQuery(this);
        let template = wp.template('add-release-field');

        // Data push
        jQuery('.add-release-form').html(template());

        // Hide no release found
        jQuery('.hide-on-add-release').hide();
    });

    // Cancel Release Field
    jQuery(document).on('click', '.cancel-release', function(){
        let $this = jQuery(this);

        // Data push
        jQuery('.add-release-form').html('');

        // Show no release found
        jQuery('.hide-on-add-release').show();
    });

    // Load Product Packages
    jQuery(document).on('load_product_packages', function(){
        let productPackages = <?php echo wp_json_encode( $product_packages ); ?>;
        let template = wp.template('license-package-field');

        // Data push
        jQuery('#license-packages-fields').html('');

        // Loop through packages
        jQuery.each( productPackages, function( index, value ) {
            jQuery('#license-packages-fields').append(template({
                field_id: value.id,
                id: value.id,
                label: value.label,
                update_period: value.update_period,
                domain_limit: value.domain_limit,
            }));
        });

    });

    // Remove Package
    jQuery(document).on('lwp_postbox_removed', function(e, postBox){
        let $this = jQuery(postBox);

        // Return if attr is not lwp_delete_package
        if( $this.attr('data-postbox-id') != 'lwp_delete_package' ){
            return;
        }

        // PostBox ID
        let postBoxId = $this.find('.license-package-id').val();

        // Return if no id
        if( !postBoxId ){
            return;
        }
        

        console.log(postBoxId);

        // Remove from database
        jQuery.ajax({
            type: 'delete',
            url: Licenser.rest_url + 'products/packages/' + postBoxId,
            beforeSend: function(xhr) {
                // Nonce
                xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);
            },
            success: function(data) {
                jQuery(document).trigger('load_product_packages');
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },

        });
    });

    // Slugify
    jQuery(document).on('input', '#slug', function(e) {
        e.preventDefault();
        let value = jQuery(this).val();
        
        // Replace spaces with hyphens
        value = value.replace(/\s+/g, '-');
        
        // Remove characters other than letters, numbers, hyphens, and underscores
        value = value.replace(/[^a-zA-Z0-9-_]/g, '');

        jQuery(this).val(value);
    });



    // Document Ready
    jQuery(document).ready(function(){
        jQuery(document).trigger('load_product_packages');
    });

    

</script>

<script type="text/html" id="tmpl-license-package-field">
    <div class="postbox lwp-postbox lmfwppt_license_field" data-postbox-id="lwp_delete_package">
        <!-- Wrapper Start -->
        <input type="hidden" name="license_packages[{{{data.field_id}}}][id]" value="{{{data.id}}}" class="license-package-id">
        <a class="header lmfwppt-toggle-head" data-toggle="collapse">
            <span id="poststuff">
                <h2 class="hndle">
                    <input type="text" class="prevent-toggle-head license-package-name regular-text" name="license_packages[{{{data.field_id}}}][label]" placeholder="License Title: 1yr unlimited domain." value="{{{data.label}}}" title="Change title to anything you like. Make sure they are unique." required />
                    <span class="dashicons indicator_field"></span>
                    <span class="delete_field remove-lwp-postbox">&times;</span>
                </h2>
            </span>
        </a>
        <div class="collapse lmfwppt-toggle-wrap">
            <div class="inside">
                <table class="form-table">

                    <tr valign="top">
                        <th scope="row">
                            <div class="tf-label">
                                <label for="license_packages[{{{data.field_id}}}]-update_period">Update Period</label>
                            </div>
                        </th>
                        <td>
                            <input id="license_packages[{{{data.field_id}}}]-update_period" class="regular-text" type="number" min="1" name="license_packages[{{{data.field_id}}}][update_period]" value="{{{data.update_period}}}" placeholder="Enter in Days"/>
                            <p>Leave empty for lifetime updates.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <div class="tf-label">
                                <label for="license_packages[{{{data.field_id}}}]-domain_limit">Domain Limit</label>
                            </div>
                        </th>
                        <td>
                            <input id="license_packages[{{{data.field_id}}}]-domain_limit" class="regular-text" type="number" min="1" name="license_packages[{{{data.field_id}}}][domain_limit]" value="{{{data.domain_limit}}}" placeholder="How many domains allowed to get updates?" />
                            <p>Leave empty for unlimited domain.</p>
                        </td>
                    </tr>

                </table>
            </div>
        </div>
    <!-- Wrapper end below -->
    </div>
</script>

<script type="text/html" id="tmpl-add-release-field">
    <div class="lmwppt-inner-card">
        <span class="cancel-release">×</span>
        <div class="lmfwppt-form-section" id="product-information">
            <h2><?php esc_html_e( 'Add New Release', 'licenser' ); ?></h2>
        </div>

        <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
            <div class="lwp-col-half">
                <label for="product_version"><?php esc_html_e( 'Product Version', 'licenser' ); ?></label>
                <input type="text" name="version" id="product_version" class="regular-text" placeholder="1.0">
            </div>

            <div class="lwp-col-half">
                <label for="release_date"><?php esc_html_e( 'Release Date', 'licenser' ); ?></label>
                <input type="date" name="release_date" id="release_date" class="regular-text" placeholder="<?php esc_attr_e( '01-23-2023', 'licenser' ); ?>">
            </div>
        </div>

        <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
            <div class="lwp-col-one-third">
                <label for="file_name"><?php esc_html_e( 'File Name', 'licenser' ); ?></label>
                <input type="text" name="file_name" id="file_name" class="regular-text" placeholder="<?php esc_attr_e( 'your-plugin-file-1.0.5.zip', 'licenser' ); ?>">
            </div>

            <div class="lwp-col-two-third">
                <label for="download_link"><?php esc_html_e( 'File URL', 'licenser' ); ?></label>
                <div class="lmfwppt-file-field">
                    <input type="url" name="download_link" id="download_link" class="regular-text" placeholder="<?php esc_attr_e( 'URL of the Theme/Plugin file', 'licenser' ); ?>">
                    <button title="Select Theme/Plugin ZIP File" class="button trigger_media_frame" data-push_selector="#download_link"  type="button" id="download_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                </div>
            </div>
        </div>

        <div class="lmfwppt-form-field">
            <label for="changelog"><?php esc_html_e( 'Changelog', 'licenser' ); ?></label>
            <textarea name="changelog" id="changelog" class="regular-text" placeholder="<?php esc_attr_e( 'Changelog', 'licenser' ); ?>"></textarea>
        </div>
    </div>
</script>