<?php
/**
 * Admin dashboard overview template.
 *
 * @package Licenser
 */

use Licenser\Models\License;
use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total_plugins  = Product::instance()->count( [ 'product_type' => 'plugin' ] );
$total_themes   = Product::instance()->count( [ 'product_type' => 'theme' ] );
$total_licenses = License::instance()->count();
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2><?php esc_html_e( 'Overview', 'licenser' ); ?></h2>
			</div>
		</div>
		<div class="licenser-content">
			<div class="lmwppt-main-section">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-plugins' ) ); ?>">
					<div class="lmwppt-inner">
						<div class="lmwppt-content">
							<div class="lmwppt-title">
								<h3 class="wp-heading-inline"><?php esc_html_e( 'Total Plugins', 'licenser' ); ?></h3>
							</div>
							<div class="lmwppt-icon">
								<span class="dashicons dashicons-admin-plugins lmwppt-icon-2"></span>
							</div>
						</div>
						<div class="lmwppt-counter">
							<h1><?php echo esc_html( (string) $total_plugins ); ?></h1>
						</div>
					</div>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-themes' ) ); ?>">
					<div class="lmwppt-inner">
						<div class="lmwppt-content">
							<div class="lmwppt-title">
								<h3 class="wp-heading-inline"><?php esc_html_e( 'Total Themes', 'licenser' ); ?></h3>
							</div>
							<div class="lmwppt-icon">
								<span class="dashicons dashicons-admin-appearance lmwppt-icon-3"></span>
							</div>
						</div>
						<div class="lmwppt-counter">
							<h1><?php echo esc_html( (string) $total_themes ); ?></h1>
						</div>
					</div>
				</a>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
					<div class="lmwppt-inner">
						<div class="lmwppt-content">
							<div class="lmwppt-title">
								<h3 class="wp-heading-inline"><?php esc_html_e( 'Total Licenses', 'licenser' ); ?></h3>
							</div>
							<div class="lmwppt-icon">
								<span class="dashicons dashicons-tickets-alt lmwppt-icon-1"></span>
							</div>
						</div>
						<div class="lmwppt-counter">
							<h1><?php echo esc_html( (string) $total_licenses ); ?></h1>
						</div>
					</div>
				</a>
			</div>
		</div>
	</div>
</div>
