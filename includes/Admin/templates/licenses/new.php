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

$product_model = Product::instance();
$license_model = License::instance();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin view parameters.
$license_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'new';

$license = $license_id > 0 ? $license_model->get( $license_id ) : false;
if ( ! $license ) {
	$license = (object) $license_model->default_fields;
}

$products = $product_model->get_all(
	[
		'status'       => 'active',
		'number'       => -1,
		'inc_packages' => false,
		'columns'      => 'id, name, product_type',
	]
);
$products = is_array( $products ) ? $products : [];

$selected_product_type = '';
foreach ( $products as $prod ) {
	if ( (int) $prod->id === (int) $license->product_id ) {
		$selected_product_type = $prod->product_type;
		break;
	}
}

$get_domains = $license_id > 0 ? $license_model->get_domains(
	[
		'license_id' => $license_id,
		'number'     => -1,
	]
) : [];
$get_domains = is_array( $get_domains ) ? $get_domains : [];

$meta_data = $license_id > 0 ? License_Meta::instance()->get_all( $license_id ) : [];
$meta_data = is_array( $meta_data ) ? $meta_data : [];

$end_date_input = '';
if ( ! empty( $license->end_date ) ) {
	$end_ts = strtotime( (string) $license->end_date );
	if ( false !== $end_ts ) {
		$end_date_input = gmdate( 'Y-m-d\TH:i', $end_ts );
	}
}

