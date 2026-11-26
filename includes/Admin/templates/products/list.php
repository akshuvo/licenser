<?php
/**
 * Admin products list template.
 *
 * @package Licenser
 */

use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing and filter parameters.
$licenser_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';
$licenser_product_type = ( 'licenser-themes' === $licenser_current_page ) ? 'theme' : 'plugin';
$licenser_type_label   = ( 'theme' === $licenser_product_type ) ? __( 'Theme', 'licenser' ) : __( 'Plugin', 'licenser' );
$licenser_type_plural  = ( 'theme' === $licenser_product_type ) ? __( 'Themes', 'licenser' ) : __( 'Plugins', 'licenser' );
$licenser_type_icon    = ( 'theme' === $licenser_product_type ) ? 'dashicons-admin-appearance' : 'dashicons-admin-plugins';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_status_filter = isset( $_GET['product_status'] ) ? sanitize_key( wp_unslash( $_GET['product_status'] ) ) : 'all';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_bulk_msg = isset( $_GET['licenser_msg'] ) ? sanitize_key( wp_unslash( $_GET['licenser_msg'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_bulk_done = isset( $_GET['licenser_done'] ) ? absint( wp_unslash( $_GET['licenser_done'] ) ) : 0;

$licenser_product_model = Product::instance();
$licenser_count_all     = $licenser_product_model->count( [ 'product_type' => $licenser_product_type ] );
$licenser_count_active  = $licenser_product_model->count(
	[
		'product_type' => $licenser_product_type,
		'status'       => 'active',
	]
);
$licenser_count_draft   = $licenser_product_model->count(
	[
		'product_type' => $licenser_product_type,
		'status'       => 'draft',
	]
);

$licenser_table = new Licenser\Admin\ProductsListTable();
$licenser_table->prepare_items( $licenser_product_type );
$licenser_has_items  = ! empty( $licenser_table->items );
$licenser_is_filters = ( '' !== $licenser_search_query || 'all' !== $licenser_status_filter );
$licenser_base_url   = admin_url( 'admin.php?page=' . $licenser_current_page );
$licenser_add_url    = admin_url( 'admin.php?page=licenser-' . $licenser_product_type . 's&action=new' );
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons <?php echo esc_attr( $licenser_type_icon ); ?>"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php echo esc_html( $licenser_type_plural ); ?></h1>
					<span class="licenser-count-pill"><?php echo esc_html( number_format_i18n( $licenser_count_all ) ); ?></span>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<a href="<?php echo esc_url( $licenser_add_url ); ?>" class="licenser-btn licenser-btn--primary">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php
					/* translators: %s: Product Type label (Plugin or Theme). */
					echo esc_html( sprintf( __( 'Add %s', 'licenser' ), $licenser_type_label ) );
					?>
				</a>
			</div>
		</div>

		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite">
				<?php if ( $licenser_bulk_msg && $licenser_bulk_done > 0 ) : ?>
					<div class="notice notice-success is-dismissible inline">
						<p>
							<?php
							if ( 'bulk_delete' === $licenser_bulk_msg ) {
								/* translators: %d: Number of products deleted. */
								echo esc_html( sprintf( _n( '%d product deleted.', '%d products deleted.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							} elseif ( 'bulk_archive' === $licenser_bulk_msg ) {
								/* translators: %d: Number of products archived. */
								echo esc_html( sprintf( _n( '%d product archived.', '%d products archived.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							} elseif ( 'bulk_activate' === $licenser_bulk_msg ) {
								/* translators: %d: Number of products restored. */
								echo esc_html( sprintf( _n( '%d product restored to Active.', '%d products restored to Active.', $licenser_bulk_done, 'licenser' ), $licenser_bulk_done ) );
							}
							?>
						</p>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $licenser_count_all > 0 || $licenser_is_filters ) : ?>
				<div class="licenser-filter-bar">
					<nav class="licenser-pills" aria-label="<?php esc_attr_e( 'Filter products by status', 'licenser' ); ?>">
						<a href="<?php echo esc_url( $licenser_base_url ); ?>" class="licenser-pill <?php echo 'all' === $licenser_status_filter ? 'is-active' : ''; ?>"<?php echo 'all' === $licenser_status_filter ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'All', 'licenser' ); ?>
							<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_count_all ) ); ?></span>
						</a>
						<a href="<?php echo esc_url( add_query_arg( 'product_status', 'active', $licenser_base_url ) ); ?>" class="licenser-pill <?php echo 'active' === $licenser_status_filter ? 'is-active' : ''; ?>"<?php echo 'active' === $licenser_status_filter ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'Active', 'licenser' ); ?>
							<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_count_active ) ); ?></span>
						</a>
						<a href="<?php echo esc_url( add_query_arg( 'product_status', 'draft', $licenser_base_url ) ); ?>" class="licenser-pill <?php echo 'draft' === $licenser_status_filter ? 'is-active' : ''; ?>"<?php echo 'draft' === $licenser_status_filter ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'Archived', 'licenser' ); ?>
							<span class="licenser-pill__count"><?php echo esc_html( number_format_i18n( $licenser_count_draft ) ); ?></span>
						</a>
					</nav>

					<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="licenser-toolbar-filters">
						<input type="hidden" name="page" value="<?php echo esc_attr( $licenser_current_page ); ?>" />
						<?php if ( 'all' !== $licenser_status_filter ) : ?>
							<input type="hidden" name="product_status" value="<?php echo esc_attr( $licenser_status_filter ); ?>" />
						<?php endif; ?>
						<label class="licenser-sr-only" for="licenser-product-search"><?php esc_html_e( 'Search products', 'licenser' ); ?></label>
						<input type="search" id="licenser-product-search" name="s" value="<?php echo esc_attr( $licenser_search_query ); ?>" placeholder="<?php esc_attr_e( 'Search name, slug, or UUID...', 'licenser' ); ?>" />
						<button type="submit" class="licenser-btn licenser-btn--secondary"><?php esc_html_e( 'Search', 'licenser' ); ?></button>
						<?php if ( $licenser_is_filters ) : ?>
							<a href="<?php echo esc_url( $licenser_base_url ); ?>" class="licenser-btn licenser-btn--ghost"><?php esc_html_e( 'Reset', 'licenser' ); ?></a>
						<?php endif; ?>
					</form>
				</div>
			<?php endif; ?>

			<?php if ( $licenser_has_items ) : ?>
				<div class="licenser-table-card">
					<form action="<?php echo esc_url( $licenser_base_url ); ?>" method="post">
						<?php $licenser_table->display(); ?>
					</form>
				</div>
			<?php else : ?>
				<div class="licenser-empty-state">
					<div class="licenser-empty-state__icon">
						<span class="dashicons <?php echo esc_attr( $licenser_type_icon ); ?>"></span>
					</div>
					<?php if ( $licenser_is_filters ) : ?>
						<h2><?php esc_html_e( 'No matching products found', 'licenser' ); ?></h2>
						<p><?php esc_html_e( 'Try adjusting your search query or clearing active status filters.', 'licenser' ); ?></p>
						<a href="<?php echo esc_url( $licenser_base_url ); ?>" class="licenser-btn licenser-btn--secondary"><?php esc_html_e( 'Reset Filters', 'licenser' ); ?></a>
					<?php else : ?>
						<h2>
							<?php
							/* translators: %s: Product type plural (Plugins or Themes). */
							echo esc_html( sprintf( __( 'No %s yet', 'licenser' ), strtolower( $licenser_type_plural ) ) );
							?>
						</h2>
						<p>
							<?php
							/* translators: %s: Product type singular (plugin or theme). */
							echo esc_html( sprintf( __( 'Register your first %s to issue license keys, upload versioned ZIP releases, and serve automatic WordPress updates.', 'licenser' ), strtolower( $licenser_type_label ) ) );
							?>
						</p>
						<a href="<?php echo esc_url( $licenser_add_url ); ?>" class="licenser-btn licenser-btn--primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php
							/* translators: %s: Product type singular (Plugin or Theme). */
							echo esc_html( sprintf( __( 'Add Your First %s', 'licenser' ), $licenser_type_label ) );
							?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
