<?php
/**
 * Admin dashboard overview template.
 *
 * @package Licenser
 */

use Licenser\Controllers\Addons;
use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_product_model = Product::instance();
$licenser_license_model = License::instance();

$licenser_total_plugins = $licenser_product_model->count( [ 'product_type' => 'plugin' ] );
$licenser_total_themes  = $licenser_product_model->count( [ 'product_type' => 'theme' ] );
$licenser_stats         = $licenser_license_model->get_dashboard_stats();

$licenser_recent_licenses = $licenser_license_model->get_all(
	[
		'number'  => 8,
		'orderby' => 'id',
		'order'   => 'DESC',
	]
);
$licenser_recent_licenses = is_array( $licenser_recent_licenses ) ? $licenser_recent_licenses : [];

$licenser_recent_domains = $licenser_license_model->get_recent_domains( 8 );

// Batch hydrate product names, package labels, and active domain counts for recent licenses.
$licenser_products_map = [];
$licenser_all_products = $licenser_product_model->get_all(
	[
		'number'  => -1,
		'status'  => 'all',
		'columns' => 'id, name, product_type',
	]
);
foreach ( (array) $licenser_all_products as $licenser_p ) {
	if ( isset( $licenser_p->id ) ) {
		$licenser_products_map[ absint( $licenser_p->id ) ] = $licenser_p;
	}
}

$licenser_packages_map = [];
$licenser_all_packages = LicensePackage::instance()->get_all( [ 'number' => -1 ] );
foreach ( (array) $licenser_all_packages as $licenser_pkg ) {
	if ( isset( $licenser_pkg->id ) ) {
		$licenser_packages_map[ absint( $licenser_pkg->id ) ] = (string) $licenser_pkg->label;
	}
}

