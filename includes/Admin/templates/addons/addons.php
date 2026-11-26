<?php
/**
 * Admin addons page template.
 *
 * @package Licenser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_wc_active  = class_exists( 'WooCommerce' );
$licenser_pro_active = defined( 'LICENSER_PRO_VERSION' );
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2><?php esc_html_e( 'Addons', 'licenser' ); ?></h2>
			</div>
		</div>

		<div class="licenser-content">
			<div class="licenser-group-wrapper">
				<div class="licenser-group">
					<div class="licenser-group__title">
						<h2><?php esc_html_e( 'Integrations & Extensions', 'licenser' ); ?></h2>
					</div>
					<div class="licenser-group__content">
						<div class="licenser-addon-card">
							<div class="licenser-addon-card__title">
								<h3><?php esc_html_e( 'WooCommerce Lite (Built-in)', 'licenser' ); ?></h3>
							</div>
							<div class="licenser-addon-card__content">
								<p><?php esc_html_e( 'Automatically generate and deliver license keys when customers purchase licensed simple or variable products in WooCommerce.', 'licenser' ); ?></p>
							</div>
							<div class="licenser-addon-card__footer">
								<?php if ( $licenser_wc_active ) : ?>
									<span class="lwp-tag lwp-tag-success"><?php esc_html_e( 'Active', 'licenser' ); ?></span>
								<?php else : ?>
									<span class="lwp-tag"><?php esc_html_e( 'Requires WooCommerce', 'licenser' ); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<div class="licenser-addon-card">
							<div class="licenser-addon-card__title">
								<h3><?php esc_html_e( 'Licenser Pro', 'licenser' ); ?></h3>
							</div>
							<div class="licenser-addon-card__content">
								<p><?php esc_html_e( 'Extend Licenser with advanced licensing workflows, customer self-service management, and premium eCommerce integrations.', 'licenser' ); ?></p>
							</div>
							<div class="licenser-addon-card__footer">
								<?php if ( $licenser_pro_active ) : ?>
									<span class="lwp-tag lwp-tag-success"><?php esc_html_e( 'Installed & Active', 'licenser' ); ?></span>
								<?php else : ?>
									<a href="https://addonmaster.com/" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php esc_html_e( 'Learn More', 'licenser' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
