<?php

namespace Licenser\Addons\Wc_Lite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;

/**
 * Customer License Manager shortcode renderer.
 */
class Shortcode {

	/**
	 * Render the logged-in customer's WooCommerce licenses table.
	 *
	 * @return string Buffered HTML markup.
	 */
	public function output() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
			return '';
		}

		$order_ids = wc_get_orders(
			[
				'limit'       => 100,
				'customer_id' => $user_id,
				'return'      => 'ids',
				'status'      => [ 'wc-processing', 'wc-completed' ],
			]
		);

		$get_licenses = ! empty( $order_ids ) ? License::instance()->get_all(
			[
				'number'      => -1,
				'offset'      => 0,
				'orderby'     => 'id',
				'order'       => 'DESC',
				'count_total' => true,
				'source'      => 'wc',
				'source_id'   => (array) $order_ids,
			]
		) : [];

		ob_start();

		printf( '<h4>%s</h4>', esc_html__( 'License Manager', 'licenser' ) );

		if ( ! empty( $get_licenses ) && is_array( $get_licenses ) ) :
			?>
			<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
				<thead>
					<tr>
						<th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e( 'Item', 'licenser' ); ?></span></th>
						<th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e( 'License Details', 'licenser' ); ?></span></th>
						<th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e( 'Actions', 'licenser' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php
					global $wpdb;

					foreach ( $get_licenses as $license_row ) :
						$license = (array) $license_row;

						$license_id   = isset( $license['id'] ) ? absint( $license['id'] ) : 0;
						$package_id   = isset( $license['package_id'] ) ? absint( $license['package_id'] ) : 0;
						$license_key  = isset( $license['license_key'] ) ? sanitize_text_field( $license['license_key'] ) : '';
						$expire_date  = isset( $license['end_date'] ) ? sanitize_text_field( $license['end_date'] ) : '';
						$is_lifetime  = isset( $license['is_lifetime'] ) ? (int) $license['is_lifetime'] : 0;
						$domain_limit = isset( $license['domain_limit'] ) ? absint( $license['domain_limit'] ) : 0;

						$expired_label = '';

						if ( 1 === $is_lifetime ) {
							$expire_display = __( 'Lifetime', 'licenser' );
						} else {
							$end_ts = $expire_date ? strtotime( $expire_date ) : false;
							if ( $end_ts ) {
								if ( $end_ts < time() ) {
									$expired_label = sprintf(
										'<span class="expired-label">(%s)</span>',
										esc_html__( 'Expired. Want to renew?', 'licenser' )
									);
								}
								$expire_display = licenser_date( 'j F Y', $end_ts );
							} else {
								$expire_display = __( 'N/A', 'licenser' );
							}
						}

						if ( ! $package_id ) {
							continue;
						}

						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$get_product = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT product.uuid, product.name, package.label, product.product_type
								FROM {$wpdb->licenser_license_packages} AS package
								INNER JOIN {$wpdb->licenser_products} AS product ON product.id = package.product_id
								WHERE package.id = %d",
								$package_id
							)
						);

						if ( ! $get_product ) {
							continue;
						}

						$product_type  = isset( $get_product->product_type ) ? $get_product->product_type : '';
						$product_name  = isset( $get_product->name ) ? $get_product->name : '';
						$pack_label    = isset( $get_product->label ) ? $get_product->label : '';
						$domain_label  = ( 0 === $domain_limit ) ? __( 'Unlimited', 'licenser' ) : (string) $domain_limit;
						$download_link = ! empty( $get_product->uuid ) ? licenser_product_download_url( $get_product->uuid, $license_key ) : '';

						$get_domains = License::instance()->get_domains(
							[
								'license_id' => $license_id,
								'number'     => -1,
							]
						);
						?>
						<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
							<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Item', 'licenser' ); ?>">
								<div class="license_product_name"><strong><?php echo esc_html( $product_name ); ?></strong></div>
								<div class="license_key">
									<code><?php echo esc_html( $license_key ); ?></code>
								</div>

								<div class="show_manage_activations_details">
									<a href="#"><?php esc_html_e( 'Manage Activations', 'licenser' ); ?></a>
								</div>

								<div class="manage-activations">
									<a href="#" class="activations-close-modal" title="<?php esc_attr_e( 'Close', 'licenser' ); ?>">&times;</a>
									<h5><?php esc_html_e( 'Manage License:', 'licenser' ); ?></h5>

									<table style="border-width: 1px;">
										<thead>
											<tr>
												<th><?php esc_html_e( 'Site URL', 'licenser' ); ?></th>
												<th><?php esc_html_e( 'Status', 'licenser' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php if ( ! empty( $get_domains ) && is_array( $get_domains ) ) : ?>
												<?php
												foreach ( $get_domains as $domain ) :
													$url           = isset( $domain->domain ) ? sanitize_text_field( $domain->domain ) : '';
													$domain_href   = licenser_format_domain_url( $url );
													$domain_status = ( isset( $domain->status ) && 1 === (int) $domain->status ) ? __( 'Active', 'licenser' ) : __( 'Inactive', 'licenser' );
													?>
													<tr>
														<td>
															<?php echo esc_html( $url ); ?>
															<?php if ( $domain_href ) : ?>
																<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $domain_href ); ?>">&#8599;</a>
															<?php endif; ?>
														</td>
														<td><?php echo esc_html( $domain_status ); ?></td>
													</tr>
												<?php endforeach; ?>
											<?php else : ?>
												<tr>
													<td colspan="2"><?php esc_html_e( 'No Domains', 'licenser' ); ?></td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
								<div class="am-overlay"></div>
							</td>

							<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'License Details', 'licenser' ); ?>">
								<div class="license_product_name"><strong><?php esc_html_e( 'Product Name', 'licenser' ); ?>:</strong> <?php echo esc_html( $product_name ); ?> (<?php echo esc_html( $pack_label ); ?>)</div>
								<div class="license_details">
									<strong><?php esc_html_e( 'Domain Limit', 'licenser' ); ?>:</strong> <?php echo esc_html( $domain_label ); ?><br>
									<strong><?php esc_html_e( 'Product Type', 'licenser' ); ?>:</strong> <?php echo esc_html( ucwords( $product_type ) ); ?><br>
									<strong><?php esc_html_e( 'Expires', 'licenser' ); ?>:</strong> <?php echo esc_html( $expire_display ); ?> <?php echo wp_kses_post( $expired_label ); ?>
								</div>
							</td>

							<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Actions', 'licenser' ); ?>">
								<?php if ( $download_link ) : ?>
									<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $download_link ); ?>" class="woocommerce-button button view"><?php esc_html_e( 'Download', 'licenser' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		else :
			$shop_page_url    = get_permalink( wc_get_page_id( 'shop' ) );
			$product_page_url = apply_filters( 'licenser_wclite_products_page_url', $shop_page_url );
			?>
			<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
				<a class="woocommerce-Button button" href="<?php echo esc_url( $product_page_url ); ?>"><?php esc_html_e( 'Browse products', 'licenser' ); ?></a>
				<?php esc_html_e( 'No licenses available yet.', 'licenser' ); ?>
			</div>
			<?php
		endif;

		return (string) ob_get_clean();
	}
}
