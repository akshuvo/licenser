<?php
// Get Settings
use Licenser\Models\Settings;

$settings = Settings::instance()->get_all();

//$lmfwppt_settings = get_option( 'lmfwppt_settings' );

// $code_prefix = isset( $settings['license_code_prefix'] ) ? sanitize_text_field( $settings['license_code_prefix'] ) : '';
// $character_limit = isset( $settings['license_code_character_limit'] ) ? $settings['license_code_character_limit'] : '32';
// $hide_cart_checkout = isset( $settings['hide_wclm_info_from_cart'] ) ? sanitize_text_field( $settings['hide_wclm_info_from_cart'] ) : '';
// $hide_order_email = isset( $settings['hide_wclm_info_from_ordermeta'] ) ? sanitize_text_field( $settings['hide_wclm_info_from_ordermeta'] ) : '';
// $license_generate_method = isset( $settings['license_generate_method'] ) ? sanitize_text_field( $settings['license_generate_method'] ) : 'microtime';
 
// $code_prefix = Settings::get('license_code_prefix');

?>

<div class="wrap">
    <div class="licenser-root">
        <!-- Header  -->
        <div class="licenser-header">
            <div class="licenser-header__title">
                <h2><?php esc_html_e( 'License Manager Settings', 'licenser' ); ?></h2>
            </div>
        </div>
        <!-- Header  -->
        <!-- Content  -->
        <div class="licenser-content">

            <form action="" method="post" id="setting-add-form">
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="license_code_prefix"><?php esc_html_e('License Code Prefix', 'licenser') ?></label></th>
                        <td><input type="text" name="license_code_prefix" id="license_code_prefix" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Prefix', 'licenser' ); ?>" value="<?php echo isset( $settings['license_code_prefix'] ) ? esc_attr( $settings['license_code_prefix'] ) : '';  ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'License Generate Method', 'licenser' ); ?></th>
                        <td>
                            <fieldset><label>
                                <input name="license_generate_method" type="radio" value="microtime" <?php checked(isset( $settings['license_generate_method'] ) ? sanitize_text_field( $settings['license_generate_method'] ) : 'microtime', "microtime"); ?>><?php esc_html_e( 'Microtime Based', 'licenser' ); ?></label>
                            </fieldset>
                            <fieldset><label>
                                <input name="license_generate_method" type="radio" value="wp_generate" <?php checked(isset( $settings['license_generate_method'] ) ? sanitize_text_field( $settings['license_generate_method'] ) : 'microtime', "wp_generate"); ?>><?php esc_html_e( 'WP Password Based', 'licenser' ); ?></label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="license_code_character_limit"><?php esc_html_e('License Code Character Limit', 'licenser') ?></label></th>
                        <td>
                            <input type="number" min="8" name="license_code_character_limit" id="license_code_character_limit" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Character Limit', 'licenser' ); ?>" value="<?php echo isset( $settings['license_code_character_limit'] ) ? esc_attr( $settings['license_code_character_limit'] ) : '32'; ?>" required>
                            <p><?php esc_html_e( '(Without License Code Prefix)', 'licenser' ); ?></p>
                        </td>
                    </tr>
                    <?php //if( class_exists('LMFWPPTWCEXT')): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Hide License Info from WooCommerce', 'licenser' ); ?></th>
                        <td>
                            <fieldset><label>
                                <input name="hide_wclm_info_from_cart" type="checkbox" id="hide_cart_checkout" <?php checked(isset( $settings['hide_wclm_info_from_cart'] ) ? sanitize_text_field( $settings['hide_wclm_info_from_cart'] ) : '', "on"); ?>><?php esc_html_e( 'Hide from Cart & Checkout', 'licenser' ); ?></label>
                            </fieldset>
                            <fieldset><label>
                                <input name="hide_wclm_info_from_ordermeta" type="checkbox" id="hide_order_email" <?php checked( isset( $settings['hide_wclm_info_from_ordermeta'] ) ? sanitize_text_field( $settings['hide_wclm_info_from_ordermeta'] ) : '', "on"); ?>><?php esc_html_e( 'Hide from Order Email', 'licenser' ); ?></label>
                            </fieldset>
                        </td>
                    </tr>
                    <?php //endif; ?> 

                    </tbody>
                </table>

                <div class="submit_btn_area"> 
                    <?php submit_button( __( 'Save', 'licenser' ), 'primary' ); ?> 
                    <span class="spinner"></span>
                </div>
                <div class="lmfwppt-notices"></div>
            </form>
        </div>
        <!-- Content  -->
    </div>
</div>

<script>
   jQuery(document).on('submit', '#setting-add-form', function(e) {
        e.preventDefault();
        let $this = jQuery(this);

        let formData = new FormData(this);

        // Convert FormData to JSON
        let jsonObject = {};
        formData.forEach(function(value, key){
            
            jsonObject[key] = value;
            
        });

        // Get Product type
        let productType = jQuery('#product_type').val();

        console.log(jsonObject);

        jQuery.ajax({
            type: 'post',
            url: Licenser.rest_url + 'settings',
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
                    jQuery(document).trigger("lmfwppt_notice", ['Settings updated successfully.', 'success']);
                } else {
                    jQuery(document).trigger("lmfwppt_notice", ['Settings added successfully.', 'success']);
                    // window.location = '/wp-admin/admin.php?page=licenser-'+productType+'s&action=edit&id='+data+'&message=1';
                }
            },
            error: function(data) {
                jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
            },
        });
    });
</script>