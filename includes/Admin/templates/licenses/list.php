<?php
/**
 * Admin licenses list template.
 *
 * @package Licenser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2><?php esc_html_e( 'Licenses', 'licenser' ); ?></h2>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New License', 'licenser' ); ?></a>
		</div>

		<div class="licenser-content">
			<div class="lmfwppt-notices"></div>
			<form action="" method="post">
				<?php
				$table = new Licenser\Admin\LicensesListTable();
				$table->prepare_items();
				$table->display();
				?>
			</form>
		</div>
	</div>
</div>
