<?php

namespace Licenser\Addons\WooLite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\License_Meta;
use Licenser\Models\Product;

/**
 * Customer License Manager shortcode renderer (`[licenser_wc_manager]`).
 */
class Shortcode {

	/**
	 * Render the logged-in customer's WooCommerce licenses portal.
	 *
	 * @return string Buffered HTML markup.
	 */
	public function output() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
			return '';
		}

		// Ensure portal stylesheet and JS are loaded whether used in My Account or standalone pages.
		Order_Handler::enqueue_portal_assets();

		$order_ids = wc_get_orders(
			[
				'limit'       => 100,
				'customer_id' => $user_id,
				'return'      => 'ids',
				'status'      => [ 'wc-processing', 'wc-completed' ],
			]
		);

		$license_model = License::instance();
		$raw_licenses  = ! empty( $order_ids ) ? $license_model->get_all(
			[
				'number'      => -1,
				'offset'      => 0,
				'orderby'     => 'id',
				'order'       => 'DESC',
				'count_total' => false,
				'source'      => [ 'wc', 'woocommerce' ],
				'source_id'   => (array) $order_ids,
			]
		) : [];

		$allow_deactivation = ( 'on' === licenser_get_option( 'allow_customer_domain_deactivation' ) );
		$hydrated_items     = [];

		if ( ! empty( $raw_licenses ) && is_array( $raw_licenses ) ) {
			global $wpdb;
			$product_model = Product::instance();
			$meta_model    = License_Meta::instance();

			foreach ( $raw_licenses as $license_row ) {
				$license_id = absint( $license_row->id );
				$product_id = absint( $license_row->product_id );
				$package_id = absint( $license_row->package_id );

				// Resolve product directly via product_id so licenses without a package_id still render.
				$product = $product_id > 0 ? $product_model->get(
					$product_id,
					[
						'inc_stable_release' => true,
						'inc_releases'       => false,
						'inc_packages'       => false,
					]
				) : false;

				if ( ! $product ) {
					continue;
				}

				$package_label = '';
				if ( $package_id > 0 ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$package_label = (string) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT label FROM {$wpdb->licenser_license_packages} WHERE id = %d LIMIT 1",
							$package_id
						)
					);
				}

				$domains = $license_model->get_domains(
					[
						'license_id' => $license_id,
						'number'     => -1,
					]
				);
				$domains = is_array( $domains ) ? $domains : [];

				$active_domains = 0;
				foreach ( $domains as $d ) {
					if ( isset( $d->status ) && 1 === (int) $d->status ) {
						++$active_domains;
					}
				}
				$active_domains = max( 0, (int) apply_filters( 'licenser_active_domain_count', $active_domains, $license_row, 'portal', [] ) );

				$order_id  = absint( $license_row->source_id );
				$order_url = '';
				if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
					$order_obj = wc_get_order( $order_id );
					if ( $order_obj && method_exists( $order_obj, 'get_view_order_url' ) ) {
						$order_url = $order_obj->get_view_order_url();
					}
				}

				$hydrated_items[] = [
					'license'        => $license_row,
					'product'        => $product,
					'package_label'  => $package_label,
					'computed'       => $license_model->get_computed_status( $license_row ),
					'domains'        => $domains,
					'active_domains' => $active_domains,
					'versions'       => $meta_model->get_installed_versions_for_license( $license_id ),
					'order_id'       => $order_id,
					'order_url'      => $order_url,
				];
			}
		}

		ob_start();
		?>
		<div class="licenser-portal">
			<div class="licenser-portal__header">
				<div>
					<h2 class="licenser-portal__title"><?php esc_html_e( 'License Manager', 'licenser' ); ?></h2>
					<p class="licenser-portal__subtitle"><?php esc_html_e( 'Copy license keys, download the latest releases, and manage activated sites.', 'licenser' ); ?></p>
				</div>
				<?php if ( ! empty( $hydrated_items ) ) : ?>
					<span class="licenser-portal__count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: Total customer licenses. */
								_n( '%d License', '%d Licenses', count( $hydrated_items ), 'licenser' ),
								count( $hydrated_items )
							)
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $hydrated_items ) ) : ?>
				<div class="licenser-portal__list">
					<?php
					foreach ( $hydrated_items as $item ) :
						$license        = $item['license'];
						$product        = $item['product'];
						$license_id     = absint( $license->id );
						$license_key    = sanitize_text_field( (string) $license->license_key );
						$domain_limit   = absint( $license->domain_limit );
						$is_lifetime    = ( 1 === (int) $license->is_lifetime );
						$computed       = (string) $item['computed'];
						$can_download   = in_array( $computed, [ 'active', 'expiring_soon' ], true ) && ! empty( $product->uuid );
						$download_link  = $can_download ? licenser_product_download_url( (string) $product->uuid, $license_key ) : '';
						$stable_version = ( ! empty( $product->stable_release ) && ! empty( $product->stable_release->version ) ) ? (string) $product->stable_release->version : '';

						$status_labels = [
							'active'        => __( 'Active', 'licenser' ),
							'expiring_soon' => __( 'Expiring Soon', 'licenser' ),
							'expired'       => __( 'Expired', 'licenser' ),
							'inactive'      => __( 'Inactive', 'licenser' ),
						];
						$status_label  = isset( $status_labels[ $computed ] ) ? $status_labels[ $computed ] : __( 'Inactive', 'licenser' );

						if ( $is_lifetime ) {
							$expire_display = __( 'Lifetime', 'licenser' );
						} elseif ( ! empty( $license->end_date ) && false !== strtotime( (string) $license->end_date ) ) {
							$expire_display = licenser_date( 'M j, Y', strtotime( (string) $license->end_date ) );
						} else {
							$expire_display = __( 'N/A', 'licenser' );
						}

						$initial = strtoupper( substr( trim( (string) $product->name ), 0, 1 ) );
						?>
						<article class="licenser-portal-card" data-license-id="<?php echo esc_attr( (string) $license_id ); ?>">
							<div class="licenser-portal-card__top">
								<div class="licenser-portal-card__brand">
									<?php if ( ! empty( $product->icon_url ) ) : ?>
										<img class="licenser-portal-card__icon" src="<?php echo esc_url( (string) $product->icon_url ); ?>" alt="<?php echo esc_attr( (string) $product->name ); ?>" loading="lazy" />
									<?php else : ?>
										<span class="licenser-portal-card__icon licenser-portal-card__icon--monogram" aria-hidden="true"><?php echo esc_html( '' !== $initial ? $initial : 'L' ); ?></span>
									<?php endif; ?>
									<div class="licenser-portal-card__identity">
										<h3 class="licenser-portal-card__name"><?php echo esc_html( (string) $product->name ); ?></h3>
										<div class="licenser-portal-card__tags">
											<?php if ( ! empty( $item['package_label'] ) ) : ?>
												<span class="licenser-portal-pill"><?php echo esc_html( $item['package_label'] ); ?></span>
											<?php endif; ?>
											<?php if ( $stable_version ) : ?>
												<span class="licenser-portal-pill">v<?php echo esc_html( $stable_version ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</div>
								<span class="licenser-portal-status licenser-portal-status--<?php echo esc_attr( $computed ); ?>">
									<span class="licenser-portal-status__dot"></span>
									<?php echo esc_html( $status_label ); ?>
								</span>
							</div>

							<div class="licenser-portal-card__keybar">
								<div class="licenser-portal-card__keylabel"><?php esc_html_e( 'License Key', 'licenser' ); ?></div>
								<div class="licenser-portal-card__keybox">
									<code class="licenser-portal-card__key"><?php echo esc_html( $license_key ); ?></code>
									<button type="button" class="licenser-portal-btn licenser-portal-btn--ghost licenser-portal-copy" data-copy="<?php echo esc_attr( $license_key ); ?>">
										<?php esc_html_e( 'Copy', 'licenser' ); ?>
									</button>
								</div>
							</div>

							<div class="licenser-portal-card__meta">
								<div class="licenser-portal-meta-item">
									<span class="licenser-portal-meta-item__label"><?php esc_html_e( 'Expires', 'licenser' ); ?></span>
									<span class="licenser-portal-meta-item__value"><?php echo esc_html( $expire_display ); ?></span>
								</div>
								<div class="licenser-portal-meta-item">
									<span class="licenser-portal-meta-item__label"><?php esc_html_e( 'Activations', 'licenser' ); ?></span>
									<span class="licenser-portal-meta-item__value">
										<span class="licenser-portal-active-count"><?php echo esc_html( (string) $item['active_domains'] ); ?></span>
										/
										<?php echo 0 === $domain_limit ? esc_html__( 'Unlimited', 'licenser' ) : esc_html( (string) $domain_limit ); ?>
									</span>
								</div>
								<?php if ( ! empty( $item['order_id'] ) ) : ?>
									<div class="licenser-portal-meta-item">
										<span class="licenser-portal-meta-item__label"><?php esc_html_e( 'Order', 'licenser' ); ?></span>
										<span class="licenser-portal-meta-item__value">
											<?php if ( ! empty( $item['order_url'] ) ) : ?>
												<a href="<?php echo esc_url( $item['order_url'] ); ?>">#<?php echo esc_html( (string) $item['order_id'] ); ?></a>
											<?php else : ?>
												#<?php echo esc_html( (string) $item['order_id'] ); ?>
											<?php endif; ?>
										</span>
									</div>
								<?php endif; ?>
							</div>

							<div class="licenser-portal-card__actions">
								<button type="button" class="licenser-portal-btn licenser-portal-btn--secondary licenser-portal-toggle-sites" aria-expanded="false">
									<?php
									printf(
										/* translators: %d: Number of active sites. */
										esc_html__( 'Manage Sites (%d)', 'licenser' ),
										absint( $item['active_domains'] )
									);
									?>
								</button>

								<?php if ( $download_link ) : ?>
									<a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $download_link ); ?>" class="licenser-portal-btn licenser-portal-btn--primary">
										<?php
										if ( $stable_version ) {
											/* translators: %s: Latest release version. */
											echo esc_html( sprintf( __( 'Download v%s', 'licenser' ), $stable_version ) );
										} else {
											esc_html_e( 'Download Latest', 'licenser' );
										}
										?>
									</a>
								<?php else : ?>
									<span class="licenser-portal-note">
										<?php
										echo 'expired' === $computed
											? esc_html__( 'Renew license to download updates', 'licenser' )
											: esc_html__( 'Download unavailable while inactive', 'licenser' );
										?>
									</span>
								<?php endif; ?>
								<?php
								/**
								 * Fires inside a customer portal license card actions bar (e.g. Renew / Upgrade buttons).
								 *
								 * @param object $license License record.
								 * @param object $product Product record.
								 * @param array  $item    Hydrated license card context.
								 */
								do_action( 'licenser_portal_license_actions', $license, $product, $item );
								?>
							</div>

							<!-- Collapsible Activated Domains Panel -->
							<div class="licenser-portal-sites" hidden>
								<div class="licenser-portal-sites__notice" hidden></div>
								<?php if ( ! empty( $item['domains'] ) ) : ?>
									<ul class="licenser-portal-sites__list">
										<?php
										foreach ( $item['domains'] as $domain ) :
											$did       = absint( $domain->id );
											$url       = sanitize_text_field( (string) $domain->domain );
											$href      = licenser_format_domain_url( $url );
											$is_active = ( isset( $domain->status ) && 1 === (int) $domain->status );
											$inst_ver  = isset( $item['versions'][ $did ] ) ? $item['versions'][ $did ] : '';
											?>
											<li class="licenser-portal-site-row" data-domain-id="<?php echo esc_attr( (string) $did ); ?>">
												<div class="licenser-portal-site-row__info">
													<span class="licenser-portal-status licenser-portal-status--<?php echo esc_attr( $is_active ? 'active' : 'inactive' ); ?>">
														<span class="licenser-portal-status__dot"></span>
														<?php echo $is_active ? esc_html__( 'Active', 'licenser' ) : esc_html__( 'Inactive', 'licenser' ); ?>
													</span>
													<span class="licenser-portal-site-row__domain">
														<?php echo esc_html( $url ); ?>
														<?php if ( $href ) : ?>
															<a href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Visit site', 'licenser' ); ?>">&#8599;</a>
														<?php endif; ?>
													</span>
													<?php if ( $inst_ver ) : ?>
														<span class="licenser-portal-pill">v<?php echo esc_html( $inst_ver ); ?></span>
													<?php endif; ?>
													<?php
													/**
													 * Fires inside a customer portal domain row info section (e.g. Staging Exempt pill).
													 *
													 * @param object $domain  Domain row object.
													 * @param object $license Parent license record.
													 */
													do_action( 'licenser_portal_domain_badges', $domain, $license );
													?>
												</div>

												<?php if ( $allow_deactivation && $is_active ) : ?>
													<div class="licenser-portal-site-row__actions">
														<button type="button" class="licenser-portal-btn licenser-portal-btn--danger-ghost licenser-portal-deactivate-init">
															<?php esc_html_e( 'Deactivate', 'licenser' ); ?>
														</button>
														<div class="licenser-portal-confirm-inline" hidden>
															<button type="button" class="licenser-portal-btn licenser-portal-btn--danger licenser-portal-deactivate-confirm" data-domain-id="<?php echo esc_attr( (string) $did ); ?>">
																<?php esc_html_e( 'Confirm', 'licenser' ); ?>
															</button>
															<button type="button" class="licenser-portal-btn licenser-portal-btn--ghost licenser-portal-deactivate-cancel">
																<?php esc_html_e( 'Cancel', 'licenser' ); ?>
															</button>
														</div>
													</div>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<p class="licenser-portal-sites__empty"><?php esc_html_e( 'No sites currently activated for this license.', 'licenser' ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php
				$shop_page_url    = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/' );
				$product_page_url = apply_filters( 'licenser_wclite_products_page_url', $shop_page_url );
				?>
				<div class="licenser-portal-empty">
					<h3><?php esc_html_e( 'No licenses available yet', 'licenser' ); ?></h3>
					<p><?php esc_html_e( 'Licenses generated from your completed orders will appear here with instant download links and site management.', 'licenser' ); ?></p>
					<?php if ( $product_page_url ) : ?>
						<a class="licenser-portal-btn licenser-portal-btn--primary" href="<?php echo esc_url( $product_page_url ); ?>">
							<?php esc_html_e( 'Browse Products', 'licenser' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
