<?php
// Get Settings
$lmfwppt_settings = get_option( 'lmfwppt_settings' );

$code_prefix = isset( $lmfwppt_settings['license_code_prefix'] ) ? sanitize_text_field( $lmfwppt_settings['license_code_prefix'] ) : '';
$character_limit = isset( $lmfwppt_settings['license_code_character_limit'] ) ? $lmfwppt_settings['license_code_character_limit'] : '32';
$hide_cart_checkout = isset( $lmfwppt_settings['hide_wclm_info_from_cart'] ) ? sanitize_text_field( $lmfwppt_settings['hide_wclm_info_from_cart'] ) : '';
$hide_order_email = isset( $lmfwppt_settings['hide_wclm_info_from_ordermeta'] ) ? sanitize_text_field( $lmfwppt_settings['hide_wclm_info_from_ordermeta'] ) : '';
$license_generate_method = isset( $lmfwppt_settings['license_generate_method'] ) ? sanitize_text_field( $lmfwppt_settings['license_generate_method'] ) : 'microtime';
 

?>


<div class="wrap">
   <h1 class="wp-heading-inline"><?php _e( 'License Manager Settings', 'lmfwppt' ); ?></h1>

      <form action="" method="post" id="setting-add-form">
         <table class="form-table" role="presentation">
            <tbody>
               <tr>
                  <th scope="row"><label for="license_code_prefix"><?php esc_html_e('License Code Prefix', 'lmfwppt') ?></label></th>
                  <td><input type="text" name="lmfwppt_settings[license_code_prefix]" id="license_code_prefix" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Prefix', 'lmfwppt' ); ?>" value="<?php echo $code_prefix  ?>"></td>
               </tr>
               <tr>
                  <th scope="row"><?php esc_html_e( 'License Generate Method', 'lmfwppt' ); ?></th>
                   <td>
                       <fieldset><label>
                           <input name="lmfwppt_settings[license_generate_method]" type="radio" value="microtime" <?php checked($license_generate_method, "microtime"); ?>><?php esc_html_e( 'Microtime Based', 'lmfwppt' ); ?></label>
                       </fieldset>
                       <fieldset><label>
                           <input name="lmfwppt_settings[license_generate_method]" type="radio" value="wp_generate" <?php checked($license_generate_method, "wp_generate"); ?>><?php esc_html_e( 'WP Password Based', 'lmfwppt' ); ?></label>
                       </fieldset>
                   </td>
               </tr>
               <tr>
                  <th scope="row"><label for="license_code_character_limit"><?php esc_html_e('License Code Character Limit', 'lmfwppt') ?></label></th>
                  <td>
                     <input type="number" min="8" name="lmfwppt_settings[license_code_character_limit]" id="license_code_character_limit" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Character Limit', 'lmfwppt' ); ?>" value="<?php echo $character_limit; ?>" required>
                     <p><?php esc_html_e( '(Without License Code Prefix)', 'lmfwppt' ); ?></p>
                  </td>
               </tr>
            <?php if( class_exists('LMFWPPTWCEXT')): ?>
               <tr>
                  <th scope="row"><?php esc_html_e( 'Hide License Info from WooCommerce', 'lmfwppt' ); ?></th>
                   <td>
                       <fieldset><label>
                           <input name="lmfwppt_settings[hide_wclm_info_from_cart]" type="checkbox" id="hide_cart_checkout" <?php checked($hide_cart_checkout, "on"); ?>><?php esc_html_e( 'Hide from Cart & Checkout', 'lmfwppt' ); ?></label>
                       </fieldset>
                       <fieldset><label>
                           <input name="lmfwppt_settings[hide_wclm_info_from_ordermeta]" type="checkbox" id="hide_order_email" <?php checked($hide_order_email, "on"); ?>><?php esc_html_e( 'Hide from Order Email', 'lmfwppt' ); ?></label>
                       </fieldset>
                   </td>
               </tr>
            <?php endif; ?> 

            </tbody>
         </table>
         <input type="hidden" name="lmaction" value="setting_add_form">
         <div class="submit_btn_area"> 
            <?php submit_button( __( 'Save', 'lmfwppt' ), 'primary' ); ?> 
            <span class="spinner"></span>
         </div>
         <div class="lmfwppt-notices"></div>
      </form>

</div>
