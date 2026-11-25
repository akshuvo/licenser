<?php
/**
 * Admin product create/edit template.
 *
 * @package Licenser
 */

use Licenser\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_model = Product::instance();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen parameters.
$product_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'new';

$product = $product_id > 0 ? $product_model->get( $product_id, [ 'cache' => false ] ) : false;
if ( ! $product ) {
	$product               = (object) $product_model->default_fields;
	$product->product_type = ( 'licenser-themes' === $current_page ) ? 'theme' : 'plugin';
}

$product_packages    = ( isset( $product->packages ) && is_array( $product->packages ) ) ? $product->packages : [];
$banners             = ( isset( $product->banners ) && is_array( $product->banners ) ) ? $product->banners : [];
$banner_low          = isset( $banners['low'] ) ? $banners['low'] : '';
$banner_high         = isset( $banners['high'] ) ? $banners['high'] : '';
$submit_button_label = ( 'edit' === $current_action && $product_id > 0 )
	? __( 'Update Product', 'licenser' )
	: __( 'Add Product', 'licenser' );
?>
<div class="wrap">
	<div class="licenser-root">
		<div class="licenser-header">
			<div class="licenser-header__title">
				<?php if ( $product_id > 0 ) : ?>
					<h2><?php esc_html_e( 'Edit Product', 'licenser' ); ?></h2>
				<?php else : ?>
					<h2><?php esc_html_e( 'Add New Product', 'licenser' ); ?></h2>
				<?php endif; ?>
			</div>
		</div>

		<div class="licenser-content">
			<div class="d-flex">
				<div class="licenser-sidenav">
					<ul>
						<li><a href="#product-information"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'General', 'licenser' ); ?></a></li>
						<li><a href="#release-information"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Releases', 'licenser' ); ?></a></li>
						<li><a href="#license-information"><span class="dashicons dashicons-tickets-alt"></span><?php esc_html_e( 'Packages', 'licenser' ); ?></a></li>
						<li><a href="#banners-icon"><span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Banners & Icon', 'licenser' ); ?></a></li>
						<li><a href="#public-api"><span class="dashicons dashicons-rest-api"></span><?php esc_html_e( 'Public API', 'licenser' ); ?></a></li>
					</ul>
				</div>

				<div class="licenser-navcontent w-100">
					<form action="" method="post" id="product-form">
						<div class="lmwppt-inner-card">
							<div class="lmfwppt-form-section" id="product-information">
								<h2><?php esc_html_e( 'Product Information', 'licenser' ); ?></h2>

								<div class="lmfwppt-form-field">
									<label for="name"><?php esc_html_e( 'Product Name', 'licenser' ); ?></label>
									<input type="text" name="name" id="name" class="regular-text product_name_input" placeholder="<?php esc_attr_e( 'Your Theme or Plugin Name', 'licenser' ); ?>" value="<?php echo esc_attr( $product->name ); ?>" required>
								</div>

								<div class="lmfwppt-form-field">
									<label for="uuid"><?php esc_html_e( 'Product ID (UUID)', 'licenser' ); ?></label>
									<input type="text" id="uuid" name="uuid" class="regular-text" value="<?php echo esc_attr( $product->uuid ); ?>" readonly>
								</div>

								<div class="lmfwppt-form-field">
									<label for="slug"><?php esc_html_e( 'Product Slug', 'licenser' ); ?></label>
									<input type="text" name="slug" id="slug" class="regular-text product_slug_input" placeholder="<?php esc_attr_e( 'your-theme-or-plugin-name', 'licenser' ); ?>" value="<?php echo esc_attr( $product->slug ); ?>" required>
								</div>

								<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
									<div class="lwp-col-half">
										<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
										<select name="product_type" id="product_type" required>
											<option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
											<?php foreach ( $product_model->get_types() as $key => $value ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $product->product_type, $key ); ?>><?php echo esc_html( $value ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="lwp-col-half">
										<label for="requires_php"><?php esc_html_e( 'Requires PHP Version', 'licenser' ); ?></label>
										<input type="text" name="requires_php" id="requires_php" class="regular-text" placeholder="<?php esc_attr_e( '7.4', 'licenser' ); ?>" value="<?php echo esc_attr( $product->requires_php ); ?>">
									</div>
								</div>

								<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
									<div class="lwp-col-half">
										<label for="requires"><?php esc_html_e( 'Requires WordPress Version', 'licenser' ); ?></label>
										<input type="text" name="requires" id="requires" class="regular-text" placeholder="<?php esc_attr_e( '5.8', 'licenser' ); ?>" value="<?php echo esc_attr( $product->requires ); ?>">
									</div>
									<div class="lwp-col-half">
										<label for="product_tested"><?php esc_html_e( 'Tested up to', 'licenser' ); ?></label>
										<input type="text" name="tested" id="product_tested" class="regular-text" placeholder="<?php esc_attr_e( '7.1', 'licenser' ); ?>" value="<?php echo esc_attr( $product->tested ); ?>">
									</div>
								</div>

								<div class="lmfwppt-form-field">
									<label for="author"><?php esc_html_e( 'Author Name', 'licenser' ); ?></label>
									<input type="text" name="author_name" id="author" class="regular-text product_name_input" placeholder="<?php esc_attr_e( 'Author Name', 'licenser' ); ?>" value="<?php echo esc_attr( $product->author_name ); ?>">
								</div>

								<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
									<div class="lwp-col-half">
										<label for="homepage_url"><?php esc_html_e( 'Homepage URL', 'licenser' ); ?></label>
										<input type="url" name="homepage_url" id="homepage_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $product->homepage_url ); ?>">
									</div>

									<div class="lwp-col-half">
										<label for="demo_url"><?php esc_html_e( 'Demo URL', 'licenser' ); ?></label>
										<input type="url" name="demo_url" id="demo_url" class="regular-text product_name_input" placeholder="https://example.com" value="<?php echo esc_attr( $product->demo_url ); ?>">
									</div>
								</div>

								<div class="lmfwppt-form-field">
									<label for="description"><?php esc_html_e( 'Description', 'licenser' ); ?></label>
									<textarea name="description" id="description" class="regular-text" placeholder="<?php esc_attr_e( 'Description', 'licenser' ); ?>"><?php echo esc_textarea( (string) $product->description ); ?></textarea>
								</div>
							</div>
						</div>

						<!-- Release -->
						<div class="lmwppt-inner-card release-information" id="release-information">
							<div class="d-flex justify-content-between mb-15">
								<h2 class="mb-0"><?php esc_html_e( 'Release Information', 'licenser' ); ?></h2>
								<?php if ( isset( $product->stable_release->download_link ) && ! empty( $product->stable_release->download_link ) ) : ?>
									<button class="button twp-btn twp-btn-lg add-release-btn hide-on-add-release" type="button"><?php esc_html_e( 'Add New Release', 'licenser' ); ?></button>
								<?php endif; ?>
							</div>
							<div class="add-release-form"></div>
							<?php if ( isset( $product->stable_release->download_link ) && ! empty( $product->stable_release->download_link ) ) : ?>
								<div class="lmfwppt-form-field">
									<div class="postbox lwp-postbox">
										<a class="header lmfwppt-toggle-head" data-toggle="collapse">
											<span id="poststuff">
												<h2 class="hndle">
													<span class="release-head">
														<span class="lwp-tag lwp-tag-success me-1"><?php esc_html_e( 'Stable', 'licenser' ); ?></span>
														<span class="release-version"><?php esc_html_e( 'Version:', 'licenser' ); ?> <span><?php echo esc_html( $product->stable_release->version ); ?></span></span>
														<span class="release-date"><?php esc_html_e( 'Released on:', 'licenser' ); ?> <span><?php echo esc_html( licenser_date( 'M d, Y', strtotime( (string) $product->stable_release->release_date ) ) ); ?></span></span>
													</span>
													<span class="dashicons indicator_field last-icon"></span>
												</h2>
											</span>
										</a>
										<div class="collapse lmfwppt-toggle-wrap">
											<div class="inside">
												<div class="mb-15">
													<strong><?php esc_html_e( 'File Name:', 'licenser' ); ?></strong> <span><?php echo esc_html( $product->stable_release->file_name ); ?></span>
													<span class="twp-spacer"></span>
													<strong><?php esc_html_e( 'Download Link:', 'licenser' ); ?></strong> <span><a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $product->stable_release->download_link ); ?>"><?php echo esc_html( $product->stable_release->download_link ); ?> <span class="dashicons dashicons-external"></span></a></span>
												</div>
												<div class="mb-15">
													<label><?php esc_html_e( 'Changelog:', 'licenser' ); ?></label>
													<span class="pre"><?php echo esc_html( (string) $product->stable_release->changelog ); ?></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php else : ?>
								<div class="twp-not-found hide-on-add-release">
									<span class="dashicons dashicons-info-outline"></span>
									<h2><?php esc_html_e( 'No Release Found', 'licenser' ); ?></h2>
									<p><?php esc_html_e( 'No release found for this product. Add a release to show here.', 'licenser' ); ?></p>
									<button class="button twp-btn twp-btn-lg add-release-btn" type="button"><?php esc_html_e( 'Create Your First Release', 'licenser' ); ?></button>
								</div>
							<?php endif; ?>
						</div>

						<!-- License Packages -->
						<div class="lmwppt-inner-card">
							<div class="lmfwppt-form-section" id="license-information">
								<h2><?php esc_html_e( 'License Packages', 'licenser' ); ?></h2>
								<div id="license-packages-fields" data-packages="<?php echo esc_attr( (string) wp_json_encode( $product_packages ) ); ?>"></div>
								<button class="button add-license-package" type="button"><?php esc_html_e( 'Add License Package', 'licenser' ); ?></button>
							</div>
						</div>

						<!-- Banners and Icon -->
						<div class="lmwppt-inner-card">
							<div class="lmfwppt-form-section" id="banners-icon">
								<h2><?php esc_html_e( 'Banners and Icon', 'licenser' ); ?></h2>

								<div class="lmfwppt-form-field">
									<label for="icon_url"><?php esc_html_e( 'Icon', 'licenser' ); ?></label>
									<div class="lmfwppt-file-field">
										<input type="url" name="icon_url" id="icon_url" class="regular-text" placeholder="<?php esc_attr_e( 'icon-128x128.png', 'licenser' ); ?>" value="<?php echo esc_attr( $product->icon_url ); ?>">
										<button title="<?php esc_attr_e( 'Select Icon Image', 'licenser' ); ?>" class="button trigger_media_frame" data-push_selector="#icon_url" type="button" id="icon_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
									</div>
								</div>

								<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
									<div class="lwp-col-half">
										<label for="banner_low"><?php esc_html_e( 'Banner Low', 'licenser' ); ?></label>
										<div class="lmfwppt-file-field">
											<input type="url" name="banners[low]" id="banner_low" class="regular-text" placeholder="<?php esc_attr_e( 'banner-772x250.png', 'licenser' ); ?>" value="<?php echo esc_attr( $banner_low ); ?>">
											<button title="<?php esc_attr_e( 'Select Banner Image', 'licenser' ); ?>" class="button trigger_media_frame" data-push_selector="#banner_low" type="button" id="banners_low_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
										</div>
									</div>
									<div class="lwp-col-half">
										<label for="banner_high"><?php esc_html_e( 'Banner High Resolution', 'licenser' ); ?></label>
										<div class="lmfwppt-file-field">
											<input type="url" name="banners[high]" id="banner_high" class="regular-text" placeholder="<?php esc_attr_e( 'banner-1544x500.png', 'licenser' ); ?>" value="<?php echo esc_attr( $banner_high ); ?>">
											<button title="<?php esc_attr_e( 'Select Banner Image', 'licenser' ); ?>" class="button trigger_media_frame" data-push_selector="#banner_high" type="button" id="banners_high_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="lmfwppt-buttons lmwppt-inner-card card-shameless">
							<?php if ( $product_id > 0 ) : ?>
								<input class="lmfwppt_edit_id" type="hidden" name="id" value="<?php echo esc_attr( (string) $product_id ); ?>">
							<?php endif; ?>

							<div class="submit_btn_area">
								<?php submit_button( $submit_button_label, 'primary', 'submit_product_license' ); ?>
								<span class="spinner"></span>
							</div>
							<div class="lmfwppt-notices"></div>
						</div>
					</form>

					<?php if ( ! empty( $product->uuid ) ) : ?>
						<div class="lmwppt-inner-card" id="public-api">
							<div class="lmfwppt-form-field">
								<label for="api-url"><?php esc_html_e( 'Public API URL', 'licenser' ); ?></label>
								<div class="lmfwppt-file-field field-group-overlap">
									<input type="text" id="api-url" class="regular-text" value="<?php echo esc_attr( home_url( '/wp-json/licenser/v1/public/products/' . $product->uuid ) ); ?>" readonly>
									<span class="lwp-tag lwp-tag-success"><?php esc_html_e( 'POST', 'licenser' ); ?></span>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<template id="licenser-tmpl-license-package-field">
	<div class="postbox lwp-postbox lmfwppt_license_field" data-postbox-id="lwp_delete_package">
		<input type="hidden" name="license_packages[{{{data.field_id}}}][id]" value="{{{data.id}}}" class="license-package-id">
		<a class="header lmfwppt-toggle-head" data-toggle="collapse">
			<span id="poststuff">
				<h2 class="hndle">
					<input type="text" class="prevent-toggle-head license-package-name regular-text" name="license_packages[{{{data.field_id}}}][label]" placeholder="<?php esc_attr_e( 'License Title: 1yr unlimited domain.', 'licenser' ); ?>" value="{{{data.label}}}" title="<?php esc_attr_e( 'Change title to anything you like. Make sure they are unique.', 'licenser' ); ?>" required />
					<span class="dashicons indicator_field"></span>
					<span class="delete_field remove-lwp-postbox">&times;</span>
				</h2>
			</span>
		</a>
		<div class="collapse lmfwppt-toggle-wrap">
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<div class="tf-label">
								<label for="license_packages[{{{data.field_id}}}]-update_period"><?php esc_html_e( 'Update Period', 'licenser' ); ?></label>
							</div>
						</th>
						<td>
							<input id="license_packages[{{{data.field_id}}}]-update_period" class="regular-text" type="number" min="0" name="license_packages[{{{data.field_id}}}][update_period]" value="{{{data.update_period}}}" placeholder="<?php esc_attr_e( 'Enter in Days', 'licenser' ); ?>" />
							<p><?php esc_html_e( 'Leave empty or 0 for lifetime updates.', 'licenser' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<div class="tf-label">
								<label for="license_packages[{{{data.field_id}}}]-domain_limit"><?php esc_html_e( 'Domain Limit', 'licenser' ); ?></label>
							</div>
						</th>
						<td>
							<input id="license_packages[{{{data.field_id}}}]-domain_limit" class="regular-text" type="number" min="0" name="license_packages[{{{data.field_id}}}][domain_limit]" value="{{{data.domain_limit}}}" placeholder="<?php esc_attr_e( 'How many domains allowed to get updates?', 'licenser' ); ?>" />
							<p><?php esc_html_e( 'Leave empty or 0 for unlimited domains.', 'licenser' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</template>

<template id="licenser-tmpl-add-release-field">
	<div class="lmwppt-inner-card">
		<span class="cancel-release">&times;</span>
		<div class="lmfwppt-form-section">
			<h2><?php esc_html_e( 'Add New Release', 'licenser' ); ?></h2>
		</div>

		<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
			<div class="lwp-col-half">
				<label for="product_version"><?php esc_html_e( 'Product Version', 'licenser' ); ?></label>
				<input type="text" name="version" id="product_version" class="regular-text" placeholder="1.0.0">
			</div>
			<div class="lwp-col-half">
				<label for="release_date"><?php esc_html_e( 'Release Date', 'licenser' ); ?></label>
				<input type="date" name="release_date" id="release_date" class="regular-text">
			</div>
		</div>

		<div class="lmfwppt-form-field lwp-row lwp-col-gap-20">
			<div class="lwp-col-one-third">
				<label for="file_name"><?php esc_html_e( 'File Name', 'licenser' ); ?></label>
				<input type="text" name="file_name" id="file_name" class="regular-text" placeholder="<?php esc_attr_e( 'your-plugin-file-1.0.5.zip', 'licenser' ); ?>">
			</div>
			<div class="lwp-col-two-third">
				<label for="download_link"><?php esc_html_e( 'File URL', 'licenser' ); ?></label>
				<div class="lmfwppt-file-field">
					<input type="url" name="download_link" id="download_link" class="regular-text" placeholder="<?php esc_attr_e( 'URL of the Theme/Plugin file', 'licenser' ); ?>">
					<button title="<?php esc_attr_e( 'Select Theme/Plugin ZIP File', 'licenser' ); ?>" class="button trigger_media_frame" data-push_selector="#download_link" type="button" id="download_link_button"><?php esc_html_e( 'Select File', 'licenser' ); ?></button>
				</div>
			</div>
		</div>

		<div class="lmfwppt-form-field">
			<label for="changelog"><?php esc_html_e( 'Changelog', 'licenser' ); ?></label>
			<textarea name="changelog" id="changelog" class="regular-text" placeholder="<?php esc_attr_e( 'Changelog', 'licenser' ); ?>"></textarea>
		</div>
	</div>
</template>
