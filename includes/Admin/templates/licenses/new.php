<?php
/**
 * Admin license create/edit template.
 *
 * @package Licenser
 */

use Licenser\Models\License;
use Licenser\Models\License_Meta;
use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_product_model = Product::instance();
$licenser_license_model = License::instance();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin view parameters.
$licenser_license_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'new';

$licenser_license = $licenser_license_id > 0 ? $licenser_license_model->get( $licenser_license_id ) : false;
if ( ! $licenser_license ) {
	$licenser_license = (object) $licenser_license_model->default_fields;
}

$licenser_products = $licenser_product_model->get_all(
	[
		'status'       => 'all',
		'number'       => -1,
		'inc_packages' => false,
		'columns'      => 'id, name, product_type',
	]
);
$licenser_products = is_array( $licenser_products ) ? $licenser_products : [];

$licenser_selected_product_type = '';
foreach ( $licenser_products as $licenser_prod ) {
	if ( (int) $licenser_prod->id === (int) $licenser_license->product_id ) {
		$licenser_selected_product_type = (string) $licenser_prod->product_type;
		break;
	}
}

$licenser_domains = $licenser_license_id > 0 ? $licenser_license_model->get_domains(
	[
		'license_id' => $licenser_license_id,
		'number'     => -1,
	]
) : [];
$licenser_domains = is_array( $licenser_domains ) ? $licenser_domains : [];

$licenser_active_domains_count = 0;
foreach ( $licenser_domains as $licenser_d ) {
	if ( isset( $licenser_d->status ) && 1 === (int) $licenser_d->status ) {
		++$licenser_active_domains_count;
	}
}

$licenser_installed_versions = $licenser_license_id > 0 ? License_Meta::instance()->get_installed_versions_for_license( $licenser_license_id ) : [];
$licenser_meta_data          = $licenser_license_id > 0 ? License_Meta::instance()->get_all( $licenser_license_id ) : [];
$licenser_meta_data          = is_array( $licenser_meta_data ) ? $licenser_meta_data : [];

$licenser_end_date_input = '';
$licenser_expiry_note    = __( 'Lifetime access', 'licenser' );
if ( 1 !== (int) $licenser_license->is_lifetime && ! empty( $licenser_license->end_date ) ) {
	$licenser_end_ts = strtotime( (string) $licenser_license->end_date );
	if ( false !== $licenser_end_ts ) {
		$licenser_end_date_input = gmdate( 'Y-m-d\TH:i', $licenser_end_ts );
		$licenser_days_diff      = (int) ceil( ( $licenser_end_ts - time() ) / DAY_IN_SECONDS );
		if ( $licenser_days_diff < 0 ) {
			/* translators: %d: Days ago. */
			$licenser_expiry_note = sprintf( __( 'Expired %d days ago', 'licenser' ), abs( $licenser_days_diff ) );
		} else {
			/* translators: %d: Days remaining. */
			$licenser_expiry_note = sprintf( __( '%d days remaining', 'licenser' ), $licenser_days_diff );
		}
	}
} elseif ( 1 !== (int) $licenser_license->is_lifetime ) {
	$licenser_expiry_note = __( 'No expiration date set', 'licenser' );
}

$licenser_is_edit             = ( 'edit' === $licenser_current_action && $licenser_license_id > 0 );
$licenser_submit_button_label = $licenser_is_edit ? __( 'Save Changes', 'licenser' ) : __( 'Issue License', 'licenser' );

$licenser_computed   = $licenser_is_edit ? $licenser_license_model->get_computed_status( $licenser_license ) : 'active';
$licenser_status_map = [
	'active'        => [ 'licenser-badge--active', __( 'Active', 'licenser' ) ],
	'expiring_soon' => [ 'licenser-badge--warning', __( 'Expiring Soon', 'licenser' ) ],
	'expired'       => [ 'licenser-badge--expired', __( 'Expired', 'licenser' ) ],
	'inactive'      => [ 'licenser-badge--inactive', __( 'Inactive', 'licenser' ) ],
];
$licenser_b_class    = isset( $licenser_status_map[ $licenser_computed ] ) ? $licenser_status_map[ $licenser_computed ][0] : 'licenser-badge--active';
$licenser_b_label    = isset( $licenser_status_map[ $licenser_computed ] ) ? $licenser_status_map[ $licenser_computed ][1] : __( 'Active', 'licenser' );