$licenser_recent_ids     = wp_list_pluck( $licenser_recent_licenses, 'id' );
$licenser_domain_counts  = $licenser_license_model->get_active_domain_counts( $licenser_recent_ids );
$licenser_addons         = Addons::instance();
$licenser_wc_enabled     = $licenser_addons->is_enabled( 'woocommerce' );
$licenser_wc_plugin      = $licenser_addons->is_dependency_active( 'woocommerce' );
$licenser_wc_active      = $licenser_wc_enabled && $licenser_wc_plugin;
$licenser_hpos_enabled   = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
$licenser_public_api_url = untrailingslashit( rest_url( 'licenser/v1/public' ) );
$licenser_inactive_total = $licenser_stats['expired'] + $licenser_stats['inactive'];
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons dashicons-tickets-alt"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php esc_html_e( 'Licenser Overview', 'licenser' ); ?></h1>
					<span class="licenser-count-pill">v<?php echo esc_html( LICENSER_VERSION ); ?></span>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator' ) ); ?>" class="licenser-btn licenser-btn--secondary">
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'SDK Studio', 'licenser' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-plugins&action=new' ) ); ?>" class="licenser-btn licenser-btn--secondary">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Add Product', 'licenser' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="licenser-btn licenser-btn--primary">
					<span class="dashicons dashicons-tickets-alt"></span>
					<?php esc_html_e( 'Issue License', 'licenser' ); ?>
				</a>
			</div>
		</div>

		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>

			<!-- 5-Card KPI Strip -->
			<div class="licenser-kpi-grid">
				<a class="licenser-kpi-card" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-plugins' ) ); ?>">
					<div class="licenser-kpi-card__top">
						<span class="licenser-kpi-card__label"><?php esc_html_e( 'Plugins', 'licenser' ); ?></span>
						<span class="licenser-kpi-card__icon licenser-kpi-card__icon--blue"><span class="dashicons dashicons-admin-plugins"></span></span>
					</div>
					<div class="licenser-kpi-card__value"><?php echo esc_html( number_format_i18n( $licenser_total_plugins ) ); ?></div>
					<div class="licenser-kpi-card__meta"><?php esc_html_e( 'Managed plugin products', 'licenser' ); ?></div>
				</a>

				<a class="licenser-kpi-card" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-themes' ) ); ?>">
					<div class="licenser-kpi-card__top">
						<span class="licenser-kpi-card__label"><?php esc_html_e( 'Themes', 'licenser' ); ?></span>
						<span class="licenser-kpi-card__icon licenser-kpi-card__icon--purple"><span class="dashicons dashicons-admin-appearance"></span></span>
					</div>
					<div class="licenser-kpi-card__value"><?php echo esc_html( number_format_i18n( $licenser_total_themes ) ); ?></div>
					<div class="licenser-kpi-card__meta"><?php esc_html_e( 'Managed theme products', 'licenser' ); ?></div>
				</a>

				<a class="licenser-kpi-card" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&license_status=active' ) ); ?>">
					<div class="licenser-kpi-card__top">
						<span class="licenser-kpi-card__label"><?php esc_html_e( 'Active Licenses', 'licenser' ); ?></span>
						<span class="licenser-kpi-card__icon licenser-kpi-card__icon--green"><span class="dashicons dashicons-yes-alt"></span></span>
					</div>
					<div class="licenser-kpi-card__value"><?php echo esc_html( number_format_i18n( $licenser_stats['active'] ) ); ?></div>
					<div class="licenser-kpi-card__meta">
						<?php
						if ( $licenser_stats['expiring_soon'] > 0 ) {
							/* translators: %d: Number of licenses expiring within 30 days. */
							echo esc_html( sprintf( __( '%d expiring within 30 days', 'licenser' ), $licenser_stats['expiring_soon'] ) );
						} else {
							/* translators: %d: Total issued licenses. */
							echo esc_html( sprintf( __( 'Of %d total issued keys', 'licenser' ), $licenser_stats['total'] ) );
						}
						?>
					</div>
				</a>

				<a class="licenser-kpi-card" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&license_status=expired' ) ); ?>">
					<div class="licenser-kpi-card__top">
						<span class="licenser-kpi-card__label"><?php esc_html_e( 'Expired & Inactive', 'licenser' ); ?></span>
						<span class="licenser-kpi-card__icon licenser-kpi-card__icon--amber"><span class="dashicons dashicons-clock"></span></span>
					</div>
					<div class="licenser-kpi-card__value"><?php echo esc_html( number_format_i18n( $licenser_inactive_total ) ); ?></div>
					<div class="licenser-kpi-card__meta">
						<?php
						/* translators: 1: Expired count, 2: Inactive count. */
						echo esc_html( sprintf( __( '%1$d expired • %2$d inactive', 'licenser' ), $licenser_stats['expired'], $licenser_stats['inactive'] ) );
						?>
					</div>
				</a>

				<a class="licenser-kpi-card" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
					<div class="licenser-kpi-card__top">
						<span class="licenser-kpi-card__label"><?php esc_html_e( 'Active Domains', 'licenser' ); ?></span>
						<span class="licenser-kpi-card__icon licenser-kpi-card__icon--teal"><span class="dashicons dashicons-admin-site-alt3"></span></span>
					</div>
					<div class="licenser-kpi-card__value"><?php echo esc_html( number_format_i18n( $licenser_stats['active_domains'] ) ); ?></div>
					<div class="licenser-kpi-card__meta"><?php esc_html_e( 'Connected customer sites', 'licenser' ); ?></div>
				</a>
			</div>

			<!-- 2-Column Bento Workspace -->
			<div class="licenser-bento-grid">
				<!-- Left Column: Recent Licenses & Recent Domain Activations -->
				<div>
					<!-- Recent Licenses -->
					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Recent Licenses', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Latest keys issued across manual and WooCommerce orders', 'licenser' ); ?></p>
							</div>
							<a class="licenser-link-more" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
								<?php esc_html_e( 'View all', 'licenser' ); ?> &rarr;
							</a>
						</div>
						<div class="licenser-card__body licenser-card__body--flush">
							<?php if ( ! empty( $licenser_recent_licenses ) ) : ?>
								<table class="licenser-mini-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'License Key', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Product', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Status', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Sites', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Issued', 'licenser' ); ?></th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ( $licenser_recent_licenses as $licenser_item ) :
											$licenser_lid        = absint( $licenser_item->id );
											$licenser_pid        = absint( $licenser_item->product_id );
											$licenser_pkg_id     = absint( $licenser_item->package_id );
											$licenser_prod_name  = isset( $licenser_products_map[ $licenser_pid ] ) ? $licenser_products_map[ $licenser_pid ]->name : __( 'Unknown Product', 'licenser' );
											$licenser_pkg_label  = isset( $licenser_packages_map[ $licenser_pkg_id ] ) ? $licenser_packages_map[ $licenser_pkg_id ] : '';
											$licenser_key_str    = (string) $licenser_item->license_key;
											$licenser_key_short  = strlen( $licenser_key_str ) > 14 ? substr( $licenser_key_str, 0, 8 ) . '...' . substr( $licenser_key_str, -4 ) : $licenser_key_str;
											$licenser_computed   = $licenser_license_model->get_computed_status( $licenser_item );
											$licenser_used_sites = isset( $licenser_domain_counts[ $licenser_lid ] ) ? $licenser_domain_counts[ $licenser_lid ] : 0;
											$licenser_site_limit = absint( $licenser_item->domain_limit );
											$licenser_limit_str  = 0 === $licenser_site_limit ? '&infin;' : (string) $licenser_site_limit;
											$licenser_ts         = ! empty( $licenser_item->dated ) ? strtotime( (string) $licenser_item->dated ) : false;

											$licenser_badge_map = [
												'active'   => [ 'licenser-badge--active', __( 'Active', 'licenser' ) ],
												'expiring_soon' => [ 'licenser-badge--warning', __( 'Expiring Soon', 'licenser' ) ],
												'expired'  => [ 'licenser-badge--expired', __( 'Expired', 'licenser' ) ],
												'inactive' => [ 'licenser-badge--inactive', __( 'Inactive', 'licenser' ) ],
											];
											$licenser_b_class   = isset( $licenser_badge_map[ $licenser_computed ] ) ? $licenser_badge_map[ $licenser_computed ][0] : 'licenser-badge--inactive';
											$licenser_b_label   = isset( $licenser_badge_map[ $licenser_computed ] ) ? $licenser_badge_map[ $licenser_computed ][1] : __( 'Inactive', 'licenser' );
											?>
											<tr>
												<td>
													<div class="licenser-key-inline">
														<code class="licenser-code-pill"><?php echo esc_html( $licenser_key_short ); ?></code>
														<button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( $licenser_key_str ); ?>" title="<?php esc_attr_e( 'Copy License Key', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Copy License Key', 'licenser' ); ?>">
															<span class="dashicons dashicons-admin-page"></span>
														</button>
													</div>
												</td>
												<td>
													<div class="licenser-cell-stack">
														<span class="licenser-cell-primary"><?php echo esc_html( $licenser_prod_name ); ?></span>
														<?php if ( $licenser_pkg_label ) : ?>
															<span class="licenser-cell-secondary"><?php echo esc_html( $licenser_pkg_label ); ?></span>
														<?php endif; ?>
													</div>
												</td>
												<td>
													<span class="licenser-badge <?php echo esc_attr( $licenser_b_class ); ?>">
														<span class="licenser-badge__dot"></span>
														<?php echo esc_html( $licenser_b_label ); ?>
													</span>
												</td>
												<td class="licenser-tabular">
													<?php echo esc_html( (string) $licenser_used_sites ); ?> / <?php echo wp_kses( $licenser_limit_str, [] ); ?>
												</td>
												<td class="licenser-tabular licenser-muted">
													<?php echo $licenser_ts ? esc_html( licenser_date( 'M j, Y', $licenser_ts ) ) : '&mdash;'; ?>
												</td>
												<td>
													<a class="licenser-icon-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . $licenser_lid ) ); ?>" title="<?php esc_attr_e( 'Edit License', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Edit License', 'licenser' ); ?>">
														<span class="dashicons dashicons-edit"></span>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else : ?>
								<div class="licenser-card__body">
									<div class="licenser-empty-state licenser-empty-state--inline">
										<h3><?php esc_html_e( 'No licenses issued yet', 'licenser' ); ?></h3>
										<p><?php esc_html_e( 'Issue your first license key manually or connect a WooCommerce product to generate keys automatically.', 'licenser' ); ?></p>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="licenser-btn licenser-btn--primary licenser-btn--sm"><?php esc_html_e( 'Issue First License', 'licenser' ); ?></a>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Recent Domain Activations -->
					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Recent Domain Activations', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Customer sites most recently connected to your update server', 'licenser' ); ?></p>
							</div>
							<a class="licenser-link-more" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
								<?php esc_html_e( 'Manage licenses', 'licenser' ); ?> &rarr;
							</a>
						</div>
						<div class="licenser-card__body licenser-card__body--flush">
							<?php if ( ! empty( $licenser_recent_domains ) ) : ?>
								<table class="licenser-mini-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Domain', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Product', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'Status', 'licenser' ); ?></th>
											<th><?php esc_html_e( 'License', 'licenser' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ( $licenser_recent_domains as $licenser_dom ) :
											$licenser_dom_url    = isset( $licenser_dom->domain ) ? (string) $licenser_dom->domain : '';
											$licenser_dom_href   = licenser_format_domain_url( $licenser_dom_url );
											$licenser_dom_active = isset( $licenser_dom->status ) && 1 === (int) $licenser_dom->status;
											$licenser_dom_ver    = ! empty( $licenser_dom->installed_version ) ? (string) $licenser_dom->installed_version : '';
											$licenser_dom_lid    = absint( $licenser_dom->license_id );
											?>
											<tr>
												<td>
													<div class="licenser-cell-stack">
														<span class="licenser-cell-primary">
															<?php echo esc_html( $licenser_dom_url ); ?>
															<?php if ( $licenser_dom_href ) : ?>
																<a href="<?php echo esc_url( $licenser_dom_href ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Open site', 'licenser' ); ?>"><span class="dashicons dashicons-external"></span></a>
															<?php endif; ?>
														</span>
														<?php if ( $licenser_dom_ver ) : ?>
															<span class="licenser-cell-secondary">
																<?php
																/* translators: %s: Installed plugin/theme version. */
																echo esc_html( sprintf( __( 'Installed v%s', 'licenser' ), $licenser_dom_ver ) );
																?>
															</span>
														<?php endif; ?>
													</div>
												</td>
												<td><?php echo esc_html( ! empty( $licenser_dom->product_name ) ? (string) $licenser_dom->product_name : __( 'Unknown', 'licenser' ) ); ?></td>
												<td>
													<span class="licenser-badge <?php echo esc_attr( $licenser_dom_active ? 'licenser-badge--active' : 'licenser-badge--inactive' ); ?>">
														<span class="licenser-badge__dot"></span>
														<?php echo $licenser_dom_active ? esc_html__( 'Active', 'licenser' ) : esc_html__( 'Inactive', 'licenser' ); ?>
													</span>
												</td>
												<td>
													<a class="licenser-row-link licenser-tabular" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . $licenser_dom_lid ) ); ?>">
														#<?php echo esc_html( (string) $licenser_dom_lid ); ?>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else : ?>
								<div class="licenser-card__body">
									<div class="licenser-empty-state licenser-empty-state--inline">
										<h3><?php esc_html_e( 'No active site domains yet', 'licenser' ); ?></h3>
										<p><?php esc_html_e( 'Once customers activate licenses from their WordPress sites, connected domains will appear here.', 'licenser' ); ?></p>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Right Column: Quick Actions & System / Setup Status -->
				<div>
					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Quick Actions', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Common workflows at one click', 'licenser' ); ?></p>
							</div>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-quick-grid">
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-plugins&action=new' ) ); ?>">
									<span class="dashicons dashicons-admin-plugins"></span>
									<span><?php esc_html_e( 'Add Plugin', 'licenser' ); ?></span>
								</a>
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-themes&action=new' ) ); ?>">
									<span class="dashicons dashicons-admin-appearance"></span>
									<span><?php esc_html_e( 'Add Theme', 'licenser' ); ?></span>
								</a>
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>">
									<span class="dashicons dashicons-tickets-alt"></span>
									<span><?php esc_html_e( 'Issue License', 'licenser' ); ?></span>
								</a>
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator' ) ); ?>">
									<span class="dashicons dashicons-editor-code"></span>
									<span><?php esc_html_e( 'SDK Studio', 'licenser' ); ?></span>
								</a>
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator&tab=diagnostics' ) ); ?>">
									<span class="dashicons dashicons-rest-api"></span>
									<span><?php esc_html_e( 'API Tester', 'licenser' ); ?></span>
								</a>
								<a class="licenser-quick-tile" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-settings' ) ); ?>">
									<span class="dashicons dashicons-admin-settings"></span>
									<span><?php esc_html_e( 'Settings', 'licenser' ); ?></span>
								</a>
							</div>
						</div>
					</div>

					<div class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Setup & Server Status', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Integration health & public API endpoint', 'licenser' ); ?></p>
							</div>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-status-list">
								<div class="licenser-status-row">
									<span class="licenser-status-row__label"><?php esc_html_e( 'Products Configured', 'licenser' ); ?></span>
									<?php $licenser_total_products = $licenser_total_plugins + $licenser_total_themes; ?>
									<span class="licenser-badge <?php echo esc_attr( $licenser_total_products > 0 ? 'licenser-badge--active' : 'licenser-badge--warning' ); ?>">
										<span class="licenser-badge__dot"></span>
										<?php
										/* translators: %d: Number of configured products. */
										echo esc_html( sprintf( __( '%d Ready', 'licenser' ), $licenser_total_products ) );
										?>
									</span>
								</div>

								<div class="licenser-status-row">
									<span class="licenser-status-row__label"><?php esc_html_e( 'WooCommerce Lite', 'licenser' ); ?></span>
									<?php if ( ! $licenser_wc_enabled ) : ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-addons' ) ); ?>" class="licenser-badge licenser-badge--inactive">
											<span class="licenser-badge__dot"></span>
											<?php esc_html_e( 'Disabled', 'licenser' ); ?>
										</a>
									<?php elseif ( $licenser_wc_plugin ) : ?>
										<span class="licenser-badge licenser-badge--active">
											<span class="licenser-badge__dot"></span>
											<?php esc_html_e( 'Active', 'licenser' ); ?>
										</span>
									<?php else : ?>
										<span class="licenser-badge licenser-badge--warning">
											<span class="licenser-badge__dot"></span>
											<?php esc_html_e( 'Requires WooCommerce', 'licenser' ); ?>
										</span>
									<?php endif; ?>
								</div>

								<?php if ( $licenser_wc_active ) : ?>
									<div class="licenser-status-row">
										<span class="licenser-status-row__label"><?php esc_html_e( 'WooCommerce HPOS', 'licenser' ); ?></span>
										<span class="licenser-badge <?php echo esc_attr( $licenser_hpos_enabled ? 'licenser-badge--active' : 'licenser-badge--accent' ); ?>">
											<?php echo $licenser_hpos_enabled ? esc_html__( 'HPOS Active', 'licenser' ) : esc_html__( 'Compatible (Legacy)', 'licenser' ); ?>
										</span>
									</div>
								<?php endif; ?>

								<div class="licenser-status-row">
									<span class="licenser-status-row__label"><?php esc_html_e( 'Expiring Within 30d', 'licenser' ); ?></span>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&license_status=expiring_soon' ) ); ?>" class="licenser-badge <?php echo esc_attr( $licenser_stats['expiring_soon'] > 0 ? 'licenser-badge--warning' : 'licenser-badge--inactive' ); ?>">
										<?php echo esc_html( number_format_i18n( $licenser_stats['expiring_soon'] ) ); ?>
									</a>
								</div>
							</div>

							<div class="licenser-field" style="margin-top: 16px;">
								<label for="licenser-dash-api-base"><?php esc_html_e( 'Public REST API Base URL', 'licenser' ); ?></label>
								<div class="licenser-input-group">
									<input type="text" id="licenser-dash-api-base" class="licenser-mono" value="<?php echo esc_attr( $licenser_public_api_url ); ?>" readonly />
									<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn" data-licenser-copy-target="#licenser-dash-api-base">
										<span class="dashicons dashicons-admin-page"></span>
										<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
									</button>
								</div>
							</div>
						</div>
						<div class="licenser-card__footer">
							<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-clear-cache-btn">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Clear Product Cache', 'licenser' ); ?>
							</button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-addons' ) ); ?>" class="licenser-link-more">
								<?php esc_html_e( 'View Addons', 'licenser' ); ?> &rarr;
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
