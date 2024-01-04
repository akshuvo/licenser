<?php
// License Class
$license_handler = new Licenser\Licenses();

// Product Class
$product_handler = new Licenser\Products();

$product_defaults_args = array (
    'name' => '',
    'slug' => '',
    'product_type' => '',
    'version' => '',
    'tested' => '',
    'requires' => '',
    'requires_php' => '',
    'download_link' => '',
    'banners' => '',
    'sections' => '',
    'author' => '',
    'created_by' => '',
    'dated' => '',
);


$get_product = array();
$get_packages = null;

$submit_button_label = __( 'Add Product', 'licenser' );

if ( isset( $_GET['action'] ) && $_GET['action'] == "edit" && isset( $_GET['id'] ) ) {
    $product_id = intval( $_GET['id'] );

    // Get Product date 
    $get_product = $product_handler->get_product( $product_id );

    // Get packages data
    $get_packages = $product_handler->get_packages( $product_id );

    $submit_button_label = __( 'Edit Product', 'licenser' );

}

// Parse incoming $args into an array and merge it with $defaults
$get_product = wp_parse_args( $get_product, $product_defaults_args );
// Let's extract the array to variable
extract( $get_product );

// banners unserialize
$banner = unserialize( $banners );
$low = isset ( $banner['low'] ) ? $banner['low'] : '';
$high = isset ( $banner['high'] ) ? $banner['high'] : '';

// sections unserialize
$sections_arr = unserialize($sections);

