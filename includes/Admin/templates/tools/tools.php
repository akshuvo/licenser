<?php
/**
 * Admin SDK Generator Studio & API Diagnostics Tester template.
 *
 * @package Licenser
 */

use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_product_model = Product::instance();
$licenser_products      = $licenser_product_model->get_all(
	[
		'status'       => 'active',
		'number'       => -1,
		'inc_packages' => false,
		'columns'      => 'id, name, product_type, uuid',
		'orderby'      => 'name',
		'order'        => 'ASC',
	]
);
$licenser_products      = is_array( $licenser_products ) ? $licenser_products : [];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab & product preselection query args.
$licenser_active_tab = isset( $_GET['tab'] ) && 'diagnostics' === sanitize_key( wp_unslash( $_GET['tab'] ) ) ? 'diagnostics' : 'sdk';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_preselect_uuid = isset( $_GET['product_uuid'] ) ? sanitize_text_field( wp_unslash( $_GET['product_uuid'] ) ) : '';

$licenser_default_product = null;
foreach ( $licenser_products as $licenser_p ) {
	if ( '' !== $licenser_preselect_uuid && $licenser_preselect_uuid === (string) $licenser_p->uuid ) {
		$licenser_default_product = $licenser_p;
		break;
	}
}
if ( ! $licenser_default_product && ! empty( $licenser_products ) ) {
	$licenser_default_product = $licenser_products[0];
}

