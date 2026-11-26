<?php
/**
 * Admin products list template.
 *
 * @package Licenser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page slug.
$licenser_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';
$licenser_product_type = ( 'licenser-themes' === $licenser_current_page ) ? 'theme' : 'plugin';
$licenser_type_label   = ( 'theme' === $licenser_product_type ) ? __( 'Theme', 'licenser' ) : __( 'Plugin', 'licenser' );
$licenser_type_plural  = ( 'theme' === $licenser_product_type ) ? __( 'Themes', 'licenser' ) : __( 'Plugins', 'licenser' );
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2>
					<?php
					/* translators: %s: Product Type plural label (Plugins or Themes). */
					echo esc_html( sprintf( __( 'License Manager: %s', 'licenser' ), $licenser_type_plural ) );
					?>
				</h2>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-' . $licenser_product_type . 's&action=new' ) ); ?>" class="page-title-action">
				<?php
				/* translators: %s: Product Type label (Plugin or Theme). */
				echo esc_html( sprintf( __( 'Add New %s', 'licenser' ), $licenser_type_label ) );
				?>
			</a>
		</div>

		<div class="licenser-content">
			<div class="lmfwppt-notices"></div>
			<form action="" method="post">
				<?php
				$licenser_table = new Licenser\Admin\ProductsListTable();
				$licenser_table->prepare_items( $licenser_product_type );
				$licenser_table->display();
				?>
			</form>
		</div>
	</div>
</div>