// Linked WooCommerce order lookup (HPOS-safe).
$licenser_wc_order     = null;
$licenser_wc_order_url = '';
$licenser_source_lower = strtolower( trim( (string) $licenser_license->source ) );
$licenser_source_id    = absint( $licenser_license->source_id );
if ( in_array( $licenser_source_lower, [ 'wc', 'woocommerce' ], true ) && $licenser_source_id > 0 ) {
	$licenser_wc_order_url = admin_url( 'post.php?post=' . $licenser_source_id . '&action=edit' );
	if ( function_exists( 'wc_get_order' ) ) {
		$licenser_wc_order = wc_get_order( $licenser_source_id );
		if ( $licenser_wc_order && method_exists( $licenser_wc_order, 'get_edit_order_url' ) ) {
			$licenser_wc_order_url = $licenser_wc_order->get_edit_order_url();
		}
	}
}

$licenser_domain_limit = absint( $licenser_license->domain_limit );
$licenser_meter_pct    = $licenser_domain_limit > 0 ? min( 100, (int) round( ( $licenser_active_domains_count / $licenser_domain_limit ) * 100 ) ) : 0;
$licenser_meter_class  = 'licenser-meter__fill';
if ( $licenser_domain_limit > 0 && $licenser_active_domains_count >= $licenser_domain_limit ) {
	$licenser_meter_class .= ' licenser-meter__fill--full';
} elseif ( $licenser_meter_pct >= 80 ) {
	$licenser_meter_class .= ' licenser-meter__fill--warn';
}
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>" class="licenser-back-link">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php esc_html_e( 'Licenses', 'licenser' ); ?>
				</a>
				<div class="licenser-topbar__heading">
					<h1>
						<?php
						if ( $licenser_is_edit ) {
							/* translators: %d: License ID. */
							echo esc_html( sprintf( __( 'License #%d', 'licenser' ), $licenser_license_id ) );
						} else {
							esc_html_e( 'Issue New License', 'licenser' );
						}
						?>
					</h1>
					<?php if ( $licenser_is_edit ) : ?>
						<span class="licenser-badge <?php echo esc_attr( $licenser_b_class ); ?>">
							<span class="licenser-badge__dot"></span>
							<?php echo esc_html( $licenser_b_label ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<?php if ( $licenser_is_edit ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="licenser-btn licenser-btn--secondary">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'New License', 'licenser' ); ?>
					</a>
				<?php endif; ?>
				<button type="button" class="licenser-btn licenser-btn--primary" data-licenser-submit-form="#license-add-form">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html( $licenser_submit_button_label ); ?>
				</button>
			</div>
		</div>

		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>

			<div class="licenser-split-layout">
				<!-- Left Main Column -->
				<div>
					<form action="" method="post" id="license-add-form">
						<?php if ( $licenser_license_id > 0 ) : ?>
							<input class="licenser-edit-id" type="hidden" name="id" value="<?php echo esc_attr( (string) $licenser_license_id ); ?>" />
						<?php endif; ?>

						<!-- License Details Card -->
						<div class="licenser-card" id="license-section">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'License Information', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Configure key credentials, product mapping, expiration, and domain limits', 'licenser' ); ?></p>
								</div>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-field">
									<label for="license_key"><?php esc_html_e( 'License Key', 'licenser' ); ?></label>
									<div class="licenser-input-group">
										<input type="text" name="license_key" id="license_key" class="licenser-mono" placeholder="<?php esc_attr_e( 'Generated license key', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_license->license_key ); ?>" readonly required />
										<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn licenser-copy-key-btn" data-licenser-copy-target="#license_key">
											<span class="dashicons dashicons-admin-page"></span>
											<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
										</button>
										<button class="licenser-btn licenser-btn--secondary" type="button" id="generate_key">
											<span class="dashicons dashicons-randomize"></span>
											<?php esc_html_e( 'Generate', 'licenser' ); ?>
										</button>
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="license_status"><?php esc_html_e( 'Status', 'licenser' ); ?></label>
										<select name="status" id="license_status">
											<option value="1" <?php selected( (int) $licenser_license->status, 1 ); ?>><?php esc_html_e( 'Active', 'licenser' ); ?></option>
											<option value="0" <?php selected( (int) $licenser_license->status, 0 ); ?>><?php esc_html_e( 'Inactive (Suspended)', 'licenser' ); ?></option>
										</select>
									</div>
									<div class="licenser-field">
										<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
										<select name="product_type" id="product_type">
											<option value=""><?php esc_html_e( 'All Product Types', 'licenser' ); ?></option>
											<?php foreach ( $licenser_product_model->get_types() as $licenser_type_key => $licenser_type_label ) : ?>
												<option value="<?php echo esc_attr( $licenser_type_key ); ?>" <?php selected( $licenser_selected_product_type, $licenser_type_key ); ?>><?php echo esc_html( $licenser_type_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="product_list"><?php esc_html_e( 'Product', 'licenser' ); ?></label>
										<select name="product_id" class="licenser-products-select" id="product_list" required>
											<option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
											<?php foreach ( $licenser_products as $licenser_product ) : ?>
												<option data-product_type="<?php echo esc_attr( (string) $licenser_product->product_type ); ?>" value="<?php echo esc_attr( (string) absint( $licenser_product->id ) ); ?>" class="<?php echo esc_attr( $licenser_product->product_type . '-opt' ); ?>" <?php selected( (int) $licenser_license->product_id, (int) $licenser_product->id ); ?>><?php echo esc_html( (string) $licenser_product->name ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="licenser-field">
										<label for="licenser_package_list"><?php esc_html_e( 'License Package', 'licenser' ); ?></label>
										<select name="package_id" id="licenser_package_list" data-pack_value="<?php echo esc_attr( (string) $licenser_license->package_id ); ?>">
											<option value="<?php echo esc_attr( (string) $licenser_license->package_id ); ?>" class="blank"><?php esc_html_e( 'Select Package', 'licenser' ); ?></option>
										</select>
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
											<label for="end_date"><?php esc_html_e( 'Expiration Date', 'licenser' ); ?></label>
											<label class="licenser-switch">
												<input type="checkbox" name="is_lifetime" id="is_lifetime" value="1" <?php checked( (int) $licenser_license->is_lifetime, 1 ); ?> />
												<span><?php esc_html_e( 'Lifetime', 'licenser' ); ?></span>
											</label>
										</div>
										<input type="datetime-local" name="end_date" id="end_date" value="<?php echo esc_attr( $licenser_end_date_input ); ?>" />
										<div class="licenser-preset-row">
											<button type="button" class="licenser-preset-btn" data-days="30">+30d</button>
											<button type="button" class="licenser-preset-btn" data-days="90">+90d</button>
											<button type="button" class="licenser-preset-btn" data-days="365">+1 Year</button>
											<button type="button" class="licenser-preset-btn" data-days="730">+2 Years</button>
										</div>
									</div>

									<div class="licenser-field">
										<label for="domain_limit"><?php esc_html_e( 'Domain Activation Limit', 'licenser' ); ?></label>
										<input type="number" min="0" name="domain_limit" id="domain_limit" placeholder="0" value="<?php echo esc_attr( (string) $licenser_license->domain_limit ); ?>" />
										<p class="licenser-help"><?php esc_html_e( 'Set to 0 or leave empty for unlimited domains.', 'licenser' ); ?></p>
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="source"><?php esc_html_e( 'Source', 'licenser' ); ?></label>
										<input type="text" name="source" id="source" placeholder="<?php esc_attr_e( 'e.g. wc, manual, bulk', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_license->source ); ?>" />
									</div>
									<div class="licenser-field">
										<label for="source_id"><?php esc_html_e( 'Source Order / Reference ID', 'licenser' ); ?></label>
										<div class="licenser-input-group">
											<input type="number" name="source_id" id="source_id" value="<?php echo esc_attr( (string) $licenser_license->source_id ); ?>" />
											<?php if ( $licenser_wc_order_url ) : ?>
												<a href="<?php echo esc_url( $licenser_wc_order_url ); ?>" class="licenser-btn licenser-btn--secondary" target="_blank" rel="noopener noreferrer">
													<?php
													/* translators: %d: WooCommerce order ID. */
													echo esc_html( sprintf( __( 'Order #%d', 'licenser' ), $licenser_source_id ) );
													?>
													<span class="dashicons dashicons-external"></span>
												</a>
											<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>

					<?php if ( $licenser_is_edit ) : ?>
						<!-- Active Domains Card -->
						<div class="licenser-card" id="active-domains">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Activated Domains', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Add, toggle, or revoke site activations connected to this license key', 'licenser' ); ?></p>
								</div>
								<span class="licenser-badge licenser-badge--accent licenser-tabular">
									<span class="licenser-active-domain-count"><?php echo esc_html( (string) $licenser_active_domains_count ); ?></span>
									/
									<?php echo 0 === $licenser_domain_limit ? esc_html__( 'Unlimited', 'licenser' ) : esc_html( (string) $licenser_domain_limit ); ?>
								</span>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-inline-add">
									<label class="licenser-sr-only" for="licenser-new-domain-input"><?php esc_html_e( 'Add domain', 'licenser' ); ?></label>
									<input type="text" id="licenser-new-domain-input" placeholder="<?php esc_attr_e( 'Add site domain (e.g. example.com)...', 'licenser' ); ?>" />
									<button type="button" class="licenser-btn licenser-btn--secondary" id="licenser-add-domain-btn" data-license-id="<?php echo esc_attr( (string) $licenser_license_id ); ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Add Domain', 'licenser' ); ?>
									</button>
								</div>

								<div id="licenser-domains-list" class="licenser-item-list">
									<?php
									foreach ( $licenser_domains as $licenser_domain_item ) :
										$licenser_domain_id     = isset( $licenser_domain_item->id ) ? absint( $licenser_domain_item->id ) : 0;
										$licenser_domain_url    = isset( $licenser_domain_item->domain ) ? (string) $licenser_domain_item->domain : '';
										$licenser_domain_href   = licenser_format_domain_url( $licenser_domain_url );
										$licenser_domain_active = ( isset( $licenser_domain_item->status ) && 1 === (int) $licenser_domain_item->status );
										$licenser_domain_ver    = isset( $licenser_installed_versions[ $licenser_domain_id ] ) ? $licenser_installed_versions[ $licenser_domain_id ] : '';
										$licenser_domain_ts     = ! empty( $licenser_domain_item->dated ) ? strtotime( (string) $licenser_domain_item->dated ) : false;
										?>
										<div class="licenser-domain-row" data-status="<?php echo esc_attr( $licenser_domain_active ? '1' : '0' ); ?>">
											<div class="licenser-domain-row__main">
												<span class="licenser-badge licenser-domain-status-badge <?php echo esc_attr( $licenser_domain_active ? 'licenser-badge--active' : 'licenser-badge--inactive' ); ?>">
													<span class="licenser-badge__dot"></span>
													<span class="licenser-badge-label"><?php echo $licenser_domain_active ? esc_html__( 'Active', 'licenser' ) : esc_html__( 'Inactive', 'licenser' ); ?></span>
												</span>
												<span class="licenser-domain-row__url"><?php echo esc_html( $licenser_domain_url ); ?></span>
												<?php if ( $licenser_domain_ver ) : ?>
													<span class="licenser-badge licenser-badge--accent licenser-tabular">v<?php echo esc_html( $licenser_domain_ver ); ?></span>
												<?php endif; ?>
												<?php if ( $licenser_domain_ts ) : ?>
													<span class="licenser-muted licenser-tabular" style="font-size: 12px;"><?php echo esc_html( licenser_date( 'M d, Y', $licenser_domain_ts ) ); ?></span>
												<?php endif; ?>
											</div>
											<div class="licenser-domain-row__actions">
												<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-toggle-domain-btn" data-id="<?php echo esc_attr( (string) $licenser_domain_id ); ?>" data-domain="<?php echo esc_attr( $licenser_domain_url ); ?>" data-status="<?php echo esc_attr( $licenser_domain_active ? '1' : '0' ); ?>">
													<?php echo $licenser_domain_active ? esc_html__( 'Deactivate', 'licenser' ) : esc_html__( 'Activate', 'licenser' ); ?>
												</button>
												<?php if ( $licenser_domain_href ) : ?>
													<a class="licenser-icon-btn" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $licenser_domain_href ); ?>" title="<?php esc_attr_e( 'Visit Domain', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Visit Domain', 'licenser' ); ?>">
														<span class="dashicons dashicons-external"></span>
													</a>
												<?php endif; ?>
												<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-domain-btn" data-id="<?php echo esc_attr( (string) $licenser_domain_id ); ?>" data-domain="<?php echo esc_attr( $licenser_domain_url ); ?>" title="<?php esc_attr_e( 'Remove Domain', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Remove Domain', 'licenser' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="licenser-empty-state licenser-empty-state--inline licenser-domains-empty" <?php echo ! empty( $licenser_domains ) ? 'hidden' : ''; ?>>
									<h3><?php esc_html_e( 'No domains activated yet', 'licenser' ); ?></h3>
									<p><?php esc_html_e( 'Sites will appear here automatically upon activation, or you can pre-authorize a domain above.', 'licenser' ); ?></p>
								</div>
							</div>
						</div>

						<!-- Custom Meta Data Card -->
						<div class="licenser-card" id="meta-data">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Custom Meta Data', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Key-value records attached to this license (including tracked installed versions)', 'licenser' ); ?></p>
								</div>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-inline-add">
									<label class="licenser-sr-only" for="licenser-new-meta-key"><?php esc_html_e( 'Meta Key', 'licenser' ); ?></label>
									<input type="text" id="licenser-new-meta-key" placeholder="<?php esc_attr_e( 'Meta key (e.g. customer_note)', 'licenser' ); ?>" />
									<label class="licenser-sr-only" for="licenser-new-meta-value"><?php esc_html_e( 'Meta Value', 'licenser' ); ?></label>
									<input type="text" id="licenser-new-meta-value" placeholder="<?php esc_attr_e( 'Meta value', 'licenser' ); ?>" />
									<button type="button" class="licenser-btn licenser-btn--secondary" id="licenser-add-meta-btn" data-license-id="<?php echo esc_attr( (string) $licenser_license_id ); ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Add Meta', 'licenser' ); ?>
									</button>
								</div>

								<div id="licenser-meta-list" class="licenser-item-list">
									<?php
									foreach ( $licenser_meta_data as $licenser_meta ) :
										$licenser_meta_id = isset( $licenser_meta->id ) ? absint( $licenser_meta->id ) : 0;
										?>
										<div class="licenser-meta-row" data-id="<?php echo esc_attr( (string) $licenser_meta_id ); ?>">
											<div class="licenser-meta-row__main">
												<code class="licenser-code-pill"><?php echo esc_html( (string) $licenser_meta->meta_key ); ?></code>
												<span><?php echo esc_html( (string) $licenser_meta->meta_value ); ?></span>
											</div>
											<div class="licenser-meta-row__actions">
												<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-meta-btn" data-id="<?php echo esc_attr( (string) $licenser_meta_id ); ?>" title="<?php esc_attr_e( 'Delete Meta', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Delete Meta', 'licenser' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="licenser-empty-state licenser-empty-state--inline licenser-meta-empty" <?php echo ! empty( $licenser_meta_data ) ? 'hidden' : ''; ?>>
									<h3><?php esc_html_e( 'No custom metadata', 'licenser' ); ?></h3>
									<p><?php esc_html_e( 'Store additional notes or integration flags as key-value pairs above.', 'licenser' ); ?></p>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Right Sticky Summary Panel -->
				<aside class="licenser-sticky-panel">
					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'License Snapshot', 'licenser' ); ?></h2>
							</div>
							<span class="licenser-badge <?php echo esc_attr( $licenser_b_class ); ?>">
								<span class="licenser-badge__dot"></span>
								<?php echo esc_html( $licenser_b_label ); ?>
							</span>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-snapshot-list">
								<div class="licenser-snapshot-item">
									<span class="licenser-snapshot-item__label"><?php esc_html_e( 'Validity', 'licenser' ); ?></span>
									<span class="licenser-snapshot-item__value licenser-tabular"><?php echo esc_html( $licenser_expiry_note ); ?></span>
								</div>

								<?php if ( $licenser_is_edit ) : ?>
									<div>
										<div class="licenser-snapshot-item" style="margin-bottom: 6px;">
											<span class="licenser-snapshot-item__label"><?php esc_html_e( 'Site Activations', 'licenser' ); ?></span>
											<span class="licenser-snapshot-item__value licenser-tabular">
												<span class="licenser-active-domain-count"><?php echo esc_html( (string) $licenser_active_domains_count ); ?></span>
												/
												<?php echo 0 === $licenser_domain_limit ? esc_html__( 'Unlimited', 'licenser' ) : esc_html( (string) $licenser_domain_limit ); ?>
											</span>
										</div>
										<?php if ( $licenser_domain_limit > 0 ) : ?>
											<div class="licenser-meter licenser-meter--wide">
												<div class="<?php echo esc_attr( $licenser_meter_class ); ?>" style="width: <?php echo esc_attr( (string) $licenser_meter_pct ); ?>%;"></div>
											</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $licenser_license->dated ) ) : ?>
									<div class="licenser-snapshot-item">
										<span class="licenser-snapshot-item__label"><?php esc_html_e( 'Issued On', 'licenser' ); ?></span>
										<span class="licenser-snapshot-item__value licenser-tabular"><?php echo esc_html( licenser_date( 'M d, Y', strtotime( (string) $licenser_license->dated ) ) ); ?></span>
									</div>
								<?php endif; ?>

								<?php if ( $licenser_wc_order ) : ?>
									<div class="licenser-snapshot-item">
										<span class="licenser-snapshot-item__label"><?php esc_html_e( 'WooCommerce Order', 'licenser' ); ?></span>
										<a class="licenser-source-link licenser-tabular" href="<?php echo esc_url( $licenser_wc_order_url ); ?>">
											#<?php echo esc_html( (string) $licenser_wc_order->get_order_number() ); ?>
											<span class="dashicons dashicons-external"></span>
										</a>
									</div>
									<div class="licenser-snapshot-item">
										<span class="licenser-snapshot-item__label"><?php esc_html_e( 'Order Status', 'licenser' ); ?></span>
										<span class="licenser-badge licenser-badge--accent"><?php echo esc_html( wc_get_order_status_name( $licenser_wc_order->get_status() ) ); ?></span>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="licenser-card__footer" style="flex-direction: column; align-items: stretch;">
							<button type="button" class="licenser-btn licenser-btn--primary" data-licenser-submit-form="#license-add-form">
								<span class="dashicons dashicons-saved"></span>
								<?php echo esc_html( $licenser_submit_button_label ); ?>
							</button>
							<?php if ( $licenser_is_edit ) : ?>
								<button type="button" class="licenser-btn licenser-btn--danger licenser-delete-license" data-id="<?php echo esc_attr( (string) $licenser_license_id ); ?>" data-redirect="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
									<span class="dashicons dashicons-trash"></span>
									<?php esc_html_e( 'Delete License', 'licenser' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>
				</aside>
			</div>
		</div>
	</div>
</div>

<!-- Dynamic Domain Row Template -->
<template id="licenser-tmpl-domain-row">
	<div class="licenser-domain-row" data-status="1">
		<div class="licenser-domain-row__main">
			<span class="licenser-badge licenser-domain-status-badge licenser-badge--active">
				<span class="licenser-badge__dot"></span>
				<span class="licenser-badge-label"><?php esc_html_e( 'Active', 'licenser' ); ?></span>
			</span>
			<span class="licenser-domain-row__url">{{{data.domain}}}</span>
			<span class="licenser-muted licenser-tabular" style="font-size: 12px;">{{{data.dated}}}</span>
		</div>
		<div class="licenser-domain-row__actions">
			<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-toggle-domain-btn" data-id="{{{data.id}}}" data-domain="{{{data.domain}}}" data-status="1"><?php esc_html_e( 'Deactivate', 'licenser' ); ?></button>
			<a class="licenser-icon-btn" target="_blank" rel="noopener noreferrer" href="{{{data.href}}}" title="<?php esc_attr_e( 'Visit Domain', 'licenser' ); ?>">
				<span class="dashicons dashicons-external"></span>
			</a>
			<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-domain-btn" data-id="{{{data.id}}}" data-domain="{{{data.domain}}}" title="<?php esc_attr_e( 'Remove Domain', 'licenser' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
	</div>
</template>

<!-- Dynamic Meta Row Template -->
<template id="licenser-tmpl-meta-row">
	<div class="licenser-meta-row" data-id="{{{data.id}}}">
		<div class="licenser-meta-row__main">
			<code class="licenser-code-pill">{{{data.meta_key}}}</code>
			<span>{{{data.meta_value}}}</span>
		</div>
		<div class="licenser-meta-row__actions">
			<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-meta-btn" data-id="{{{data.id}}}" title="<?php esc_attr_e( 'Delete Meta', 'licenser' ); ?>">
				<span class="dashicons dashicons-trash"></span>
			</button>
		</div>
	</div>
</template>