?>
<div class="wrap">
    <div class="lmwppt-wrap">

        <div class="lmwppt-inner-card card-shameless">
            <?php if( isset( $_GET['id'] ) ) : ?>
                <h1><?php _e( 'Edit Product', 'licenser' ); ?></h1>
            <?php else : ?>
                <h1><?php _e( 'Add New Product', 'licenser' ); ?></h1>
            <?php endif; ?>
        </div>

        <form action="" method="post" id="product-form">
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Product Information', 'licenser' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="name"><?php esc_html_e( 'Product Name', 'licenser' ); ?></label>
                        <input type="text" name="name" id="name" class="regular-text product_name_input" placeholder="Your Theme or Plugin Name" value="<?php echo esc_attr( $name ); ?>" required>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="slug"><?php esc_html_e( 'Product Slug', 'licenser' ); ?></label>
                        <input type="text" name="slug" id="slug" class="regular-text product_slug_input" placeholder="your-theme-or-plugin-name" value="<?php echo esc_attr( $slug ); ?>" required>
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
                            <select name="product_type" id="product_type">
                                <option value="plugin" <?php selected( $product_type, 'plugin' ); ?> ><?php esc_html_e( 'Plugin', 'licenser' ); ?></option>
                                <option value="theme" <?php selected( $product_type, 'theme' ); ?> ><?php esc_html_e( 'Theme', 'licenser' ); ?></option>
                            </select>
                        </div>
                        <div class="lwp-col-half">
                            <label for="requires_php"><?php esc_html_e( 'Requires PHP Version', 'licenser' ); ?></label>
                            <input type="text" name="requires_php" id="requires_php" class="regular-text" placeholder="<?php esc_attr_e( '7.4', 'licenser' ); ?>" value="<?php echo esc_attr( $requires_php ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="requires"><?php esc_html_e( 'Requires WordPress Version', 'licenser' ); ?></label>
                            <input type="text" name="requires" id="requires" class="regular-text" placeholder="<?php esc_attr_e( '4.7', 'licenser' ); ?>" value="<?php echo esc_attr( $requires ); ?>">
                        </div>
                        <div class="lwp-col-half">
                            <label for="product_tested"><?php esc_html_e( 'Tested up to', 'licenser' ); ?></label>
                            <input type="text" name="tested" id="product_tested" class="regular-text" placeholder="<?php esc_attr_e( '5.7', 'licenser' ); ?>" value="<?php echo esc_attr( $tested ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="author"><?php esc_html_e( 'Author Name', 'licenser' ); ?></label>
                        <input type="text" name="author" id="author" class="regular-text product_name_input" placeholder="Author Name" value="<?php echo esc_attr( $author ); ?>">
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="homepage_url"><?php esc_html_e( 'Homepage URL', 'licenser' ); ?></label>
                            <input type="text" name="homepage_url" id="homepage_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $homepage_url ); ?>">
                        </div>

                        <div class="lwp-col-half">
                            <label for="demo_url"><?php esc_html_e( 'Demo URL', 'licenser' ); ?></label>
                            <input type="text" name="demo_url" id="demo_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $demo_url ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="description"><?php esc_html_e( 'Description', 'licenser' ); ?></label>
                        <textarea name="description" id="description" class="regular-text" placeholder="<?php esc_attr_e( 'Description', 'licenser' ); ?>"><?php echo esc_attr( $description ); ?></textarea>
                    </div>
                    

                    <!-- Release -->

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="product_version"><?php esc_html_e( 'Product Version', 'licenser' ); ?></label>
                            <input type="text" name="version" id="product_version" class="regular-text" placeholder="1.0" value="<?php echo esc_attr( $version ); ?>">
                        </div>

                        <div class="lwp-col-half">
                            <label for="release_date"><?php esc_html_e( 'Release Date', 'licenser' ); ?></label>
                            <input type="text" name="release_date" id="release_date" class="regular-text" placeholder="<?php esc_attr_e( '2021-01-01', 'licenser' ); ?>" value="<?php echo esc_attr( $release_date ); ?>">
                        </div>
                    </div>

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-one-third">
                            <label for="file_name"><?php esc_html_e( 'File Name', 'licenser' ); ?></label>
                            <input type="text" name="version" id="file_name" class="regular-text" placeholder="<?php esc_attr_e( 'your-plugin-file-1.0.5.zip', 'licenser' ); ?>" value="<?php echo esc_attr( $file_name ); ?>">
                        </div>

                        <div class="lwp-col-two-third">
                            <label for="download_link"><?php esc_html_e( 'File URL', 'licenser' ); ?></label>
                            <div class="lmfwppt-file-field">
                                <input type="text" name="download_link" id="download_link" class="regular-text" placeholder="<?php esc_attr_e( 'URL of the Theme/Plugin file', 'licenser' ); ?>" value="<?php echo esc_attr( $download_link ); ?>">
                                <button title="Select Theme/Plugin ZIP File" class="button trigger_media_frame" data-push_selector="#download_link"  type="button" id="download_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="lmfwppt-form-field">
                        <label for="changelog"><?php esc_html_e( 'Changelog', 'licenser' ); ?></label>
                        <textarea name="changelog" id="changelog" class="regular-text" placeholder="<?php esc_attr_e( 'Changelog', 'licenser' ); ?>"><?php echo esc_attr( $changelog ); ?></textarea>
                    </div>

                    <!-- /Release -->

                </div>
            </div>
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="license-information">
                    <h2><?php esc_html_e( 'License Packages', 'licenser' ); ?></h2>
                    <div id="license-information-fields">
                        <?php $product_handler->get_packages_html( $get_packages ); ?>
                    </div>
                    <button class="button add-license-information" type="button"><?php esc_html_e( 'Add License Package', 'licenser' ); ?></button>
                </div>
            </div>
            <!-- banner -->
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="product-information">
                    <h2><?php esc_html_e( 'Banners and Icon', 'licenser' ); ?></h2>

                    <div class="lmfwppt-form-field">
                        <label for="icon_url"><?php esc_html_e( 'Icon', 'licenser' ); ?></label>
                        <div class="lmfwppt-file-field">
                            <input type="text" name="icon_url" id="icon_url" class="regular-text" placeholder="<?php esc_attr_e( 'icon-128x128.png', 'licenser' ); ?>" value="<?php echo $low; ?>">
                            <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#icon_url" type="button" id="icon_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                        </div>
                    </div> 

                    <div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
                        <div class="lwp-col-half">
                            <label for="banner_low"><?php esc_html_e( 'Banner Low', 'licenser' ); ?></label>
                            <div class="lmfwppt-file-field">
                                <input type="text" name="banners[low]" id="banner_low" class="regular-text" placeholder="<?php esc_attr_e( 'banner-772x250.png', 'licenser' ); ?>" value="<?php echo $low; ?>">
                                <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#banner_low" type="button" id="banners_low_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                            </div>
                        </div> 
                        <div class="lwp-col-half">
                            <label for="banner_high"><?php esc_html_e( 'Banner High Resolution', 'licenser' ); ?></label>
                            <div class="lmfwppt-file-field">
                                <input type="text" name="banners[high]" id="banner_high" class="regular-text" placeholder="<?php esc_attr_e( 'banner-1544x500.png', 'licenser' ); ?>" value="<?php echo $high; ?>">
                                <button title="Select Banner Image" class="button trigger_media_frame" data-push_selector="#banner_high" type="button" id="banners_high_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
                            </div>
                        </div> 
                    </div>

                </div>
            </div>
            <!-- sections -->
            <div class="lmwppt-inner-card">
                <div class="lmfwppt-form-section" id="license-information">
                    <h2><?php esc_html_e( 'Sections', 'licenser' ); ?></h2>
                    <div id="section-information-fields">
                        <?php $product_handler::get_section_html( $sections_arr ); ?>
                    </div>
                    <button class="button add-section-information" type="button"><?php esc_html_e( 'Add Section Package', 'licenser' ); ?></button>
                </div>
            </div>
            <div class="lmwppt-inner-card">
                
            </div>

            <div class="lmfwppt-buttons lmwppt-inner-card card-shameless">
                <input type="hidden" name="lmaction" value="product_add_form">
                <input type="hidden" name="created_by" value="<?php _e( get_current_user_id() ); ?>">
                
                <?php if( isset( $product_id ) ) : ?>
                    <input class="lmfwppt_edit_id" type="hidden" name="product_id" value="<?php _e( $product_id ); ?>">
                <?php endif; ?>
                
                <?php wp_nonce_field( 'lmfwppt-add-product-nonce' ); ?>
                <div class="submit_btn_area"> 
                    <?php submit_button( $submit_button_label, 'primary', 'submit_product_license' ); ?> 
                    <span class="spinner"></span>
                </div>   
                <div class="lmfwppt-notices"></div> 
            </div>
            
        </form>

    </div>
</div>

 