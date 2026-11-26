<?php
/**
 * Admin licenses list template.
 *
 * @package Licenser
 */

use Licenser\Models\License;
use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter query parameters.
$licenser_status_filter = isset( $_GET['license_status'] ) ? sanitize_key( wp_unslash( $_GET['license_status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_product_filter = isset( $_GET['product_id'] ) ? absint( wp_unslash( $_GET['product_id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_bulk_msg = isset( $_GET['licenser_msg'] ) ? sanitize_key( wp_unslash( $_GET['licenser_msg'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_bulk_done = isset( $_GET['licenser_done'] ) ? absint( wp_unslash( $_GET['licenser_done'] ) ) : 0;

$licenser_stats        = License::instance()->get_dashboard_stats();
$licenser_all_products = Product::instance()->get_all(
	[
		'number'  => -1,
		'status'  => 'all',
		'columns' => 'id, name, product_type',
		'orderby' => 'name',
		'order'   => 'ASC',
	]
);
$licenser_all_products = is_array( $licenser_all_products ) ? $licenser_all_products : [];

$licenser_table = new Licenser\Admin\LicensesListTable();
$licenser_table->prepare_items();
$licenser_has_items   = ! empty( $licenser_table->items );
$licenser_has_filters = ( '' !== $licenser_status_filter || $licenser_product_filter > 0 || '' !== $licenser_search_query );
$licenser_base_url    = admin_url( 'admin.php?page=licenser-licenses' );

$licenser_export_url = wp_nonce_url(
	add_query_arg(
		[
			'action'         => 'licenser_export_licenses',
			'product_id'     => $licenser_product_filter > 0 ? $licenser_product_filter : false,
			'license_status' => '' !== $licenser_status_filter ? $licenser_status_filter : false,
			's'              => '' !== $licenser_search_query ? $licenser_search_query : false,
		],
		admin_url( 'admin-post.php' )
	),
	'licenser_export_licenses'
);
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons dashicons-tickets-alt"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php esc_html_e( 'Licenses', 'licenser' ); ?></h1>
					<span class="licenser-count-pill"><?php echo esc_html( number_format_i18n( $licenser_stats['total'] ) ); ?></span>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<a href="<?php echo esc_url( $licenser_export_url ); ?>" class="licenser-btn licenser-btn--secondary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export CSV', 'licenser' ); ?>
				</a>
				<button type="button" class="licenser-btn licenser-btn--secondary licenser-open-bulk-modal">
					<span class="dashicons dashicons-randomize"></span>
					<?php esc_html_e( 'Bulk Generate', 'licenser' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="licenser-btn licenser-btn--primary">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Issue License', 'licenser' ); ?>
				</a>
			</div>
		</div>

		<div class="licenser-main">
			<div class="licenser-notices">
				<?php if ( $licenser_bulk_msg && $licenser_bulk_done > 0 ) : ?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							if ( 'bulk_activate' === $licenser_bulk_msg ) {
								/* translators: %d: Number of activated licenses. */
								echo esc_html( sprintf( _n( '%d license activated.', '%d licenses activated.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							} elseif ( 'bulk_deactivate' === $licenser_bulk_msg ) {
								/* translators: %d: Number of deactivated licenses. */
								echo esc_html( sprintf( _n( '%d license deactivated.', '%d licenses deactivated.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							} elseif ( 'bulk_delete' === $licenser_bulk_msg ) {
								/* translators: %d: Number of deleted licenses. */
								echo esc_html( sprintf( _n( '%d license deleted.', '%d licenses deleted.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							}
							?>
						</p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Status Filter Pills & Product/Search Toolbar -->
			<div class="licenser-filter-bar">
				<div class="licenser-pills" role="tablist">
					<?php
					$licenser_tab_base = $licenser_product_filter > 0 ? add_query_arg( 'product_id', $licenser_product_filter, $licenser_base_url ) : $licenser_base_url;
					?>
					<a href="<?php echo esc_url( $licenser_tab_base ); ?>" class="licenser-pill <?php echo '' === $licenser_status_filter ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'All', 'licenser' ); ?>
						<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_stats['total'] ) ); ?></span>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'license_status', 'active', $licenser_tab_base ) ); ?>" class="licenser-pill <?php echo 'active' === $licenser_status_filter ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'Active', 'licenser' ); ?>
						<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_stats['active'] ) ); ?></span>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'license_status', 'expiring_soon', $licenser_tab_base ) ); ?>" class="licenser-pill <?php echo 'expiring_soon' === $licenser_status_filter ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'Expiring Soon', 'licenser' ); ?>
						<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_stats['expiring_soon'] ) ); ?></span>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'license_status', 'expired', $licenser_tab_base ) ); ?>" class="licenser-pill <?php echo 'expired' === $licenser_status_filter ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'Expired', 'licenser' ); ?>
						<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_stats['expired'] ) ); ?></span>
					</a>
					<a href="<?php echo esc_url( add_query_arg( 'license_status', 'inactive', $licenser_tab_base ) ); ?>" class="licenser-pill <?php echo 'inactive' === $licenser_status_filter ? 'is-active' : ''; ?>">
						<?php esc_html_e( 'Inactive', 'licenser' ); ?>
						<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_stats['inactive'] ) ); ?></span>
					</a>
				</div>

				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="licenser-toolbar-filters">
					<input type="hidden" name="page" value="licenser-licenses" />
					<?php if ( '' !== $licenser_status_filter ) : ?>
						<input type="hidden" name="license_status" value="<?php echo esc_attr( $licenser_status_filter ); ?>" />
					<?php endif; ?>

					<label class="licenser-sr-only" for="licenser-filter-product"><?php esc_html_e( 'Filter by product', 'licenser' ); ?></label>
					<select name="product_id" id="licenser-filter-product">
						<option value="0"><?php esc_html_e( 'All Products', 'licenser' ); ?></option>
						<?php foreach ( $licenser_all_products as $licenser_p ) : ?>
							<option value="<?php echo esc_attr( (string) absint( $licenser_p->id ) ); ?>" <?php selected( $licenser_product_filter, absint( $licenser_p->id ) ); ?>>
								<?php echo esc_html( (string) $licenser_p->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<label class="licenser-sr-only" for="licenser-license-search"><?php esc_html_e( 'Search licenses', 'licenser' ); ?></label>
					<input type="search" id="licenser-license-search" name="s" value="<?php echo esc_attr( $licenser_search_query ); ?>" placeholder="<?php esc_attr_e( 'Search key, order ID, or domain...', 'licenser' ); ?>" />

					<button type="submit" class="licenser-btn licenser-btn--secondary"><?php esc_html_e( 'Filter', 'licenser' ); ?></button>
					<?php if ( $licenser_has_filters ) : ?>
						<a href="<?php echo esc_url( $licenser_base_url ); ?>" class="licenser-btn licenser-btn--ghost"><?php esc_html_e( 'Reset', 'licenser' ); ?></a>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( $licenser_has_items ) : ?>
				<div class="licenser-table-card">
					<form action="<?php echo esc_url( $licenser_base_url ); ?>" method="post">
						<?php $licenser_table->display(); ?>
					</form>
				</div>
			<?php else : ?>
				<div class="licenser-empty-state">
					<div class="licenser-empty-state__icon">
						<span class="dashicons dashicons-tickets-alt"></span>
					</div>
					<?php if ( $licenser_has_filters ) : ?>
						<h2><?php esc_html_e( 'No licenses match your filters', 'licenser' ); ?></h2>
						<p><?php esc_html_e( 'Clear your active search, product, or status filters to see all issued license keys.', 'licenser' ); ?></p>
						<a href="<?php echo esc_url( $licenser_base_url ); ?>" class="licenser-btn licenser-btn--secondary"><?php esc_html_e( 'Clear Filters', 'licenser' ); ?></a>
					<?php else : ?>
						<h2><?php esc_html_e( 'No licenses issued yet', 'licenser' ); ?></h2>
						<p><?php esc_html_e( 'Issue an individual license key, bulk-generate a batch for campaigns, or sell automatically via WooCommerce.', 'licenser' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="licenser-btn licenser-btn--primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Issue First License', 'licenser' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Bulk License Generator Modal -->
		<div id="licenser-bulk-modal" class="licenser-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="licenser-bulk-modal-title">
			<div class="licenser-modal licenser-modal--lg">
				<div class="licenser-modal__header">
					<h2 id="licenser-bulk-modal-title"><?php esc_html_e( 'Bulk Generate License Keys', 'licenser' ); ?></h2>
					<button type="button" class="licenser-icon-btn licenser-modal-close" aria-label="<?php esc_attr_e( 'Close', 'licenser' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<form id="licenser-bulk-form">
					<div class="licenser-modal__body">
						<p><?php esc_html_e( 'Generate up to 100 keys in a single batch for agency packs, giveaways, or partner promotions.', 'licenser' ); ?></p>

						<div class="licenser-form-grid licenser-form-grid--2">
							<div class="licenser-field">
								<label for="licenser-bulk-product"><?php esc_html_e( 'Product', 'licenser' ); ?></label>
								<select id="licenser-bulk-product" required>
									<option value=""><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
									<?php foreach ( $licenser_all_products as $licenser_p ) : ?>
										<option value="<?php echo esc_attr( (string) absint( $licenser_p->id ) ); ?>"><?php echo esc_html( (string) $licenser_p->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="licenser-field">
								<label for="licenser-bulk-package"><?php esc_html_e( 'Package (Optional)', 'licenser' ); ?></label>
								<select id="licenser-bulk-package" disabled>
									<option value=""><?php esc_html_e( 'Select Package', 'licenser' ); ?></option>
								</select>
							</div>
						</div>

						<div class="licenser-form-grid licenser-form-grid--3">
							<div class="licenser-field">
								<label for="licenser-bulk-qty"><?php esc_html_e( 'Quantity (1–100)', 'licenser' ); ?></label>
								<input type="number" id="licenser-bulk-qty" min="1" max="100" value="10" required />
							</div>
							<div class="licenser-field">
								<label for="licenser-bulk-limit"><?php esc_html_e( 'Domain Limit Override', 'licenser' ); ?></label>
								<input type="number" id="licenser-bulk-limit" min="0" placeholder="<?php esc_attr_e( 'From package', 'licenser' ); ?>" />
							</div>
							<div class="licenser-field">
								<label for="licenser-bulk-status"><?php esc_html_e( 'Initial Status', 'licenser' ); ?></label>
								<select id="licenser-bulk-status">
									<option value="1"><?php esc_html_e( 'Active', 'licenser' ); ?></option>
									<option value="0"><?php esc_html_e( 'Inactive', 'licenser' ); ?></option>
								</select>
							</div>
						</div>

						<div class="licenser-form-grid licenser-form-grid--2">
							<div class="licenser-field">
								<label for="licenser-bulk-end-date"><?php esc_html_e( 'Expiry Override (Optional)', 'licenser' ); ?></label>
								<input type="datetime-local" id="licenser-bulk-end-date" />
							</div>
							<div class="licenser-field">
								<label for="licenser-bulk-source"><?php esc_html_e( 'Source Tag', 'licenser' ); ?></label>
								<input type="text" id="licenser-bulk-source" value="bulk" placeholder="bulk" />
							</div>
						</div>

						<div class="licenser-field">
							<label class="licenser-switch">
								<input type="checkbox" id="licenser-bulk-lifetime" value="1" />
								<span><?php esc_html_e( 'Issue as Lifetime licenses', 'licenser' ); ?></span>
							</label>
						</div>

						<div class="licenser-bulk-results" hidden>
							<div class="licenser-card__header">
								<h3>
									<?php esc_html_e( 'Generated Keys', 'licenser' ); ?>
									(<span class="licenser-bulk-count">0</span>)
								</h3>
								<div style="display: flex; gap: 6px;">
									<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-copy-btn licenser-bulk-copy-all" data-licenser-copy="">
										<span class="dashicons dashicons-admin-page"></span>
										<span class="licenser-copy-label"><?php esc_html_e( 'Copy All', 'licenser' ); ?></span>
									</button>
									<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-bulk-download-csv">
										<span class="dashicons dashicons-download"></span>
										<?php esc_html_e( 'Download CSV', 'licenser' ); ?>
									</button>
								</div>
							</div>
							<pre class="licenser-bulk-results__list"></pre>
						</div>
					</div>
					<div class="licenser-modal__footer">
						<button type="button" class="licenser-btn licenser-btn--secondary licenser-modal-close"><?php esc_html_e( 'Close', 'licenser' ); ?></button>
						<button type="submit" class="licenser-btn licenser-btn--primary">
							<span class="dashicons dashicons-randomize"></span>
							<?php esc_html_e( 'Generate Keys', 'licenser' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
