<?php
/**
 * Admin settings page template.
 *
 * @package Licenser
 */

use Licenser\Controllers\Addons;
use Licenser\Models\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_settings        = Settings::instance()->get_all();
$licenser_code_prefix     = isset( $licenser_settings['license_code_prefix'] ) ? (string) $licenser_settings['license_code_prefix'] : '';
$licenser_generate_method = isset( $licenser_settings['license_generate_method'] ) ? (string) $licenser_settings['license_generate_method'] : 'microtime';
$licenser_char_limit      = isset( $licenser_settings['license_code_character_limit'] ) ? absint( $licenser_settings['license_code_character_limit'] ) : 32;
$licenser_hide_cart       = isset( $licenser_settings['hide_wclm_info_from_cart'] ) ? (string) $licenser_settings['hide_wclm_info_from_cart'] : '';
$licenser_hide_ordermeta  = isset( $licenser_settings['hide_wclm_info_from_ordermeta'] ) ? (string) $licenser_settings['hide_wclm_info_from_ordermeta'] : '';
$licenser_qty_mode        = isset( $licenser_settings['wc_quantity_mode'] ) ? (string) $licenser_settings['wc_quantity_mode'] : 'separate_keys';
$licenser_self_deactivate = isset( $licenser_settings['allow_customer_domain_deactivation'] ) ? (string) $licenser_settings['allow_customer_domain_deactivation'] : 'on';
$licenser_auto_suspend    = isset( $licenser_settings['auto_suspend_on_refund'] ) ? (string) $licenser_settings['auto_suspend_on_refund'] : 'on';

$licenser_addons         = Addons::instance();
$licenser_wc_enabled     = $licenser_addons->is_enabled( 'woocommerce' );
$licenser_wc_plugin      = $licenser_addons->is_dependency_active( 'woocommerce' );
$licenser_public_api_url = untrailingslashit( rest_url( 'licenser/v1/public' ) );

/**
 * Filter additional tabs displayed on the Licenser Settings page.
 *
 * Each entry supports `slug`, `label`, `icon` (dashicon class), and optional `dot_class`.
 *
 * @param array<int, array<string, string>> $tabs Extra tab definitions.
 */
$licenser_extra_tabs       = apply_filters( 'licenser_settings_tabs', [] );
$licenser_extra_tabs       = is_array( $licenser_extra_tabs ) ? $licenser_extra_tabs : [];
$licenser_store_extra_tabs = [];
$licenser_other_extra_tabs = [];