$submit_button_label = ( 'edit' === $current_action && $license_id > 0 )
	? __( 'Update License', 'licenser' )
	: __( 'Add License', 'licenser' );
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<?php if ( $license_id > 0 ) : ?>
					<h2><?php esc_html_e( 'Edit License', 'licenser' ); ?></h2>
				<?php else : ?>
					<h2><?php esc_html_e( 'Add New License', 'licenser' ); ?></h2>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New License', 'licenser' ); ?></a>
			</div>
		</div>

		<div class="licenser-content">
			<div class="d-flex">
				<div class="licenser-sidenav">
					<ul>
						<li><a href="#license-section"><?php esc_html_e( 'License', 'licenser' ); ?></a></li>
						<li><a href="#active-domains"><?php esc_html_e( 'Active Domains', 'licenser' ); ?></a></li>
						<li><a href="#meta-data"><?php esc_html_e( 'Meta Data', 'licenser' ); ?></a></li>
					</ul>
				</div>

				<div class="licenser-navcontent w-100">
					<form action="" method="post" id="license-add-form">
						<div class="lmwppt-inner-card" id="license-section">
							<div class="lmfwppt-form-section" id="product-information">
								<h2><?php esc_html_e( 'License Information', 'licenser' ); ?></h2>

								<div class="lmfwppt-form-field">
									<label for="license_key"><?php esc_html_e( 'License Key', 'licenser' ); ?></label>
									<div class="lmfwppt-file-field">
										<input type="text" name="license_key" id="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'License Key', 'licenser' ); ?>" value="<?php echo esc_attr( $license->license_key ); ?>" readonly required />
										<button class="button" type="button" id="generate_key">
											<span class="generate-key-label"><?php esc_html_e( 'Generate Key', 'licenser' ); ?></span>
											<span class="spinner key-spinner"></span>
										</button>
									</div>
								</div>

								<div class="lmfwppt-form-field">
									<label for="license_status"><?php esc_html_e( 'Status', 'licenser' ); ?></label>
									<select name="status" id="license_status">
										<option value="1" <?php selected( (int) $license->status, 1 ); ?>><?php esc_html_e( 'Active', 'licenser' ); ?></option>
										<option value="0" <?php selected( (int) $license->status, 0 ); ?>><?php esc_html_e( 'Inactive', 'licenser' ); ?></option>
									</select>
								</div>

								<div class="lmfwppt-form-field">
									<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
									<select name="product_type" id="product_type">
										<option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
										<?php foreach ( $product_model->get_types() as $key => $value ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_product_type, $key ); ?>><?php echo esc_html( $value ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="lmfwppt-form-field lmfwppt_theme_products">
									<label for="product_list"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
									<select name="product_id" class="products_list" id="product_list">
										<option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
										<?php foreach ( $products as $product ) : ?>
											<option data-product_type="<?php echo esc_attr( $product->product_type ); ?>" value="<?php echo esc_attr( (string) $product->id ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt' ); ?>" <?php selected( (int) $license->product_id, (int) $product->id ); ?>><?php echo esc_html( $product->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="lmfwppt-form-field lmfwppt_license_package" id="lmfwppt_license_package">
									<label for="lmfwppt_package_list"><?php esc_html_e( 'Select Package', 'licenser' ); ?></label>
									<select name="package_id" id="lmfwppt_package_list" data-pack_value="<?php echo esc_attr( (string) $license->package_id ); ?>">
										<option value="<?php echo esc_attr( (string) $license->package_id ); ?>" class="blank"><?php esc_html_e( 'Select Package', 'licenser' ); ?></option>
									</select>
								</div>

								<div class="lmfwppt-form-field">
									<div class="d-flex">
										<label for="end_date" class="me-1"><?php esc_html_e( 'License End Date', 'licenser' ); ?></label>
										<label>
											<input type="checkbox" name="is_lifetime" value="1" <?php checked( (int) $license->is_lifetime, 1 ); ?>>
											<?php esc_html_e( 'Lifetime', 'licenser' ); ?>
										</label>
									</div>
									<input type="datetime-local" name="end_date" id="end_date" class="regular-text licenser-date-time" value="<?php echo esc_attr( $end_date_input ); ?>">
								</div>

								<div class="lmfwppt-form-field">
									<label for="domain_limit"><?php esc_html_e( 'License Domain Limit', 'licenser' ); ?></label>
									<input type="number" min="0" name="domain_limit" id="domain_limit" class="regular-text" placeholder="<?php esc_attr_e( 'Enter Domain Limit', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $license->domain_limit ); ?>">
									<div><?php esc_html_e( 'Leave empty or 0 for unlimited domains.', 'licenser' ); ?></div>
								</div>

								<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
									<div class="lwp-col-half">
										<label for="source"><?php esc_html_e( 'Source', 'licenser' ); ?></label>
										<input type="text" name="source" id="source" class="regular-text" value="<?php echo esc_attr( (string) $license->source ); ?>">
									</div>

									<div class="lwp-col-half">
										<label for="source_id"><?php esc_html_e( 'Source ID', 'licenser' ); ?></label>
										<input type="number" name="source_id" id="source_id" class="regular-text" value="<?php echo esc_attr( (string) $license->source_id ); ?>">
									</div>
								</div>
							</div>
						</div>

						<div class="lmwppt-inner-card" id="active-domains">
							<div class="lmfwppt-form-section" id="license-information">
								<h2><?php esc_html_e( 'Active Domains', 'licenser' ); ?></h2>
								<div id="lmfwppt_domains_fields">
									<?php if ( ! empty( $get_domains ) ) : ?>
										<?php
										foreach ( $get_domains as $domain_item ) :
											$domain_id        = isset( $domain_item->id ) ? absint( $domain_item->id ) : 0;
											$url              = isset( $domain_item->domain ) ? $domain_item->domain : '';
											$domain_href      = licenser_format_domain_url( $url );
											$is_active_domain = ( isset( $domain_item->status ) && 1 === (int) $domain_item->status );
											$status_label     = $is_active_domain ? __( 'Active', 'licenser' ) : __( 'Inactive', 'licenser' );
											$status_tag_class = $is_active_domain ? 'lwp-tag-success' : '';
											?>
											<div class="postbox">
												<h4>
													<span class="lwp-tag ms-1 me-1 <?php echo esc_attr( $status_tag_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
													<?php echo esc_html( $url . ' - (id:' . $domain_id . ')' ); ?>
													<div class="lwp-postbox-actions">
														<?php if ( $domain_href ) : ?>
															<a class="lwp-action-item lwp-tooltip" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $domain_href ); ?>" data-title="<?php esc_attr_e( 'Visit Domain', 'licenser' ); ?>">
																<span class="dashicons dashicons-external"></span>
															</a>
														<?php endif; ?>
														<a href="javascript:void(0);" class="lwp-action-item lwp-tooltip lwp-delete-domain" data-title="<?php esc_attr_e( 'Delete Domain', 'licenser' ); ?>" data-id="<?php echo esc_attr( (string) $domain_id ); ?>">
															<span class="dashicons dashicons-trash"></span>
														</a>
													</div>
												</h4>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="licenser-not-found"><?php esc_html_e( 'No Domains', 'licenser' ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<div class="lmwppt-inner-card lmfwppt-buttons card-shameless">
							<?php if ( $license_id > 0 ) : ?>
								<input class="lmfwppt_edit_id" type="hidden" name="id" value="<?php echo esc_attr( (string) $license_id ); ?>">
							<?php endif; ?>

							<div class="submit_btn_area">
								<?php submit_button( $submit_button_label, 'primary', 'add_license' ); ?>
								<span class="spinner"></span>
							</div>
							<div class="lmfwppt-notices"></div>
						</div>
					</form>

					<div id="meta-data">
						<div class="lmwppt-inner-card">
							<div class="lmfwppt-form-section" id="licenser-metadata">
								<h2><?php esc_html_e( 'Meta Data', 'licenser' ); ?></h2>
								<div>
									<?php if ( ! empty( $meta_data ) ) : ?>
										<?php
										foreach ( $meta_data as $meta ) :
											$meta_id = isset( $meta->id ) ? absint( $meta->id ) : 0;
											?>
											<div class="postbox">
												<h4 class="d-flex">
													<div class="ms-1"><?php echo esc_html( $meta->meta_key ); ?></div>
													<div class="ms-1"><?php echo esc_html( $meta->meta_value ); ?></div>
													<div class="lwp-postbox-actions">
														<a href="javascript:void(0);" class="lwp-action-item lwp-tooltip lwp-delete-meta" data-title="<?php esc_attr_e( 'Delete Meta', 'licenser' ); ?>" data-id="<?php echo esc_attr( (string) $meta_id ); ?>">
															<span class="dashicons dashicons-trash"></span>
														</a>
													</div>
												</h4>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="licenser-not-found"><?php esc_html_e( 'No Meta Data', 'licenser' ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
