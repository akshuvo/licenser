<?php
/**
 * Admin settings page template.
 *
 * @package Licenser
 */

use Licenser\Models\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_settings        = Settings::instance()->get_all();
$licenser_code_prefix     = isset( $licenser_settings['license_code_prefix'] ) ? $licenser_settings['license_code_prefix'] : '';
$licenser_generate_method = isset( $licenser_settings['license_generate_method'] ) ? $licenser_settings['license_generate_method'] : 'microtime';
$licenser_char_limit      = isset( $licenser_settings['license_code_character_limit'] ) ? $licenser_settings['license_code_character_limit'] : '32';
$licenser_hide_cart       = isset( $licenser_settings['hide_wclm_info_from_cart'] ) ? $licenser_settings['hide_wclm_info_from_cart'] : '';
$licenser_hide_ordermeta  = isset( $licenser_settings['hide_wclm_info_from_ordermeta'] ) ? $licenser_settings['hide_wclm_info_from_ordermeta'] : '';
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2><?php esc_html_e( 'License Manager Settings', 'licenser' ); ?></h2>
			</div>
		</div>

		<div class="licenser-content">
			<form action="" method="post" id="setting-add-form">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="license_code_prefix"><?php esc_html_e( 'License Code Prefix', 'licenser' ); ?></label></th>
							<td>
								<input type="text" name="license_code_prefix" id="license_code_prefix" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Prefix', 'licenser' ); ?>" value="<?php echo esc_attr( $licenser_code_prefix ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'License Generate Method', 'licenser' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input name="license_generate_method" type="radio" value="microtime" <?php checked( $licenser_generate_method, 'microtime' ); ?>>
										<?php esc_html_e( 'Microtime Based', 'licenser' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label>
										<input name="license_generate_method" type="radio" value="wp_generate" <?php checked( $licenser_generate_method, 'wp_generate' ); ?>>
										<?php esc_html_e( 'WP Password Based', 'licenser' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label>
										<input name="license_generate_method" type="radio" value="uuid" <?php checked( $licenser_generate_method, 'uuid' ); ?>>
										<?php esc_html_e( 'UUID v4', 'licenser' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="license_code_character_limit"><?php esc_html_e( 'License Code Character Limit', 'licenser' ); ?></label></th>
							<td>
								<input type="number" min="8" name="license_code_character_limit" id="license_code_character_limit" class="regular-text" placeholder="<?php esc_attr_e( 'License Code Character Limit', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_char_limit ); ?>" required>
								<p><?php esc_html_e( '(Without License Code Prefix)', 'licenser' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Hide License Info from WooCommerce', 'licenser' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input name="hide_wclm_info_from_cart" type="checkbox" id="hide_cart_checkout" value="on" <?php checked( $licenser_hide_cart, 'on' ); ?>>
										<?php esc_html_e( 'Hide from Cart & Checkout', 'licenser' ); ?>
									</label>
								</fieldset>
								<fieldset>
									<label>
										<input name="hide_wclm_info_from_ordermeta" type="checkbox" id="hide_order_email" value="on" <?php checked( $licenser_hide_ordermeta, 'on' ); ?>>
										<?php esc_html_e( 'Hide from Order Email', 'licenser' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="submit_btn_area">
					<?php submit_button( __( 'Save', 'licenser' ), 'primary' ); ?>
					<span class="spinner"></span>
				</div>
				<div class="lmfwppt-notices"></div>
			</form>
		</div>
	</div>
</div>
