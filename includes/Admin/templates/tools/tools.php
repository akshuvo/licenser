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
$licenser_clone_cmd        = "cd your-plugin-or-theme\ngit clone https://github.com/LicenserWP/client.git licenser";
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
					<h1><?php esc_html_e( 'SDK Studio & API Diagnostics', 'licenser' ); ?></h1>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<div class="licenser-pills" role="tablist">
					<button type="button" class="licenser-pill <?php echo 'sdk' === $licenser_active_tab ? 'is-active' : ''; ?>" data-licenser-tab="sdk" role="tab" aria-selected="<?php echo 'sdk' === $licenser_active_tab ? 'true' : 'false'; ?>">
						<span class="dashicons dashicons-editor-code"></span>
						<?php esc_html_e( 'SDK Code Studio', 'licenser' ); ?>
					</button>
					<button type="button" class="licenser-pill <?php echo 'diagnostics' === $licenser_active_tab ? 'is-active' : ''; ?>" data-licenser-tab="diagnostics" role="tab" aria-selected="<?php echo 'diagnostics' === $licenser_active_tab ? 'true' : 'false'; ?>">
						<span class="dashicons dashicons-rest-api"></span>
						<?php esc_html_e( 'API Diagnostics', 'licenser' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="licenser-main">
			<div class="licenser-notices"></div>

			<!-- Tab 1: SDK Code Studio -->
			<div id="licenser-tab-sdk" class="licenser-tab-panel <?php echo 'sdk' === $licenser_active_tab ? 'is-active' : ''; ?>">
				<div class="licenser-studio-grid">
					<!-- Left Panel: Generator Options -->
					<form action="" method="post" id="sdk-generator-form" class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Integration Options', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Code updates live as you configure your product', 'licenser' ); ?></p>
							</div>
						</div>
						<div class="licenser-card__body">
							<div class="licenser-field">
								<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
								<select name="product_type" id="product_type" required>
									<?php foreach ( $licenser_product_model->get_types() as $licenser_type_key => $licenser_type_label ) : ?>
										<option value="<?php echo esc_attr( $licenser_type_key ); ?>" <?php selected( $licenser_default_type, $licenser_type_key ); ?>><?php echo esc_html( $licenser_type_label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="licenser-field">
								<label for="select_product"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
								<select id="select_product" name="select_product" required>
									<option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
									<?php foreach ( $licenser_products as $licenser_product ) : ?>
										<option value="<?php echo esc_attr( (string) $licenser_product->uuid ); ?>" class="<?php echo esc_attr( $licenser_product->product_type . '-opt' ); ?>" <?php selected( $licenser_default_uuid, (string) $licenser_product->uuid ); ?>><?php echo esc_html( (string) $licenser_product->name ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="licenser-help">
									<?php esc_html_e( 'Product UUID:', 'licenser' ); ?>
									<code id="licenser-sdk-uuid-badge" class="licenser-mono"><?php echo $licenser_default_uuid ? esc_html( $licenser_default_uuid ) : '&mdash;'; ?></code>
								</p>
							</div>

							<div class="licenser-field">
								<label for="licenser_menu_select"><?php esc_html_e( 'License Page Output Mode', 'licenser' ); ?></label>
								<select id="licenser_menu_select" name="menu_type" required>
									<option value="submenu" selected><?php esc_html_e( 'Sub Menu (Under Existing Menu)', 'licenser' ); ?></option>
									<option value="menu"><?php esc_html_e( 'Top-Level Admin Menu', 'licenser' ); ?></option>
									<option value="options"><?php esc_html_e( 'WordPress Settings (Options)', 'licenser' ); ?></option>
									<option value="section"><?php esc_html_e( 'Inline Section (Embed in Existing Page)', 'licenser' ); ?></option>
								</select>
								<div
									id="licenser-sdk-mode-hint"
									class="licenser-mode-hint"
									data-hint-menu="<?php esc_attr_e( 'Registers a dedicated top-level menu item in the WordPress admin sidebar for license activation.', 'licenser' ); ?>"
									data-hint-submenu="<?php esc_attr_e( 'Attaches the license activation screen beneath an existing parent menu slug (for example, your plugin menu or options-general.php).', 'licenser' ); ?>"
									data-hint-options="<?php esc_attr_e( 'Places the license activation page neatly inside WordPress Settings (Options) without requiring a parent menu slug.', 'licenser' ); ?>"
									data-hint-section="<?php esc_attr_e( 'Exposes a renderable license form section without creating a new admin menu — ideal when embedding inside your own settings tabs.', 'licenser' ); ?>"
								></div>
							</div>

							<div class="licenser-field licenser-sdk-parent-slug-field">
								<label for="licenser_parent_menu_slug"><?php esc_html_e( 'Parent Menu Slug', 'licenser' ); ?></label>
								<input type="text" list="licenser_parent_slug_list" name="parent_slug" id="licenser_parent_menu_slug" value="options-general.php" placeholder="options-general.php" />
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

							<div class="licenser-sdk-title-fields">
								<div class="licenser-field">
									<label for="licenser_page_title"><?php esc_html_e( 'Page Title', 'licenser' ); ?></label>
									<input type="text" name="page_title" id="licenser_page_title" value="<?php echo esc_attr( $licenser_default_name ? $licenser_default_name . ' License Activation' : '' ); ?>" placeholder="<?php esc_attr_e( 'My Plugin License Activation', 'licenser' ); ?>" />
								</div>

								<div class="licenser-field">
									<label for="licenser_menu_title"><?php esc_html_e( 'Menu Title', 'licenser' ); ?></label>
									<input type="text" name="menu_title" id="licenser_menu_title" value="<?php echo esc_attr( $licenser_default_name ? $licenser_default_name . ' License' : '' ); ?>" placeholder="<?php esc_attr_e( 'License', 'licenser' ); ?>" />
								</div>
							</div>

							<div class="licenser-field">
								<span class="licenser-field-label"><?php esc_html_e( 'SDK Modules', 'licenser' ); ?></span>
								<div class="licenser-toggle-stack">
									<label class="licenser-switch">
										<input type="checkbox" name="inc-licensing" id="inc-licensing" checked />
										<span><?php esc_html_e( 'Include License Manager & Daily Checker', 'licenser' ); ?></span>
									</label>
									<label class="licenser-switch">
										<input type="checkbox" name="inc-updater" id="inc-updater" checked />
										<span><?php esc_html_e( 'Include Automatic WordPress Updater', 'licenser' ); ?></span>
									</label>
									<label class="licenser-switch">
										<input type="checkbox" name="inc-insights" id="inc-insights" />
										<span><?php esc_html_e( 'Include Optional Insights (Pro / Custom Handler)', 'licenser' ); ?></span>
									</label>
								</div>
							</div>
						</div>
						<div class="licenser-card__footer">
							<span class="licenser-help"><?php esc_html_e( 'Compatible with LicenserWP/client v2.0.1', 'licenser' ); ?></span>
							<button type="submit" class="licenser-btn licenser-btn--primary licenser-btn--sm"><?php esc_html_e( 'Refresh Code', 'licenser' ); ?></button>
						</div>
					</form>

					<!-- Right Panel: Live PHP Code Output + Quick Setup Guide -->
					<div>
						<div class="licenser-card">
							<div class="licenser-code-chrome">
								<div class="licenser-code-chrome__left">
									<span class="licenser-window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
									<span class="licenser-code-chrome__file" id="licenser-sdk-step-heading"><?php esc_html_e( 'Step 2: Add this snippet to your main plugin file.', 'licenser' ); ?></span>
								</div>
								<button type="button" id="licenser-copy-sdk-btn" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-copy-btn" data-licenser-copy="">
									<span class="dashicons dashicons-admin-page"></span>
									<span class="licenser-copy-label"><?php esc_html_e( 'Copy PHP Code', 'licenser' ); ?></span>
								</button>
							</div>
							<div class="licenser-code-viewer">
								<textarea id="licenser-sdk-code-output" readonly></textarea>
							</div>
						</div>

						<div class="licenser-card">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Step 1: Bundle the Licenser Client Library', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Place the open-source client SDK inside your plugin or theme folder', 'licenser' ); ?></p>
								</div>
								<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( $licenser_clone_cmd ); ?>">
									<span class="dashicons dashicons-admin-page"></span>
									<span class="licenser-copy-label"><?php esc_html_e( 'Copy Command', 'licenser' ); ?></span>
								</button>
							</div>
							<div class="licenser-card__body">
								<pre class="licenser-pre-block"><?php echo esc_html( $licenser_clone_cmd ); ?></pre>
								<p class="licenser-help">
									<?php
									echo wp_kses(
										sprintf(
											/* translators: 1: Link to GitHub repo, 2: Code-formatted folder name. */
											__( 'Or download the library from %1$s and extract it inside your product root directory as %2$s:', 'licenser' ),
											'<a href="https://github.com/LicenserWP/client" target="_blank" rel="noopener noreferrer">github.com/LicenserWP/client</a>',
											'<code>licenser</code>'
										),
										[
											'a'    => [
												'href'   => [],
												'target' => [],
												'rel'    => [],
											],
											'code' => [],
										]
									);
									?>
								</p>
								<pre class="licenser-pre-block"><?php echo esc_html( $licenser_dir_tree ); ?></pre>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Tab 2: Live API & License Diagnostics Tester -->
			<div id="licenser-tab-diagnostics" class="licenser-tab-panel <?php echo 'diagnostics' === $licenser_active_tab ? 'is-active' : ''; ?>">
				<div class="licenser-studio-grid">
					<form id="licenser-diag-form" class="licenser-card">
						<div class="licenser-card__header">
							<div class="licenser-card__title-group">
								<h2><?php esc_html_e( 'Live API Request Builder', 'licenser' ); ?></h2>
								<p class="licenser-card__subtitle"><?php esc_html_e( 'Test public SDK routes directly without curl or Postman', 'licenser' ); ?></p>
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

							<div id="licenser-diag-write-warn" class="licenser-mode-hint" style="display: none; background: #fff6e5; color: #92400e;">
								<?php esc_html_e( 'Heads up: Activate and Deactivate modify domain activation records on the supplied license key.', 'licenser' ); ?>
							</div>

							<div class="licenser-field" style="margin-top: 14px;">
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
							<span class="licenser-help"><?php esc_html_e( 'Calls live local /wp-json/licenser/v1/public', 'licenser' ); ?></span>
							<button type="submit" class="licenser-btn licenser-btn--primary">
								<span class="dashicons dashicons-controls-play"></span>
								<?php esc_html_e( 'Run Test Request', 'licenser' ); ?>
							</button>
						</div>
					</form>

					<div class="licenser-card">
						<div class="licenser-code-chrome">
							<div class="licenser-code-chrome__left">
								<span class="licenser-window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
								<span class="licenser-code-chrome__file"><?php esc_html_e( 'JSON Response Inspector', 'licenser' ); ?></span>
							</div>
							<button type="button" id="licenser-copy-diag-btn" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-copy-btn" data-licenser-copy="">
								<span class="dashicons dashicons-admin-page"></span>
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
