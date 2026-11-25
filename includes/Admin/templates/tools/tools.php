<?php
/**
 * Admin SDK Generator tool template.
 *
 * @package Licenser
 */

use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_model = Product::instance();
$products      = $product_model->get_all(
	[
		'status'       => 'active',
		'number'       => -1,
		'inc_packages' => false,
		'columns'      => 'name, product_type, uuid',
	]
);
$products      = is_array( $products ) ? $products : [];
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<h2><?php esc_html_e( 'SDK Generator', 'licenser' ); ?></h2>
			</div>
		</div>

		<div class="licenser-content">
			<form action="" method="post" id="sdk-generator-form">
				<div class="lmwppt-inner-card">
					<div class="lmfwppt-form-section">
						<div class="lmfwppt-form-field">
							<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
							<select name="product_type" class="product_type" id="product_type" required>
								<option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
								<?php foreach ( $product_model->get_types() as $key => $value ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="lmfwppt-form-field">
							<label for="select_product"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
							<select id="select_product" name="select_product" class="select_product products_list" required>
								<option value="" class="blank"><?php esc_html_e( 'Select Product', 'licenser' ); ?></option>
								<?php foreach ( $products as $product ) : ?>
									<option value="<?php echo esc_attr( $product->uuid ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt' ); ?>"><?php echo esc_html( $product->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="lmfwppt-form-field">
							<label for="lmfwppt_menu_select"><?php esc_html_e( 'Output Type', 'licenser' ); ?></label>
							<select id="lmfwppt_menu_select" name="menu_type" class="menu_select" required>
								<option value="menu"><?php esc_html_e( 'Menu', 'licenser' ); ?></option>
								<option value="submenu"><?php esc_html_e( 'Sub Menu', 'licenser' ); ?></option>
								<option value="options"><?php esc_html_e( 'Settings (Options)', 'licenser' ); ?></option>
								<option value="section"><?php esc_html_e( 'Section', 'licenser' ); ?></option>
							</select>
						</div>

						<div class="lmfwppt-form-field parent-slug-menu hide-on-section-type">
							<label for="lmfwppt_parent_menu_slug"><?php esc_html_e( 'Parent Menu Slug', 'licenser' ); ?></label>
							<input type="text" list="parent_slug_list" name="parent_slug" id="lmfwppt_parent_menu_slug" class="regular-text" placeholder="<?php esc_attr_e( 'Parent Menu Slug', 'licenser' ); ?>">
							<datalist id="parent_slug_list">
								<option value="index.php">
								<option value="edit.php">
								<option value="upload.php">
								<option value="edit.php?post_type=page">
								<option value="edit-comments.php">
								<option value="themes.php">
								<option value="plugins.php">
								<option value="users.php">
								<option value="tools.php">
								<option value="options-general.php">
							</datalist>
						</div>

						<div class="lmfwppt-form-field page-title-wrap hide-on-section-type show-on-default-type">
							<label for="lmfwppt_page_title"><?php esc_html_e( 'Page Title', 'licenser' ); ?></label>
							<input type="text" name="page_title" id="lmfwppt_page_title" class="regular-text lmfwppt_page_title" placeholder="<?php esc_attr_e( 'Page Title', 'licenser' ); ?>">
						</div>

						<div class="lmfwppt-form-field menu-title-wrap hide-on-section-type show-on-default-type">
							<label for="lmfwppt_menu_title"><?php esc_html_e( 'Menu Title', 'licenser' ); ?></label>
							<input type="text" name="menu_title" id="lmfwppt_menu_title" class="regular-text lmfwppt_menu_title" placeholder="<?php esc_attr_e( 'Menu Title', 'licenser' ); ?>">
						</div>

						<div class="lmfwppt-form-field d-flex">
							<label class="me-1">
								<input type="checkbox" name="inc-licensing" id="inc-licensing" checked>
								<?php esc_html_e( 'Include Licensing', 'licenser' ); ?>
							</label>
							<label class="me-1">
								<input type="checkbox" name="inc-updater" id="inc-updater" checked>
								<?php esc_html_e( 'Include Updater', 'licenser' ); ?>
							</label>
							<label>
								<input type="checkbox" name="inc-insights" id="inc-insights">
								<?php esc_html_e( 'Include Insights', 'licenser' ); ?>
							</label>
						</div>
					</div>
				</div>

				<div class="lmwppt-inner-card lmfwppt-buttons card-shameless">
					<div class="submit_btn_area">
						<button type="submit" class="button button-primary" id="submit"><?php esc_html_e( 'Generate SDK', 'licenser' ); ?></button>
						<span class="spinner"></span>
					</div>
					<div class="lmfwppt-notices"></div>
				</div>

				<div class="lmwppt-inner-card">
					<div class="lmfwppt-form-section">
						<h2><?php esc_html_e( 'How to use', 'licenser' ); ?></h2>
						<h3><?php esc_html_e( 'Step 1: Clone the Licenser client library', 'licenser' ); ?></h3>
						<p><?php esc_html_e( 'Navigate to your project using shell and clone the Licenser client repository inside your project:', 'licenser' ); ?></p>
						<pre class="licenser-code-text">cd your-plugin-or-theme
git clone https://github.com/LicenserWP/client.git licenser</pre>

						<p>
							<?php
							echo wp_kses(
								sprintf(
									/* translators: %s: Link to LicenserWP client GitHub repository. */
									__( 'Or download the client library from %s and include it in your plugin or theme root directory. The client library folder name should be `licenser`:', 'licenser' ),
									'<a href="https://github.com/LicenserWP/client" target="_blank" rel="noopener noreferrer">https://github.com/LicenserWP/client</a>'
								),
								[
									'a' => [
										'href'   => [],
										'target' => [],
										'rel'    => [],
									],
								]
							);
							?>
						</p>

						<pre class="licenser-code-text">├── your-plugin-or-theme
│   ├── licenser
│   │   ├── src
│   │   │   ├── Client.php
│   │   │   ├── Insights.php
│   │   │   ├── License.php
│   │   │   └── Updater.php</pre>

						<div class="client_generator_response"></div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