$licenser_allowed_tabs = [ 'general', 'woocommerce', 'api' ];
foreach ( $licenser_extra_tabs as $licenser_extra_tab ) {
	if ( ! empty( $licenser_extra_tab['slug'] ) ) {
		$licenser_allowed_tabs[] = sanitize_key( (string) $licenser_extra_tab['slug'] );
		if ( isset( $licenser_extra_tab['position'] ) && 'after_woocommerce' === $licenser_extra_tab['position'] ) {
			$licenser_store_extra_tabs[] = $licenser_extra_tab;
		} else {
			$licenser_other_extra_tabs[] = $licenser_extra_tab;
		}
	}
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only settings tab navigation.
$licenser_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
if ( ! in_array( $licenser_active_tab, $licenser_allowed_tabs, true ) ) {
	$licenser_active_tab = 'general';
}

$licenser_wc_dot_class = 'licenser-tab__dot--inactive';
if ( $licenser_wc_enabled ) {
	$licenser_wc_dot_class = $licenser_wc_plugin ? 'licenser-tab__dot--active' : 'licenser-tab__dot--warning';
}
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons dashicons-admin-settings"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php esc_html_e( 'Licenser Settings', 'licenser' ); ?></h1>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<button type="button" class="licenser-btn licenser-btn--primary" data-licenser-submit-form="#setting-add-form">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Settings', 'licenser' ); ?>
				</button>
			</div>
		</div>

		<div class="licenser-main licenser-main--narrow">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>

			<div class="licenser-settings-tabs-bar">
				<div class="licenser-pills" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'licenser' ); ?>">
					<button type="button" id="licenser-tab-btn-general" class="licenser-pill <?php echo 'general' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tab" aria-controls="licenser-tab-general" aria-selected="<?php echo 'general' === $licenser_active_tab ? 'true' : 'false'; ?>" data-licenser-tab="general" data-licenser-tab-sync="1">
						<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
						<?php esc_html_e( 'General', 'licenser' ); ?>
					</button>
					<button type="button" id="licenser-tab-btn-woocommerce" class="licenser-pill <?php echo 'woocommerce' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tab" aria-controls="licenser-tab-woocommerce" aria-selected="<?php echo 'woocommerce' === $licenser_active_tab ? 'true' : 'false'; ?>" data-licenser-tab="woocommerce" data-licenser-tab-sync="1">
						<span class="dashicons dashicons-cart" aria-hidden="true"></span>
						<?php esc_html_e( 'WooCommerce', 'licenser' ); ?>
						<span class="licenser-tab__dot <?php echo esc_attr( $licenser_wc_dot_class ); ?>" id="licenser-wc-tab-dot" aria-hidden="true"></span>
					</button>
					<?php
					foreach ( $licenser_store_extra_tabs as $licenser_extra_tab ) :
						$licenser_tab_slug   = ! empty( $licenser_extra_tab['slug'] ) ? sanitize_key( (string) $licenser_extra_tab['slug'] ) : '';
						$licenser_tab_label  = ! empty( $licenser_extra_tab['label'] ) ? (string) $licenser_extra_tab['label'] : $licenser_tab_slug;
						$licenser_tab_icon   = ! empty( $licenser_extra_tab['icon'] ) ? sanitize_html_class( (string) $licenser_extra_tab['icon'] ) : 'dashicons-admin-generic';
						$licenser_tab_dot    = ! empty( $licenser_extra_tab['dot_class'] ) ? sanitize_html_class( (string) $licenser_extra_tab['dot_class'] ) : '';
						$licenser_tab_dot_id = ! empty( $licenser_extra_tab['dot_id'] ) ? sanitize_html_class( (string) $licenser_extra_tab['dot_id'] ) : '';
						$licenser_is_cur     = ( $licenser_tab_slug === $licenser_active_tab );
						?>
						<button type="button" id="licenser-tab-btn-<?php echo esc_attr( $licenser_tab_slug ); ?>" class="licenser-pill <?php echo $licenser_is_cur ? 'is-active' : ''; ?>" role="tab" aria-controls="licenser-tab-<?php echo esc_attr( $licenser_tab_slug ); ?>" aria-selected="<?php echo $licenser_is_cur ? 'true' : 'false'; ?>" data-licenser-tab="<?php echo esc_attr( $licenser_tab_slug ); ?>" data-licenser-tab-sync="1">
							<span class="dashicons <?php echo esc_attr( $licenser_tab_icon ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $licenser_tab_label ); ?>
							<?php if ( '' !== $licenser_tab_dot ) : ?>
								<span class="licenser-tab__dot <?php echo esc_attr( $licenser_tab_dot ); ?>"<?php echo '' !== $licenser_tab_dot_id ? ' id="' . esc_attr( $licenser_tab_dot_id ) . '"' : ''; ?> aria-hidden="true"></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
					<button type="button" id="licenser-tab-btn-api" class="licenser-pill <?php echo 'api' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tab" aria-controls="licenser-tab-api" aria-selected="<?php echo 'api' === $licenser_active_tab ? 'true' : 'false'; ?>" data-licenser-tab="api" data-licenser-tab-sync="1">
						<span class="dashicons dashicons-rest-api" aria-hidden="true"></span>
						<?php esc_html_e( 'API & Maintenance', 'licenser' ); ?>
					</button>
					<?php
					foreach ( $licenser_other_extra_tabs as $licenser_extra_tab ) :
						$licenser_tab_slug = ! empty( $licenser_extra_tab['slug'] ) ? sanitize_key( (string) $licenser_extra_tab['slug'] ) : '';
						if ( '' === $licenser_tab_slug ) {
							continue;
						}
						$licenser_tab_label  = ! empty( $licenser_extra_tab['label'] ) ? (string) $licenser_extra_tab['label'] : $licenser_tab_slug;
						$licenser_tab_icon   = ! empty( $licenser_extra_tab['icon'] ) ? sanitize_html_class( (string) $licenser_extra_tab['icon'] ) : 'dashicons-admin-generic';
						$licenser_tab_dot    = ! empty( $licenser_extra_tab['dot_class'] ) ? sanitize_html_class( (string) $licenser_extra_tab['dot_class'] ) : '';
						$licenser_tab_dot_id = ! empty( $licenser_extra_tab['dot_id'] ) ? sanitize_html_class( (string) $licenser_extra_tab['dot_id'] ) : '';
						$licenser_is_cur     = ( $licenser_tab_slug === $licenser_active_tab );
						?>
						<button type="button" id="licenser-tab-btn-<?php echo esc_attr( $licenser_tab_slug ); ?>" class="licenser-pill <?php echo $licenser_is_cur ? 'is-active' : ''; ?>" role="tab" aria-controls="licenser-tab-<?php echo esc_attr( $licenser_tab_slug ); ?>" aria-selected="<?php echo $licenser_is_cur ? 'true' : 'false'; ?>" data-licenser-tab="<?php echo esc_attr( $licenser_tab_slug ); ?>" data-licenser-tab-sync="1">
							<span class="dashicons <?php echo esc_attr( $licenser_tab_icon ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $licenser_tab_label ); ?>
							<?php if ( '' !== $licenser_tab_dot ) : ?>
								<span class="licenser-tab__dot <?php echo esc_attr( $licenser_tab_dot ); ?>"<?php echo '' !== $licenser_tab_dot_id ? ' id="' . esc_attr( $licenser_tab_dot_id ) . '"' : ''; ?> aria-hidden="true"></span>
							<?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<form action="" method="post" id="setting-add-form">
				<!-- Tab 1: General (License Key Generation) -->
				<div id="licenser-tab-general" class="licenser-tab-panel <?php echo 'general' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-general">
					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'License Key Generation', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Customize how new license keys are formatted across manual, bulk, and store issuance', 'licenser' ); ?></p>
							</div>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<label for="license_code_prefix"><?php esc_html_e( 'Key Prefix', 'licenser' ); ?></label>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Optional prefix prepended to every newly generated key (e.g. PRO- or AM_).', 'licenser' ); ?></p>
								</div>
								<div class="licenser-field">
									<input type="text" name="license_code_prefix" id="license_code_prefix" class="licenser-mono" placeholder="<?php esc_attr_e( 'e.g. LIC-', 'licenser' ); ?>" value="<?php echo esc_attr( $licenser_code_prefix ); ?>" />
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<span class="licenser-setting-row__title"><?php esc_html_e( 'Generation Method', 'licenser' ); ?></span>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Choose the algorithm used for generating unique license tokens.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-radio-cards">
									<label class="licenser-radio-card">
										<input name="license_generate_method" type="radio" value="microtime" <?php checked( $licenser_generate_method, 'microtime' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'Microtime Hash', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( '32-char hexadecimal hash', 'licenser' ); ?></span>
									</label>
									<label class="licenser-radio-card">
										<input name="license_generate_method" type="radio" value="wp_generate" <?php checked( $licenser_generate_method, 'wp_generate' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'WP Password', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( 'Alphanumeric, custom length', 'licenser' ); ?></span>
									</label>
									<label class="licenser-radio-card">
										<input name="license_generate_method" type="radio" value="uuid" <?php checked( $licenser_generate_method, 'uuid' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'UUID v4', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( 'Standard RFC 4122 UUID', 'licenser' ); ?></span>
									</label>
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<label for="license_code_character_limit"><?php esc_html_e( 'Character Length', 'licenser' ); ?></label>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Applies to WP Password method (excluding prefix). Range: 8–64 characters.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-field">
									<input type="number" min="8" max="64" name="license_code_character_limit" id="license_code_character_limit" value="<?php echo esc_attr( (string) $licenser_char_limit ); ?>" required />
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<span class="licenser-setting-row__title"><?php esc_html_e( 'Live Sample Preview', 'licenser' ); ?></span>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Preview updates immediately as you adjust prefix, algorithm, or length.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-key-preview">
									<code id="licenser-sample-key" class="licenser-key-preview__code"></code>
									<button type="button" id="licenser-regen-sample" class="licenser-btn licenser-btn--secondary licenser-btn--sm">
										<span class="dashicons dashicons-randomize"></span>
										<?php esc_html_e( 'Regenerate', 'licenser' ); ?>
									</button>
								</div>
							</div>
						</div>
						<div class="licenser-card__footer">
							<span class="licenser-help"><?php esc_html_e( 'All settings are stored cleanly in option licenser_settings.', 'licenser' ); ?></span>
							<button type="submit" class="licenser-btn licenser-btn--primary">
								<span class="licenser-inline-spinner" hidden></span>
								<?php esc_html_e( 'Save Settings', 'licenser' ); ?>
							</button>
						</div>
					</div>
				</div>

				<!-- Tab 2: WooCommerce Integration -->
				<div id="licenser-tab-woocommerce" class="licenser-tab-panel <?php echo 'woocommerce' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-woocommerce">
					<div class="licenser-card" id="licenser-section-woocommerce">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'WooCommerce Integration', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Control checkout metadata, multi-quantity orders, refund handling, and customer self-service', 'licenser' ); ?></p>
							</div>
							<span id="licenser-wc-settings-badge" class="licenser-badge <?php echo esc_attr( ! $licenser_wc_enabled ? 'licenser-badge--inactive' : ( $licenser_wc_plugin ? 'licenser-badge--active' : 'licenser-badge--warning' ) ); ?>">
								<span class="licenser-badge__dot"></span>
								<span class="licenser-badge-label">
									<?php
									if ( ! $licenser_wc_enabled ) {
										esc_html_e( 'Disabled in Addons', 'licenser' );
									} elseif ( $licenser_wc_plugin ) {
										esc_html_e( 'WooCommerce Active', 'licenser' );
									} else {
										esc_html_e( 'Requires WooCommerce', 'licenser' );
									}
									?>
								</span>
							</span>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-inline-callout" id="licenser-wc-disabled-callout" <?php echo $licenser_wc_enabled ? 'hidden' : ''; ?>>
								<div class="licenser-inline-callout__text">
									<strong><?php esc_html_e( 'WooCommerce Lite is currently disabled', 'licenser' ); ?></strong>
									<span><?php esc_html_e( 'WooCommerce integration files and hooks are not loaded. You can still review or pre-configure these options, and enable the integration anytime.', 'licenser' ); ?></span>
								</div>
								<div class="licenser-inline-callout__actions">
									<button type="button" class="licenser-btn licenser-btn--primary licenser-btn--sm licenser-quick-enable-integration" data-integration="woocommerce">
										<?php esc_html_e( 'Enable WooCommerce Lite', 'licenser' ); ?>
									</button>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-addons' ) ); ?>" class="licenser-btn licenser-btn--secondary licenser-btn--sm">
										<?php esc_html_e( 'Manage in Addons', 'licenser' ); ?> &rarr;
									</a>
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<span class="licenser-setting-row__title"><?php esc_html_e( 'Multi-Quantity Orders', 'licenser' ); ?></span>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'How Licenser generates keys when a customer purchases quantity > 1 of a licensed product.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-radio-cards">
									<label class="licenser-radio-card">
										<input name="wc_quantity_mode" type="radio" value="separate_keys" <?php checked( $licenser_qty_mode, 'separate_keys' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'Separate Keys', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( 'Qty 3 generates 3 individual keys', 'licenser' ); ?></span>
									</label>
									<label class="licenser-radio-card">
										<input name="wc_quantity_mode" type="radio" value="multiply_domains" <?php checked( $licenser_qty_mode, 'multiply_domains' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'Multiply Sites', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( '1 key with domain limit × qty', 'licenser' ); ?></span>
									</label>
									<label class="licenser-radio-card">
										<input name="wc_quantity_mode" type="radio" value="single_legacy" <?php checked( $licenser_qty_mode, 'single_legacy' ); ?> />
										<span class="licenser-radio-card__title"><?php esc_html_e( 'Single Key', 'licenser' ); ?></span>
										<span class="licenser-radio-card__desc"><?php esc_html_e( 'Always 1 license per line item', 'licenser' ); ?></span>
									</label>
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<span class="licenser-setting-row__title"><?php esc_html_e( 'Customer Portal & Lifecycle', 'licenser' ); ?></span>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Self-service controls and automated order lifecycle rules.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-toggle-stack">
									<div class="licenser-toggle-item">
										<div class="licenser-toggle-item__text">
											<strong><?php esc_html_e( 'Allow Customer Self-Service Domain Deactivation', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Customers can deactivate their own site activations directly inside My Account → License Manager.', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input name="allow_customer_domain_deactivation" id="allow_customer_domain_deactivation" type="checkbox" value="on" aria-label="<?php esc_attr_e( 'Allow Customer Self-Service Domain Deactivation', 'licenser' ); ?>" <?php checked( $licenser_self_deactivate, 'on' ); ?> />
										</label>
									</div>

									<div class="licenser-toggle-item">
										<div class="licenser-toggle-item__text">
											<strong><?php esc_html_e( 'Auto-Suspend Licenses on Refund / Cancel', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Automatically set linked licenses to Inactive when an order is refunded or cancelled (re-activates if restored).', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input name="auto_suspend_on_refund" id="auto_suspend_on_refund" type="checkbox" value="on" aria-label="<?php esc_attr_e( 'Auto-Suspend Licenses on Refund or Cancel', 'licenser' ); ?>" <?php checked( $licenser_auto_suspend, 'on' ); ?> />
										</label>
									</div>
								</div>
							</div>

							<div class="licenser-setting-row">
								<div class="licenser-setting-row__info">
									<span class="licenser-setting-row__title"><?php esc_html_e( 'Cart & Email Visibility', 'licenser' ); ?></span>
									<p class="licenser-setting-row__desc"><?php esc_html_e( 'Optionally suppress Licenser metadata lines in WooCommerce cart, checkout, or order emails.', 'licenser' ); ?></p>
								</div>
								<div class="licenser-toggle-stack">
									<div class="licenser-toggle-item">
										<div class="licenser-toggle-item__text">
											<strong><?php esc_html_e( 'Hide from Cart & Checkout', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Do not show Product Type / Package summary rows beneath cart items.', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input name="hide_wclm_info_from_cart" type="checkbox" id="hide_cart_checkout" value="on" aria-label="<?php esc_attr_e( 'Hide from Cart and Checkout', 'licenser' ); ?>" <?php checked( $licenser_hide_cart, 'on' ); ?> />
										</label>
									</div>

									<div class="licenser-toggle-item">
										<div class="licenser-toggle-item__text">
											<strong><?php esc_html_e( 'Hide from Order Emails & Order Meta', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Do not append license key and download links to WooCommerce order emails.', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input name="hide_wclm_info_from_ordermeta" type="checkbox" id="hide_order_email" value="on" aria-label="<?php esc_attr_e( 'Hide from Order Emails and Order Meta', 'licenser' ); ?>" <?php checked( $licenser_hide_ordermeta, 'on' ); ?> />
										</label>
									</div>
								</div>
							</div>
						</div>
						<div class="licenser-card__footer">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-addons' ) ); ?>" class="licenser-link-more">
								<?php esc_html_e( 'Enable / disable integration in Addons', 'licenser' ); ?> &rarr;
							</a>
							<button type="submit" class="licenser-btn licenser-btn--primary">
								<span class="licenser-inline-spinner" hidden></span>
								<?php esc_html_e( 'Save Settings', 'licenser' ); ?>
							</button>
						</div>
					</div>
					<?php
					/**
					 * Fires inside the WooCommerce settings tab immediately after the core WooCommerce Lite card.
					 *
					 * @param array $licenser_settings Saved core settings array.
					 */
					do_action( 'licenser_settings_woocommerce_after_card', $licenser_settings );
					?>
				</div>
			</form>

			<!-- Tab 3: Public API & Maintenance -->
			<div id="licenser-tab-api" class="licenser-tab-panel <?php echo 'api' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-api">
				<div class="licenser-card">
					<div class="licenser-card__header">
						<div class="licenser-card__title-group">
							<h2><?php esc_html_e( 'Public API & Maintenance', 'licenser' ); ?></h2>
							<p class="licenser-card__subtitle"><?php esc_html_e( 'REST endpoints for LicenserWP/client SDK and transient cache controls', 'licenser' ); ?></p>
						</div>
					</div>
					<div class="licenser-card__body">
						<div class="licenser-setting-row">
							<div class="licenser-setting-row__info">
								<label for="licenser-settings-api-base"><?php esc_html_e( 'Public API Base URL', 'licenser' ); ?></label>
								<p class="licenser-setting-row__desc"><?php esc_html_e( 'Base namespace serving product metadata, license verification, and ZIP downloads.', 'licenser' ); ?></p>
							</div>
							<div class="licenser-field">
								<div class="licenser-input-group">
									<input type="text" id="licenser-settings-api-base" class="licenser-mono" value="<?php echo esc_attr( $licenser_public_api_url ); ?>" readonly />
									<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn" data-licenser-copy-target="#licenser-settings-api-base">
										<span class="dashicons dashicons-admin-page"></span>
										<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
									</button>
								</div>
							</div>
						</div>

						<div class="licenser-setting-row">
							<div class="licenser-setting-row__info">
								<span class="licenser-setting-row__title"><?php esc_html_e( 'Public Routes', 'licenser' ); ?></span>
								<p class="licenser-setting-row__desc"><?php esc_html_e( 'Active v1 routes compatible with LicenserWP/client v2.0.1.', 'licenser' ); ?></p>
							</div>
							<div class="licenser-item-list">
								<div class="licenser-meta-row">
									<div class="licenser-meta-row__main">
										<span class="licenser-badge licenser-badge--post">POST</span>
										<code class="licenser-mono">/public/products/{uuid}</code>
									</div>
									<span class="licenser-addon-card__meta"><?php esc_html_e( 'Update check', 'licenser' ); ?></span>
								</div>
								<div class="licenser-meta-row">
									<div class="licenser-meta-row__main">
										<span class="licenser-badge licenser-badge--post">POST</span>
										<code class="licenser-mono">/public/license/{uuid}/{check|activate|deactivate}</code>
									</div>
									<span class="licenser-addon-card__meta"><?php esc_html_e( 'License lifecycle', 'licenser' ); ?></span>
								</div>
								<div class="licenser-meta-row">
									<div class="licenser-meta-row__main">
										<span class="licenser-badge licenser-badge--accent licenser-mono">GET</span>
										<code class="licenser-mono">/public/products/download/{uuid}?license_key=...</code>
									</div>
									<span class="licenser-addon-card__meta"><?php esc_html_e( 'Release ZIP', 'licenser' ); ?></span>
								</div>
							</div>
						</div>

						<div class="licenser-setting-row">
							<div class="licenser-setting-row__info">
								<span class="licenser-setting-row__title"><?php esc_html_e( 'Product Cache', 'licenser' ); ?></span>
								<p class="licenser-setting-row__desc"><?php esc_html_e( 'Product API responses are cached in WordPress transients for 1 hour and automatically flushed on product updates.', 'licenser' ); ?></p>
							</div>
							<div class="licenser-inline-callout__actions">
								<button type="button" class="licenser-btn licenser-btn--secondary licenser-clear-cache-btn">
									<span class="dashicons dashicons-update"></span>
									<?php esc_html_e( 'Clear Product Cache', 'licenser' ); ?>
								</button>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator&tab=diagnostics' ) ); ?>" class="licenser-btn licenser-btn--ghost">
									<?php esc_html_e( 'Open API Tester', 'licenser' ); ?> &rarr;
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
			/**
			 * Fires after core Settings tab panels to allow Pro/extensions to render custom tab panels.
			 *
			 * @param string $licenser_active_tab Active tab slug.
			 * @param array  $licenser_settings   Saved core settings array.
			 */
			do_action( 'licenser_settings_tab_panels', $licenser_active_tab, $licenser_settings );
			?>
		</div>
	</div>
</div>