$licenser_default_type     = $licenser_default_product ? (string) $licenser_default_product->product_type : 'plugin';
$licenser_default_uuid     = $licenser_default_product ? (string) $licenser_default_product->uuid : '';
$licenser_default_name     = $licenser_default_product ? (string) $licenser_default_product->name : '';
$licenser_clone_cmd        = 'git clone https://github.com/LicenserWP/client.git licenser';
$licenser_dir_tree         = "your-plugin-or-theme/\n└── licenser/\n    └── src/\n        ├── Client.php\n        ├── Insights.php\n        ├── License.php\n        └── Updater.php";
$licenser_diag_placeholder = wp_json_encode(
	[
		'status' => 'ready',
		'tip'    => __( 'Run a read-only License Check or Product Update Info request on the left.', 'licenser' ),
	],
	JSON_PRETTY_PRINT
);
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<span class="licenser-brand-mark" aria-hidden="true">
					<span class="dashicons dashicons-editor-code"></span>
				</span>
				<div class="licenser-topbar__heading">
					<h1><?php esc_html_e( 'SDK Studio', 'licenser' ); ?></h1>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<div class="licenser-pills" role="tablist" aria-label="<?php esc_attr_e( 'Developer tools', 'licenser' ); ?>">
					<button type="button" id="licenser-tab-btn-sdk" class="licenser-pill <?php echo 'sdk' === $licenser_active_tab ? 'is-active' : ''; ?>" data-licenser-tab="sdk" data-licenser-tab-sync="1" role="tab" aria-controls="licenser-tab-sdk" aria-selected="<?php echo 'sdk' === $licenser_active_tab ? 'true' : 'false'; ?>">
						<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
						<?php esc_html_e( 'Code Studio', 'licenser' ); ?>
					</button>
					<button type="button" id="licenser-tab-btn-diagnostics" class="licenser-pill <?php echo 'diagnostics' === $licenser_active_tab ? 'is-active' : ''; ?>" data-licenser-tab="diagnostics" data-licenser-tab-sync="1" role="tab" aria-controls="licenser-tab-diagnostics" aria-selected="<?php echo 'diagnostics' === $licenser_active_tab ? 'true' : 'false'; ?>">
						<span class="dashicons dashicons-rest-api" aria-hidden="true"></span>
						<?php esc_html_e( 'API Diagnostics', 'licenser' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>

			<!-- Tab 1: SDK Code Studio -->
			<div id="licenser-tab-sdk" class="licenser-tab-panel <?php echo 'sdk' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-sdk">
				<div class="licenser-studio-grid">
					<!-- Left Panel: Clean Configuration Inspector -->
					<form action="" method="post" id="sdk-generator-form" class="licenser-card licenser-studio-inspector">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Configuration', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Select your product and integration modules', 'licenser' ); ?></p>
							</div>
						</div>

						<div class="licenser-card__body licenser-studio-sections">
							<!-- Group 1: Product -->
							<div class="licenser-studio-group">
								<div class="licenser-field">
									<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
									<select name="product_type" id="product_type" required>
										<?php foreach ( $licenser_product_model->get_types() as $licenser_type_key => $licenser_type_label ) : ?>
											<option value="<?php echo esc_attr( $licenser_type_key ); ?>" <?php selected( $licenser_default_type, $licenser_type_key ); ?>><?php echo esc_html( $licenser_type_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="licenser-field">
									<label for="select_product"><?php esc_html_e( 'Product', 'licenser' ); ?></label>
									<select id="select_product" name="select_product" required>
										<option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
										<?php foreach ( $licenser_products as $licenser_product ) : ?>
											<option value="<?php echo esc_attr( (string) $licenser_product->uuid ); ?>" class="<?php echo esc_attr( $licenser_product->product_type . '-opt' ); ?>" <?php selected( $licenser_default_uuid, (string) $licenser_product->uuid ); ?>><?php echo esc_html( (string) $licenser_product->name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="licenser-uuid-chip">
									<span class="licenser-uuid-chip__label"><?php esc_html_e( 'UUID', 'licenser' ); ?></span>
									<code id="licenser-sdk-uuid-badge" class="licenser-uuid-chip__value"><?php echo $licenser_default_uuid ? esc_html( $licenser_default_uuid ) : '&mdash;'; ?></code>
									<button type="button" id="licenser-sdk-uuid-copy" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( $licenser_default_uuid ); ?>" title="<?php esc_attr_e( 'Copy Product UUID', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Copy Product UUID', 'licenser' ); ?>">
										<span class="dashicons dashicons-admin-page"></span>
									</button>
								</div>
							</div>

							<!-- Group 2: Modules -->
							<div class="licenser-studio-group">
								<span class="licenser-studio-group__label"><?php esc_html_e( 'Modules', 'licenser' ); ?></span>
								<div class="licenser-module-list">
									<div class="licenser-module-row">
										<div class="licenser-module-row__text">
											<strong><?php esc_html_e( 'License Manager', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Activation screen & daily cron check', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input type="checkbox" name="inc-licensing" id="inc-licensing" aria-label="<?php esc_attr_e( 'Include License Manager', 'licenser' ); ?>" checked />
										</label>
									</div>

									<div class="licenser-module-row">
										<div class="licenser-module-row__text">
											<strong><?php esc_html_e( 'Auto Updater', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Native WordPress one-click updates', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input type="checkbox" name="inc-updater" id="inc-updater" aria-label="<?php esc_attr_e( 'Include Automatic WordPress Updater', 'licenser' ); ?>" checked />
										</label>
									</div>

									<div class="licenser-module-row">
										<div class="licenser-module-row__text">
											<strong><?php esc_html_e( 'Usage Insights', 'licenser' ); ?></strong>
											<span><?php esc_html_e( 'Optional Pro or custom telemetry handler', 'licenser' ); ?></span>
										</div>
										<label class="licenser-switch">
											<input type="checkbox" name="inc-insights" id="inc-insights" aria-label="<?php esc_attr_e( 'Include Optional Insights', 'licenser' ); ?>" />
										</label>
									</div>
								</div>
							</div>

							<!-- Group 3: License Screen Placement -->
							<div class="licenser-studio-group licenser-sdk-licensing-options">
								<span class="licenser-studio-group__label"><?php esc_html_e( 'License Screen', 'licenser' ); ?></span>

								<div class="licenser-field">
									<label for="licenser_menu_select"><?php esc_html_e( 'Placement', 'licenser' ); ?></label>
									<select id="licenser_menu_select" name="menu_type" required>
										<option value="submenu" selected><?php esc_html_e( 'Sub Menu', 'licenser' ); ?></option>
										<option value="menu"><?php esc_html_e( 'Top-Level Menu', 'licenser' ); ?></option>
										<option value="options"><?php esc_html_e( 'Settings (Options)', 'licenser' ); ?></option>
										<option value="section"><?php esc_html_e( 'Inline Section', 'licenser' ); ?></option>
									</select>
									<p
										id="licenser-sdk-mode-hint"
										class="licenser-help"
										data-hint-menu="<?php esc_attr_e( 'Adds a dedicated top-level sidebar menu item.', 'licenser' ); ?>"
										data-hint-submenu="<?php esc_attr_e( 'Nested inside an existing menu such as Settings or your plugin menu.', 'licenser' ); ?>"
										data-hint-options="<?php esc_attr_e( 'Placed neatly inside WordPress Settings (Options).', 'licenser' ); ?>"
										data-hint-section="<?php esc_attr_e( 'Embeds cleanly inside your own settings page without a new menu.', 'licenser' ); ?>"
									></p>
								</div>

								<div class="licenser-field licenser-sdk-parent-slug-field">
									<label for="licenser_parent_menu_slug"><?php esc_html_e( 'Parent Slug', 'licenser' ); ?></label>
									<input type="text" list="licenser_parent_slug_list" name="parent_slug" id="licenser_parent_menu_slug" class="licenser-mono" value="options-general.php" placeholder="options-general.php" />
									<datalist id="licenser_parent_slug_list">
										<option value="index.php">
										<option value="edit.php">
										<option value="upload.php">
										<option value="edit.php?post_type=page">
										<option value="themes.php">
										<option value="plugins.php">
										<option value="tools.php">
										<option value="options-general.php">
									</datalist>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2 licenser-sdk-title-fields">
									<div class="licenser-field">
										<label for="licenser_menu_title"><?php esc_html_e( 'Menu Label', 'licenser' ); ?></label>
										<input type="text" name="menu_title" id="licenser_menu_title" value="<?php esc_attr_e( 'License', 'licenser' ); ?>" placeholder="<?php esc_attr_e( 'License', 'licenser' ); ?>" />
									</div>

									<div class="licenser-field">
										<label for="licenser_page_title"><?php esc_html_e( 'Page Heading', 'licenser' ); ?></label>
										<input type="text" name="page_title" id="licenser_page_title" value="<?php echo esc_attr( $licenser_default_name ? $licenser_default_name . ' License' : __( 'License Activation', 'licenser' ) ); ?>" placeholder="<?php esc_attr_e( 'License Activation', 'licenser' ); ?>" />
									</div>
								</div>
							</div>
						</div>

						<div class="licenser-card__footer">
							<span class="licenser-live-dot">
								<span class="licenser-badge__dot" aria-hidden="true"></span>
								<?php esc_html_e( 'Updates live', 'licenser' ); ?>
							</span>
							<span class="licenser-badge licenser-badge--inactive">Client v2.0.1</span>
						</div>
					</form>

					<!-- Right Panel: Step 1 Install Bar + Step 2 Unified Dark Code Studio -->
					<div class="licenser-studio-workspace">
						<!-- Step 1: Compact Terminal Install Bar -->
						<div class="licenser-card licenser-install-card">
							<div class="licenser-install-card__head">
								<div class="licenser-step-heading">
									<span class="licenser-step-num" aria-hidden="true">1</span>
									<div>
										<h2><?php esc_html_e( 'Bundle the Client SDK', 'licenser' ); ?></h2>
										<p><?php esc_html_e( 'Run inside your plugin or theme directory, or download from GitHub.', 'licenser' ); ?></p>
									</div>
								</div>
								<a href="https://github.com/LicenserWP/client" target="_blank" rel="noopener noreferrer" class="licenser-link-more">
									<?php esc_html_e( 'GitHub', 'licenser' ); ?>
									<span class="dashicons dashicons-external" aria-hidden="true"></span>
								</a>
							</div>

							<div class="licenser-terminal-bar">
								<div class="licenser-terminal-bar__cmd">
									<span class="licenser-terminal-bar__prompt" aria-hidden="true">$</span>
									<code><?php echo esc_html( $licenser_clone_cmd ); ?></code>
								</div>
								<button type="button" class="licenser-dark-btn licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( $licenser_clone_cmd ); ?>">
									<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
									<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
								</button>
							</div>

							<details class="licenser-studio-details">
								<summary><?php esc_html_e( 'Expected folder structure', 'licenser' ); ?></summary>
								<pre class="licenser-pre-block"><?php echo esc_html( $licenser_dir_tree ); ?></pre>
							</details>
						</div>

						<!-- Step 2: Unified Dark Xcode Code Studio Window -->
						<div class="licenser-code-window">
							<div class="licenser-code-chrome">
								<div class="licenser-code-chrome__left">
									<span class="licenser-window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
									<span class="licenser-step-num licenser-step-num--dark" aria-hidden="true">2</span>
									<span class="licenser-code-chrome__label"><?php esc_html_e( 'Paste into', 'licenser' ); ?></span>
									<span class="licenser-code-chrome__file" id="licenser-sdk-step-heading"><?php echo 'theme' === $licenser_default_type ? esc_html__( 'functions.php', 'licenser' ) : esc_html__( 'your-plugin.php', 'licenser' ); ?></span>
								</div>
								<button type="button" id="licenser-copy-sdk-btn" class="licenser-dark-btn licenser-copy-btn" data-licenser-copy="">
									<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
									<span class="licenser-copy-label"><?php esc_html_e( 'Copy PHP', 'licenser' ); ?></span>
								</button>
							</div>
							<div class="licenser-code-viewer">
								<textarea id="licenser-sdk-code-output" aria-label="<?php esc_attr_e( 'Generated PHP SDK integration code', 'licenser' ); ?>" readonly></textarea>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Tab 2: Live API & License Diagnostics Tester -->
			<div id="licenser-tab-diagnostics" class="licenser-tab-panel <?php echo 'diagnostics' === $licenser_active_tab ? 'is-active' : ''; ?>" role="tabpanel" aria-labelledby="licenser-tab-btn-diagnostics">
				<div class="licenser-studio-grid">
					<form id="licenser-diag-form" class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Request Builder', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Test live public SDK routes directly from wp-admin', 'licenser' ); ?></p>
							</div>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-field">
								<label for="licenser-diag-action"><?php esc_html_e( 'Endpoint Action', 'licenser' ); ?></label>
								<select id="licenser-diag-action">
									<option value="check"><?php esc_html_e( 'License Check (Read-Only)', 'licenser' ); ?></option>
									<option value="product"><?php esc_html_e( 'Product Update Info (Read-Only)', 'licenser' ); ?></option>
									<option value="activate"><?php esc_html_e( 'License Activate (Writes Domain)', 'licenser' ); ?></option>
									<option value="deactivate"><?php esc_html_e( 'License Deactivate (Removes Domain)', 'licenser' ); ?></option>
								</select>
							</div>

							<div id="licenser-diag-write-warn" class="licenser-callout-warn" hidden>
								<?php esc_html_e( 'Heads up: Activate and Deactivate modify domain activation records on the supplied license key.', 'licenser' ); ?>
							</div>

							<div class="licenser-field">
								<label for="licenser-diag-product"><?php esc_html_e( 'Product', 'licenser' ); ?></label>
								<select id="licenser-diag-product" required>
									<option value=""><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
									<?php foreach ( $licenser_products as $licenser_product ) : ?>
										<option value="<?php echo esc_attr( (string) $licenser_product->uuid ); ?>" <?php selected( $licenser_default_uuid, (string) $licenser_product->uuid ); ?>>
											<?php echo esc_html( (string) $licenser_product->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="licenser-field">
								<label for="licenser-diag-key"><?php esc_html_e( 'License Key', 'licenser' ); ?></label>
								<input type="text" id="licenser-diag-key" class="licenser-mono" placeholder="<?php esc_attr_e( 'Paste a license key to inspect...', 'licenser' ); ?>" />
							</div>

							<div class="licenser-field" id="licenser-diag-url-wrap">
								<label for="licenser-diag-url"><?php esc_html_e( 'Calling Site URL', 'licenser' ); ?></label>
								<input type="url" id="licenser-diag-url" value="https://staging.example.com" placeholder="https://staging.example.com" />
							</div>
						</div>
						<div class="licenser-card__footer">
							<span class="licenser-help"><?php esc_html_e( 'POST /wp-json/licenser/v1/public', 'licenser' ); ?></span>
							<button type="submit" class="licenser-btn licenser-btn--primary">
								<span class="dashicons dashicons-controls-play" aria-hidden="true"></span>
								<?php esc_html_e( 'Run Request', 'licenser' ); ?>
							</button>
						</div>
					</form>

					<div class="licenser-code-window">
						<div class="licenser-code-chrome">
							<div class="licenser-code-chrome__left">
								<span class="licenser-window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
								<span class="licenser-code-chrome__file"><?php esc_html_e( 'JSON Response Inspector', 'licenser' ); ?></span>
							</div>
							<button type="button" id="licenser-copy-diag-btn" class="licenser-dark-btn licenser-copy-btn" data-licenser-copy="">
								<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
								<span class="licenser-copy-label"><?php esc_html_e( 'Copy JSON', 'licenser' ); ?></span>
							</button>
						</div>
						<div class="licenser-diag-meta">
							<span id="licenser-diag-status" class="licenser-badge licenser-badge--inactive"><?php esc_html_e( 'Idle', 'licenser' ); ?></span>
							<span id="licenser-diag-time" class="licenser-badge licenser-badge--inactive licenser-tabular">&mdash;</span>
							<code id="licenser-diag-endpoint" class="licenser-mono licenser-muted"><?php esc_html_e( 'Select a product and run a request to inspect the response.', 'licenser' ); ?></code>
						</div>
						<pre id="licenser-diag-output" class="licenser-json-output"><?php echo esc_html( (string) $licenser_diag_placeholder ); ?></pre>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
