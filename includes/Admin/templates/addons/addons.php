<?php
/**
 * Admin addons & integrations page template.
 *
 * @package Licenser
 */

use Licenser\Controllers\Addons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_addons       = Addons::instance();
$licenser_integrations = $licenser_addons->get_integrations();
$licenser_pro_active   = defined( 'LICENSER_PRO_VERSION' );
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons dashicons-admin-plugins"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php esc_html_e( 'Addons & Integrations', 'licenser' ); ?></h1>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-settings' ) ); ?>" class="licenser-btn licenser-btn--secondary">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Settings', 'licenser' ); ?>
				</a>
			</div>
		</div>

		<div class="licenser-main">
			<div class="licenser-notices"></div>

			<!-- Section 1: Store Integrations -->
			<div class="licenser-section-header">
				<h2><?php esc_html_e( 'Store Integrations', 'licenser' ); ?></h2>
				<p><?php esc_html_e( 'Toggle eCommerce integrations on or off. Disabled integrations never load their PHP files, hooks, or storefront assets — even when the store plugin is active.', 'licenser' ); ?></p>
			</div>

			<div class="licenser-addons-grid licenser-addons-grid--2col">
				<?php
				foreach ( $licenser_integrations as $licenser_slug => $licenser_int ) :
					$licenser_int_slug       = sanitize_key( (string) $licenser_slug );
					$licenser_int_name       = isset( $licenser_int['name'] ) ? (string) $licenser_int['name'] : $licenser_int_slug;
					$licenser_int_desc       = isset( $licenser_int['description'] ) ? (string) $licenser_int['description'] : '';
					$licenser_int_icon       = isset( $licenser_int['icon'] ) ? (string) $licenser_int['icon'] : 'dashicons-admin-plugins';
					$licenser_int_icon_style = isset( $licenser_int['icon_style'] ) ? sanitize_key( (string) $licenser_int['icon_style'] ) : 'blue';
					$licenser_int_features   = isset( $licenser_int['features'] ) && is_array( $licenser_int['features'] ) ? $licenser_int['features'] : [];
					$licenser_int_available  = $licenser_addons->is_available( $licenser_int_slug );
					$licenser_int_enabled    = $licenser_addons->is_enabled( $licenser_int_slug );
					$licenser_int_dep_active = $licenser_addons->is_dependency_active( $licenser_int_slug );
					$licenser_int_dep_name   = isset( $licenser_int['dependency_name'] ) ? (string) $licenser_int['dependency_name'] : '';
					$licenser_int_settings   = isset( $licenser_int['settings_url'] ) ? (string) $licenser_int['settings_url'] : '';

					$licenser_card_classes = 'licenser-addon-card';
					if ( $licenser_int_available && ! $licenser_int_enabled ) {
						$licenser_card_classes .= ' licenser-addon-card--disabled';
					}
					?>
					<div class="<?php echo esc_attr( $licenser_card_classes ); ?>" data-integration="<?php echo esc_attr( $licenser_int_slug ); ?>" data-dependency-name="<?php echo esc_attr( $licenser_int_dep_name ); ?>">
						<div class="licenser-addon-card__body">
							<div class="licenser-addon-card__top">
								<span class="licenser-addon-card__icon licenser-kpi-card__icon--<?php echo esc_attr( $licenser_int_icon_style ); ?>">
									<span class="dashicons <?php echo esc_attr( $licenser_int_icon ); ?>"></span>
								</span>

								<?php if ( ! $licenser_int_available ) : ?>
									<span class="licenser-badge licenser-badge--inactive">
										<?php esc_html_e( 'Coming Soon', 'licenser' ); ?>
									</span>
								<?php elseif ( ! $licenser_int_enabled ) : ?>
									<span class="licenser-badge licenser-badge--inactive licenser-addon-status-badge">
										<span class="licenser-badge__dot"></span>
										<span class="licenser-badge-label"><?php esc_html_e( 'Disabled', 'licenser' ); ?></span>
									</span>
								<?php elseif ( $licenser_int_dep_active ) : ?>
									<span class="licenser-badge licenser-badge--active licenser-addon-status-badge">
										<span class="licenser-badge__dot"></span>
										<span class="licenser-badge-label"><?php esc_html_e( 'Active', 'licenser' ); ?></span>
									</span>
								<?php else : ?>
									<span class="licenser-badge licenser-badge--warning licenser-addon-status-badge">
										<span class="licenser-badge__dot"></span>
										<span class="licenser-badge-label">
											<?php
											/* translators: %s: Dependency plugin name (e.g., WooCommerce). */
											echo esc_html( sprintf( __( 'Requires %s', 'licenser' ), $licenser_int_dep_name ) );
											?>
										</span>
									</span>
								<?php endif; ?>
							</div>

							<h3><?php echo esc_html( $licenser_int_name ); ?></h3>
							<p><?php echo esc_html( $licenser_int_desc ); ?></p>

							<?php if ( ! empty( $licenser_int_features ) ) : ?>
								<ul class="licenser-feature-list">
									<?php foreach ( $licenser_int_features as $licenser_feat ) : ?>
										<li>
											<span class="dashicons dashicons-yes"></span>
											<?php echo esc_html( (string) $licenser_feat ); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>

						<div class="licenser-addon-card__footer">
							<div class="licenser-addon-card__footer-left">
								<?php if ( $licenser_int_available ) : ?>
									<?php if ( '' !== $licenser_int_settings ) : ?>
										<a href="<?php echo esc_url( $licenser_int_settings ); ?>" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-addon-configure-btn" <?php echo ! $licenser_int_enabled ? 'hidden' : ''; ?>>
											<span class="dashicons dashicons-admin-settings"></span>
											<?php esc_html_e( 'Configure', 'licenser' ); ?>
										</a>
									<?php endif; ?>
									<span class="licenser-addon-card__meta licenser-addon-unloaded-note" <?php echo $licenser_int_enabled ? 'hidden' : ''; ?>>
										<?php esc_html_e( 'Files not loaded', 'licenser' ); ?>
									</span>
								<?php else : ?>
									<span class="licenser-addon-card__meta">
										<?php esc_html_e( 'Planned Integration', 'licenser' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="licenser-addon-toggle">
								<?php if ( $licenser_int_available ) : ?>
									<span class="licenser-addon-toggle__label">
										<?php echo $licenser_int_enabled ? esc_html__( 'Enabled', 'licenser' ) : esc_html__( 'Disabled', 'licenser' ); ?>
									</span>
									<label class="licenser-switch">
										<input
											type="checkbox"
											class="licenser-integration-toggle"
											data-integration="<?php echo esc_attr( $licenser_int_slug ); ?>"
											data-name="<?php echo esc_attr( $licenser_int_name ); ?>"
											aria-label="<?php /* translators: %s: Integration name. */ echo esc_attr( sprintf( __( 'Enable %s integration', 'licenser' ), $licenser_int_name ) ); ?>"
											<?php checked( $licenser_int_enabled ); ?>
										/>
									</label>
								<?php else : ?>
									<span class="licenser-addon-toggle__label"><?php esc_html_e( 'Soon', 'licenser' ); ?></span>
									<label class="licenser-switch licenser-switch--disabled">
										<input type="checkbox" disabled aria-disabled="true" />
									</label>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Section 2: Developer SDK & Extensions -->
			<div class="licenser-section-header licenser-section-header--spaced">
				<h2><?php esc_html_e( 'SDK & Extensions', 'licenser' ); ?></h2>
				<p><?php esc_html_e( 'Connect your commercial plugins and themes to your self-hosted update server or unlock Pro workflows.', 'licenser' ); ?></p>
			</div>

			<div class="licenser-addons-grid licenser-addons-grid--2col">
				<!-- Licenser Client SDK -->
				<div class="licenser-addon-card">
					<div class="licenser-addon-card__body">
						<div class="licenser-addon-card__top">
							<span class="licenser-addon-card__icon licenser-kpi-card__icon--blue">
								<span class="dashicons dashicons-editor-code"></span>
							</span>
							<span class="licenser-badge licenser-badge--accent">
								<?php esc_html_e( 'Open Source', 'licenser' ); ?>
							</span>
						</div>
						<h3><?php esc_html_e( 'Licenser Client SDK', 'licenser' ); ?></h3>
						<p><?php esc_html_e( 'Lightweight PHP library embedded inside your commercial plugins and themes to connect with your Licenser server.', 'licenser' ); ?></p>
						<ul class="licenser-feature-list">
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'One-click native WordPress plugin & theme updates', 'licenser' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Built-in license activation screen & daily cron check', 'licenser' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Instant bootstrap snippets in SDK Studio', 'licenser' ); ?></li>
						</ul>
					</div>
					<div class="licenser-addon-card__footer">
						<a href="https://github.com/LicenserWP/client" target="_blank" rel="noopener noreferrer" class="licenser-link-more">
							<?php esc_html_e( 'GitHub', 'licenser' ); ?> <span class="dashicons dashicons-external"></span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator' ) ); ?>" class="licenser-btn licenser-btn--secondary licenser-btn--sm">
							<?php esc_html_e( 'Open SDK Studio', 'licenser' ); ?>
						</a>
					</div>
				</div>

				<!-- Licenser Pro -->
				<div class="licenser-addon-card">
					<div class="licenser-addon-card__body">
						<div class="licenser-addon-card__top">
							<span class="licenser-addon-card__icon licenser-kpi-card__icon--teal">
								<span class="dashicons dashicons-star-filled"></span>
							</span>
							<?php if ( $licenser_pro_active ) : ?>
								<span class="licenser-badge licenser-badge--active">
									<span class="licenser-badge__dot"></span>
									<?php esc_html_e( 'Installed & Active', 'licenser' ); ?>
								</span>
							<?php else : ?>
								<span class="licenser-badge licenser-badge--inactive">
									<?php esc_html_e( 'Extension', 'licenser' ); ?>
								</span>
							<?php endif; ?>
						</div>
						<h3><?php esc_html_e( 'Licenser Pro', 'licenser' ); ?></h3>
						<p><?php esc_html_e( 'Optional companion extension for teams selling at scale with advanced licensing policies and store integrations.', 'licenser' ); ?></p>
						<ul class="licenser-feature-list">
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Staging & local domain exemption rules', 'licenser' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Automated renewal reminders & telemetry insights', 'licenser' ); ?></li>
							<li><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Extended eCommerce & subscription workflows', 'licenser' ); ?></li>
						</ul>
					</div>
					<div class="licenser-addon-card__footer">
						<span class="licenser-addon-card__meta"><?php esc_html_e( 'Optional Addon', 'licenser' ); ?></span>
						<?php if ( $licenser_pro_active ) : ?>
							<span class="licenser-badge licenser-badge--active"><?php esc_html_e( 'Active', 'licenser' ); ?></span>
						<?php else : ?>
							<a href="https://addonmaster.com/" target="_blank" rel="noopener noreferrer" class="licenser-btn licenser-btn--secondary licenser-btn--sm">
								<?php esc_html_e( 'Learn More', 'licenser' ); ?>
								<span class="dashicons dashicons-external"></span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
