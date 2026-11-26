<?php
/**
 * Admin product create/edit template.
 *
 * @package Licenser
 */

use Licenser\Models\Product;
use Licenser\Models\ProductRelease;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$licenser_product_model = Product::instance();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin screen parameters.
$licenser_product_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$licenser_current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'new';

$licenser_product = $licenser_product_id > 0 ? $licenser_product_model->get( $licenser_product_id, [ 'cache' => false ] ) : false;
if ( ! $licenser_product ) {
	$licenser_product               = (object) $licenser_product_model->default_fields;
	$licenser_product->product_type = ( 'licenser-themes' === $licenser_current_page ) ? 'theme' : 'plugin';
}

$licenser_releases = $licenser_product_id > 0 ? ProductRelease::instance()->get_all(
	[
		'product_id' => $licenser_product_id,
		'number'     => -1,
		'orderby'    => 'id',
		'order'      => 'DESC',
	]
) : [];
$licenser_releases = is_array( $licenser_releases ) ? $licenser_releases : [];

$licenser_deps = $licenser_product_id > 0 ? $licenser_product_model->get_dependency_counts( [ $licenser_product_id ] ) : [];
$licenser_dep  = isset( $licenser_deps[ $licenser_product_id ] ) ? $licenser_deps[ $licenser_product_id ] : [
	'releases' => count( $licenser_releases ),
	'packages' => 0,
	'licenses' => 0,
];

$licenser_product_packages    = ( isset( $licenser_product->packages ) && is_array( $licenser_product->packages ) ) ? $licenser_product->packages : [];
$licenser_banners             = ( isset( $licenser_product->banners ) && is_array( $licenser_product->banners ) ) ? $licenser_product->banners : [];
$licenser_banner_low          = isset( $licenser_banners['low'] ) ? (string) $licenser_banners['low'] : '';
$licenser_banner_high         = isset( $licenser_banners['high'] ) ? (string) $licenser_banners['high'] : '';
$licenser_icon_url            = ! empty( $licenser_product->icon_url ) ? (string) $licenser_product->icon_url : '';
$licenser_is_edit             = ( 'edit' === $licenser_current_action && $licenser_product_id > 0 );
$licenser_submit_button_label = $licenser_is_edit ? __( 'Save Changes', 'licenser' ) : __( 'Create Product', 'licenser' );
$licenser_list_page           = ( 'theme' === $licenser_product->product_type ) ? 'licenser-themes' : 'licenser-plugins';
$licenser_back_label          = ( 'theme' === $licenser_product->product_type ) ? __( 'Themes', 'licenser' ) : __( 'Plugins', 'licenser' );
$licenser_status_val          = ! empty( $licenser_product->status ) ? (string) $licenser_product->status : 'active';
$licenser_stable_ver          = ! empty( $licenser_releases[0]->version ) ? (string) $licenser_releases[0]->version : '';
?>
<div class="wrap licenser-wrap">
	<div class="licenser-app">
		<div class="licenser-topbar">
			<div class="licenser-topbar__left">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $licenser_list_page ) ); ?>" class="licenser-back-link">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<?php echo esc_html( $licenser_back_label ); ?>
				</a>
				<div class="licenser-topbar__heading">
					<h1>
						<?php
						if ( $licenser_is_edit && ! empty( $licenser_product->name ) ) {
							echo esc_html( (string) $licenser_product->name );
						} elseif ( $licenser_is_edit ) {
							esc_html_e( 'Edit Product', 'licenser' );
						} else {
							esc_html_e( 'Add New Product', 'licenser' );
						}
						?>
					</h1>
					<?php if ( $licenser_is_edit ) : ?>
						<span class="licenser-badge <?php echo esc_attr( 'active' === $licenser_status_val ? 'licenser-badge--active' : 'licenser-badge--inactive' ); ?>">
							<span class="licenser-badge__dot"></span>
							<?php echo esc_html( ucfirst( $licenser_status_val ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<div class="licenser-topbar__actions">
				<?php if ( $licenser_is_edit && ! empty( $licenser_product->uuid ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator&product_uuid=' . rawurlencode( (string) $licenser_product->uuid ) ) ); ?>" class="licenser-btn licenser-btn--secondary">
						<span class="dashicons dashicons-editor-code"></span>
						<?php esc_html_e( 'Generate SDK', 'licenser' ); ?>
					</a>
				<?php endif; ?>
				<button type="button" class="licenser-btn licenser-btn--primary" data-licenser-submit-form="#product-form">
					<span class="dashicons dashicons-saved"></span>
					<?php echo esc_html( $licenser_submit_button_label ); ?>
				</button>
			</div>
		</div>

		<div class="licenser-main">
			<hr class="wp-header-end" />
			<div class="licenser-notices" aria-live="polite"></div>

			<div class="licenser-editor-layout">
				<!-- Sticky Side Navigation + Product Summary -->
				<aside class="licenser-sidebar-column">
					<nav class="licenser-sidenav" aria-label="<?php esc_attr_e( 'Product sections', 'licenser' ); ?>">
						<ul>
							<li><a href="#product-information"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'General', 'licenser' ); ?></a></li>
							<li><a href="#release-information"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Releases', 'licenser' ); ?></a></li>
							<li><a href="#license-information"><span class="dashicons dashicons-tickets-alt"></span><?php esc_html_e( 'Packages', 'licenser' ); ?></a></li>
							<li><a href="#banners-icon"><span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Banners & Icon', 'licenser' ); ?></a></li>
							<?php if ( ! empty( $licenser_product->uuid ) ) : ?>
								<li><a href="#public-api"><span class="dashicons dashicons-rest-api"></span><?php esc_html_e( 'Public API', 'licenser' ); ?></a></li>
							<?php endif; ?>
						</ul>
					</nav>

					<?php if ( $licenser_is_edit ) : ?>
						<div class="licenser-side-summary">
							<p class="licenser-side-summary__title"><?php esc_html_e( 'Product Snapshot', 'licenser' ); ?></p>
							<div class="licenser-side-summary__metrics">
								<div class="licenser-side-stat">
									<span class="licenser-side-stat__val"><?php echo $licenser_stable_ver ? esc_html( 'v' . $licenser_stable_ver ) : '&mdash;'; ?></span>
									<span class="licenser-side-stat__lbl"><?php esc_html_e( 'Latest', 'licenser' ); ?></span>
								</div>
								<div class="licenser-side-stat">
									<span class="licenser-side-stat__val"><?php echo esc_html( number_format_i18n( $licenser_dep['licenses'] ) ); ?></span>
									<span class="licenser-side-stat__lbl"><?php esc_html_e( 'Licenses', 'licenser' ); ?></span>
								</div>
							</div>
							<div class="licenser-side-summary__links">
								<a class="licenser-btn licenser-btn--secondary licenser-btn--sm" href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&product_id=' . $licenser_product_id ) ); ?>">
									<span class="dashicons dashicons-tickets-alt"></span>
									<?php esc_html_e( 'View Licenses', 'licenser' ); ?>
								</a>
								<?php if ( ! empty( $licenser_product->uuid ) ) : ?>
									<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( (string) $licenser_product->uuid ); ?>">
										<span class="dashicons dashicons-admin-page"></span>
										<span class="licenser-copy-label"><?php esc_html_e( 'Copy UUID', 'licenser' ); ?></span>
									</button>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</aside>

				<!-- Main Editor Column -->
				<div>
					<form action="" method="post" id="product-form">
						<?php if ( $licenser_product_id > 0 ) : ?>
							<input class="licenser-edit-id" type="hidden" name="id" value="<?php echo esc_attr( (string) $licenser_product_id ); ?>" />
						<?php endif; ?>

						<!-- Section 1: General Information -->
						<div class="licenser-card licenser-anchor-target" id="product-information">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Product Information', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Core metadata served to WordPress update checks and the customer portal', 'licenser' ); ?></p>
								</div>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="name"><?php esc_html_e( 'Product Name', 'licenser' ); ?></label>
										<input type="text" name="name" id="name" placeholder="<?php esc_attr_e( 'e.g. Post Grid Master Pro', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_product->name ); ?>" required />
									</div>
									<div class="licenser-field">
										<label for="slug"><?php esc_html_e( 'Product Slug', 'licenser' ); ?></label>
										<input type="text" name="slug" id="slug" class="licenser-mono" placeholder="<?php esc_attr_e( 'e.g. post-grid-master-pro', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_product->slug ); ?>" required />
									</div>
								</div>

								<?php if ( ! empty( $licenser_product->uuid ) ) : ?>
									<div class="licenser-field">
										<label for="uuid"><?php esc_html_e( 'Product UUID', 'licenser' ); ?></label>
										<div class="licenser-input-group">
											<input type="text" id="uuid" name="uuid" class="licenser-mono" value="<?php echo esc_attr( (string) $licenser_product->uuid ); ?>" readonly />
											<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn" data-licenser-copy-target="#uuid">
												<span class="dashicons dashicons-admin-page"></span>
												<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
											</button>
										</div>
										<p class="licenser-help"><?php esc_html_e( 'Unique public identifier used by LicenserWP/client SDK.', 'licenser' ); ?></p>
									</div>
								<?php endif; ?>

								<div class="licenser-form-grid licenser-form-grid--3">
									<div class="licenser-field">
										<label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
										<select name="product_type" id="product_type" required>
											<option value=""><?php esc_html_e( 'Select Type', 'licenser' ); ?></option>
											<?php foreach ( $licenser_product_model->get_types() as $licenser_type_key => $licenser_type_label ) : ?>
												<option value="<?php echo esc_attr( $licenser_type_key ); ?>" <?php selected( $licenser_product->product_type, $licenser_type_key ); ?>><?php echo esc_html( $licenser_type_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="licenser-field">
										<label for="product_status"><?php esc_html_e( 'Status', 'licenser' ); ?></label>
										<select name="status" id="product_status">
											<option value="active" <?php selected( $licenser_status_val, 'active' ); ?>><?php esc_html_e( 'Active', 'licenser' ); ?></option>
											<option value="draft" <?php selected( $licenser_status_val, 'draft' ); ?>><?php esc_html_e( 'Archived (Draft)', 'licenser' ); ?></option>
										</select>
									</div>
									<div class="licenser-field">
										<label for="author"><?php esc_html_e( 'Author Name', 'licenser' ); ?></label>
										<input type="text" name="author_name" id="author" placeholder="<?php esc_attr_e( 'Author or Studio Name', 'licenser' ); ?>" value="<?php echo esc_attr( (string) $licenser_product->author_name ); ?>" />
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--3">
									<div class="licenser-field">
										<label for="requires"><?php esc_html_e( 'Requires WordPress', 'licenser' ); ?></label>
										<input type="text" name="requires" id="requires" placeholder="5.8" value="<?php echo esc_attr( (string) $licenser_product->requires ); ?>" />
									</div>
									<div class="licenser-field">
										<label for="product_tested"><?php esc_html_e( 'Tested Up To', 'licenser' ); ?></label>
										<input type="text" name="tested" id="product_tested" placeholder="7.1" value="<?php echo esc_attr( (string) $licenser_product->tested ); ?>" />
									</div>
									<div class="licenser-field">
										<label for="requires_php"><?php esc_html_e( 'Requires PHP', 'licenser' ); ?></label>
										<input type="text" name="requires_php" id="requires_php" placeholder="7.4" value="<?php echo esc_attr( (string) $licenser_product->requires_php ); ?>" />
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="homepage_url"><?php esc_html_e( 'Homepage URL', 'licenser' ); ?></label>
										<input type="url" name="homepage_url" id="homepage_url" placeholder="https://example.com" value="<?php echo esc_attr( (string) $licenser_product->homepage_url ); ?>" />
									</div>
									<div class="licenser-field">
										<label for="demo_url"><?php esc_html_e( 'Demo URL', 'licenser' ); ?></label>
										<input type="url" name="demo_url" id="demo_url" placeholder="https://demo.example.com" value="<?php echo esc_attr( (string) $licenser_product->demo_url ); ?>" />
									</div>
								</div>

								<div class="licenser-field">
									<label for="description"><?php esc_html_e( 'Description', 'licenser' ); ?></label>
									<textarea name="description" id="description" placeholder="<?php esc_attr_e( 'Describe your product features (HTML allowed for the WordPress plugin info modal)...', 'licenser' ); ?>"><?php echo esc_textarea( (string) $licenser_product->description ); ?></textarea>
								</div>
							</div>
						</div>

						<!-- Section 2: Full Release History -->
						<div class="licenser-card licenser-anchor-target" id="release-information">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Release History', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Manage versioned ZIP packages and changelogs — the newest release is served as Stable', 'licenser' ); ?></p>
								</div>
								<button class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-add-release-btn" type="button">
									<span class="dashicons dashicons-upload"></span>
									<?php esc_html_e( 'Add Release', 'licenser' ); ?>
								</button>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-add-release-form"></div>

								<div id="licenser-releases-list" class="licenser-item-list">
									<?php
									foreach ( $licenser_releases as $licenser_rel_idx => $licenser_rel ) :
										$licenser_rel_is_stable = ( 0 === $licenser_rel_idx );
										$licenser_rel_ts        = ! empty( $licenser_rel->release_date ) ? strtotime( (string) $licenser_rel->release_date ) : false;
										$licenser_rel_date_fmt  = $licenser_rel_ts ? licenser_date( 'M d, Y', $licenser_rel_ts ) : '';
										$licenser_rel_date_iso  = $licenser_rel_ts ? licenser_date( 'Y-m-d', $licenser_rel_ts ) : '';
										?>
										<div class="licenser-item-card <?php echo $licenser_rel_is_stable ? 'is-open' : ''; ?>">
											<div class="licenser-item-card__head">
												<div class="licenser-item-card__head-main">
													<span class="licenser-badge licenser-release-badge <?php echo esc_attr( $licenser_rel_is_stable ? 'licenser-badge--active' : 'licenser-badge--inactive' ); ?>">
														<span class="licenser-badge__dot"></span>
														<span class="licenser-release-badge-text"><?php echo $licenser_rel_is_stable ? esc_html__( 'Stable', 'licenser' ) : esc_html__( 'Previous', 'licenser' ); ?></span>
													</span>
													<strong class="licenser-tabular">v<?php echo esc_html( (string) $licenser_rel->version ); ?></strong>
													<?php if ( $licenser_rel_date_fmt ) : ?>
														<span class="licenser-item-card__meta licenser-tabular">
															<?php
															/* translators: %s: Release date. */
															echo esc_html( sprintf( __( 'Released %s', 'licenser' ), $licenser_rel_date_fmt ) );
															?>
														</span>
													<?php endif; ?>
												</div>
												<div class="licenser-item-card__actions">
													<button type="button" class="licenser-icon-btn licenser-edit-release-btn" data-id="<?php echo esc_attr( (string) absint( $licenser_rel->id ) ); ?>" data-version="<?php echo esc_attr( (string) $licenser_rel->version ); ?>" data-date="<?php echo esc_attr( $licenser_rel_date_iso ); ?>" data-filename="<?php echo esc_attr( (string) $licenser_rel->file_name ); ?>" data-url="<?php echo esc_attr( (string) $licenser_rel->download_link ); ?>" title="<?php esc_attr_e( 'Edit Release', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Edit Release', 'licenser' ); ?>">
														<span class="dashicons dashicons-edit"></span>
													</button>
													<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-release-btn" data-id="<?php echo esc_attr( (string) absint( $licenser_rel->id ) ); ?>" data-version="<?php echo esc_attr( (string) $licenser_rel->version ); ?>" title="<?php esc_attr_e( 'Delete Release', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Delete Release', 'licenser' ); ?>">
														<span class="dashicons dashicons-trash"></span>
													</button>
													<button type="button" class="licenser-icon-btn licenser-item-toggle-btn" aria-expanded="<?php echo $licenser_rel_is_stable ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Toggle Release Details', 'licenser' ); ?>">
														<span class="dashicons dashicons-arrow-down-alt2 licenser-item-card__chevron"></span>
													</button>
												</div>
											</div>
											<div class="licenser-item-card__body">
												<div class="licenser-form-grid licenser-form-grid--2">
													<div class="licenser-field">
														<span class="licenser-field-label"><?php esc_html_e( 'Archive File', 'licenser' ); ?></span>
														<span class="licenser-code-pill"><?php echo esc_html( (string) $licenser_rel->file_name ); ?></span>
													</div>
													<div class="licenser-field">
														<span class="licenser-field-label"><?php esc_html_e( 'Package URL', 'licenser' ); ?></span>
														<div class="licenser-key-inline">
															<a href="<?php echo esc_url( (string) $licenser_rel->download_link ); ?>" target="_blank" rel="noopener noreferrer" class="licenser-link-more">
																<?php esc_html_e( 'Open Archive', 'licenser' ); ?> <span class="dashicons dashicons-external"></span>
															</a>
															<button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="<?php echo esc_attr( (string) $licenser_rel->download_link ); ?>" title="<?php esc_attr_e( 'Copy Download URL', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Copy Download URL', 'licenser' ); ?>">
																<span class="dashicons dashicons-admin-page"></span>
															</button>
														</div>
													</div>
												</div>
												<?php if ( '' !== trim( (string) $licenser_rel->changelog ) ) : ?>
													<div class="licenser-field" style="margin-top: 12px;">
														<span class="licenser-field-label"><?php esc_html_e( 'Changelog', 'licenser' ); ?></span>
														<div class="licenser-changelog-box"><?php echo esc_html( (string) $licenser_rel->changelog ); ?></div>
													</div>
												<?php endif; ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="licenser-empty-state licenser-empty-state--inline licenser-releases-empty" <?php echo ! empty( $licenser_releases ) ? 'hidden' : ''; ?>>
									<h3><?php esc_html_e( 'No releases uploaded yet', 'licenser' ); ?></h3>
									<p><?php esc_html_e( 'Upload a ZIP archive and changelog so customers receive automatic updates inside WordPress.', 'licenser' ); ?></p>
									<button class="licenser-btn licenser-btn--primary licenser-btn--sm licenser-add-release-btn" type="button">
										<span class="dashicons dashicons-upload"></span>
										<?php esc_html_e( 'Create First Release', 'licenser' ); ?>
									</button>
								</div>
							</div>
						</div>

						<!-- Section 3: License Packages -->
						<div class="licenser-card licenser-anchor-target" id="license-information">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'License Packages', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Define reusable pricing tiers (update period & domain limits) mapped to WooCommerce products', 'licenser' ); ?></p>
								</div>
								<button class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-add-package-btn" type="button">
									<span class="dashicons dashicons-plus-alt2"></span>
									<?php esc_html_e( 'Add Package', 'licenser' ); ?>
								</button>
							</div>
							<div class="licenser-card__body">
								<div id="license-packages-fields" class="licenser-item-list" data-packages="<?php echo esc_attr( (string) wp_json_encode( $licenser_product_packages ) ); ?>"></div>

								<div class="licenser-empty-state licenser-empty-state--inline licenser-packages-empty" <?php echo ! empty( $licenser_product_packages ) ? 'hidden' : ''; ?>>
									<h3><?php esc_html_e( 'No license packages yet', 'licenser' ); ?></h3>
									<p><?php esc_html_e( 'Create packages like "1 Year • 1 Site" or "Lifetime • Unlimited" to standardize license issuance.', 'licenser' ); ?></p>
									<button class="licenser-btn licenser-btn--primary licenser-btn--sm licenser-add-package-btn" type="button">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Add First Package', 'licenser' ); ?>
									</button>
								</div>
							</div>
						</div>

						<!-- Section 4: Banners & Icon with Live Previews -->
						<div class="licenser-card licenser-anchor-target" id="banners-icon">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Banners & Icon', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Artwork shown in the WordPress plugin/theme update details modal', 'licenser' ); ?></p>
								</div>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-field">
									<label for="icon_url"><?php esc_html_e( 'Product Icon (128×128 or 256×256 px)', 'licenser' ); ?></label>
									<div class="licenser-media-row">
										<div id="licenser-preview-icon" class="licenser-media-preview licenser-media-preview--icon">
											<div class="licenser-media-preview__empty" <?php echo $licenser_icon_url ? 'hidden' : ''; ?>>
												<span class="dashicons dashicons-format-image"></span>
											</div>
											<?php if ( $licenser_icon_url ) : ?>
												<img src="<?php echo esc_url( $licenser_icon_url ); ?>" alt="<?php esc_attr_e( 'Product icon preview', 'licenser' ); ?>" />
											<?php endif; ?>
										</div>
										<div class="licenser-media-controls">
											<div class="licenser-input-group">
												<input type="url" name="icon_url" id="icon_url" data-preview-target="#licenser-preview-icon" placeholder="https://example.com/icon-256x256.png" value="<?php echo esc_attr( $licenser_icon_url ); ?>" />
												<button class="licenser-btn licenser-btn--secondary trigger_media_frame" data-push_selector="#icon_url" type="button"><?php esc_html_e( 'Select', 'licenser' ); ?></button>
												<button class="licenser-btn licenser-btn--ghost licenser-clear-media" data-target="#icon_url" type="button"><?php esc_html_e( 'Clear', 'licenser' ); ?></button>
											</div>
											<p class="licenser-help"><?php esc_html_e( 'Square PNG, JPG, or SVG artwork displayed in customer WordPress Updates screens.', 'licenser' ); ?></p>
										</div>
									</div>
								</div>

								<div class="licenser-form-grid licenser-form-grid--2">
									<div class="licenser-field">
										<label for="banner_low"><?php esc_html_e( 'Banner Low Resolution (772×250 px)', 'licenser' ); ?></label>
										<div id="licenser-preview-banner-low" class="licenser-media-preview licenser-media-preview--banner">
											<div class="licenser-media-preview__empty" <?php echo $licenser_banner_low ? 'hidden' : ''; ?>>
												<span class="dashicons dashicons-format-image"></span>
												<span>772 &times; 250 px</span>
											</div>
											<?php if ( $licenser_banner_low ) : ?>
												<img src="<?php echo esc_url( $licenser_banner_low ); ?>" alt="<?php esc_attr_e( 'Low resolution banner preview', 'licenser' ); ?>" />
											<?php endif; ?>
										</div>
										<div class="licenser-input-group">
											<input type="url" name="banners[low]" id="banner_low" data-preview-target="#licenser-preview-banner-low" placeholder="https://example.com/banner-772x250.png" value="<?php echo esc_attr( $licenser_banner_low ); ?>" />
											<button class="licenser-btn licenser-btn--secondary trigger_media_frame" data-push_selector="#banner_low" type="button"><?php esc_html_e( 'Select', 'licenser' ); ?></button>
											<button class="licenser-btn licenser-btn--ghost licenser-clear-media" data-target="#banner_low" type="button"><?php esc_html_e( 'Clear', 'licenser' ); ?></button>
										</div>
									</div>

									<div class="licenser-field">
										<label for="banner_high"><?php esc_html_e( 'Banner High Resolution (1544×500 px)', 'licenser' ); ?></label>
										<div id="licenser-preview-banner-high" class="licenser-media-preview licenser-media-preview--banner">
											<div class="licenser-media-preview__empty" <?php echo $licenser_banner_high ? 'hidden' : ''; ?>>
												<span class="dashicons dashicons-format-image"></span>
												<span>1544 &times; 500 px (Retina)</span>
											</div>
											<?php if ( $licenser_banner_high ) : ?>
												<img src="<?php echo esc_url( $licenser_banner_high ); ?>" alt="<?php esc_attr_e( 'High resolution banner preview', 'licenser' ); ?>" />
											<?php endif; ?>
										</div>
										<div class="licenser-input-group">
											<input type="url" name="banners[high]" id="banner_high" data-preview-target="#licenser-preview-banner-high" placeholder="https://example.com/banner-1544x500.png" value="<?php echo esc_attr( $licenser_banner_high ); ?>" />
											<button class="licenser-btn licenser-btn--secondary trigger_media_frame" data-push_selector="#banner_high" type="button"><?php esc_html_e( 'Select', 'licenser' ); ?></button>
											<button class="licenser-btn licenser-btn--ghost licenser-clear-media" data-target="#banner_high" type="button"><?php esc_html_e( 'Clear', 'licenser' ); ?></button>
										</div>
									</div>
								</div>
							</div>
							<div class="licenser-card__footer">
								<span class="licenser-help"><?php esc_html_e( 'Saving updates product details, new inline releases, and package changes together.', 'licenser' ); ?></span>
								<button type="submit" class="licenser-btn licenser-btn--primary" name="submit_product_license">
									<span class="licenser-inline-spinner" hidden></span>
									<?php echo esc_html( $licenser_submit_button_label ); ?>
								</button>
							</div>
						</div>
					</form>

					<!-- Section 5: Public API -->
					<?php if ( ! empty( $licenser_product->uuid ) ) : ?>
						<?php
						$licenser_update_endpoint   = home_url( '/wp-json/licenser/v1/public/products/' . $licenser_product->uuid );
						$licenser_activate_endpoint = home_url( '/wp-json/licenser/v1/public/license/' . $licenser_product->uuid . '/activate' );
						?>
						<div class="licenser-card licenser-anchor-target" id="public-api">
							<div class="licenser-card__header">
								<div class="licenser-card__title-group">
									<h2><?php esc_html_e( 'Public REST API Endpoints', 'licenser' ); ?></h2>
									<p class="licenser-card__subtitle"><?php esc_html_e( 'Endpoints consumed automatically by LicenserWP/client v2.0.1+', 'licenser' ); ?></p>
								</div>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-sdk-generator&tab=diagnostics&product_uuid=' . rawurlencode( (string) $licenser_product->uuid ) ) ); ?>" class="licenser-btn licenser-btn--secondary licenser-btn--sm">
									<span class="dashicons dashicons-rest-api"></span>
									<?php esc_html_e( 'Test Endpoint', 'licenser' ); ?>
								</a>
							</div>
							<div class="licenser-card__body">
								<div class="licenser-field">
									<label for="api-url"><?php esc_html_e( 'Update & Info Endpoint', 'licenser' ); ?> <span class="licenser-badge licenser-badge--post">POST</span></label>
									<div class="licenser-input-group">
										<input type="text" id="api-url" class="licenser-mono" value="<?php echo esc_attr( $licenser_update_endpoint ); ?>" readonly />
										<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn" data-licenser-copy-target="#api-url">
											<span class="dashicons dashicons-admin-page"></span>
											<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
										</button>
									</div>
								</div>

								<div class="licenser-field">
									<label for="api-activate-url"><?php esc_html_e( 'License Activate Endpoint', 'licenser' ); ?> <span class="licenser-badge licenser-badge--post">POST</span></label>
									<div class="licenser-input-group">
										<input type="text" id="api-activate-url" class="licenser-mono" value="<?php echo esc_attr( $licenser_activate_endpoint ); ?>" readonly />
										<button type="button" class="licenser-btn licenser-btn--secondary licenser-copy-btn" data-licenser-copy-target="#api-activate-url">
											<span class="dashicons dashicons-admin-page"></span>
											<span class="licenser-copy-label"><?php esc_html_e( 'Copy', 'licenser' ); ?></span>
										</button>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- License Package Card Template -->
<template id="licenser-tmpl-license-package-field">
	<div class="licenser-item-card">
		<input type="hidden" name="license_packages[{{{data.field_id}}}][id]" value="{{{data.id}}}" class="license-package-id" />
		<div class="licenser-item-card__head">
			<div class="licenser-item-card__head-main">
				<input type="text" class="licenser-no-toggle licenser-package-name licenser-item-card__title-input" name="license_packages[{{{data.field_id}}}][label]" placeholder="<?php esc_attr_e( 'e.g. 1 Year • 5 Sites', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'License package name', 'licenser' ); ?>" value="{{{data.label}}}" required />
				<span class="licenser-badge licenser-badge--accent licenser-pkg-summary">{{{data.summary}}}</span>
			</div>
			<div class="licenser-item-card__actions">
				<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-remove-package-btn" title="<?php esc_attr_e( 'Remove Package', 'licenser' ); ?>" aria-label="<?php esc_attr_e( 'Remove Package', 'licenser' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
				<button type="button" class="licenser-icon-btn licenser-item-toggle-btn" aria-expanded="false" aria-label="<?php esc_attr_e( 'Expand Package Settings', 'licenser' ); ?>">
					<span class="dashicons dashicons-arrow-down-alt2 licenser-item-card__chevron"></span>
				</button>
			</div>
		</div>
		<div class="licenser-item-card__body">
			<div class="licenser-form-grid licenser-form-grid--2">
				<div class="licenser-field">
					<label for="pkg-period-{{{data.field_id}}}"><?php esc_html_e( 'Update Period (Days)', 'licenser' ); ?></label>
					<input id="pkg-period-{{{data.field_id}}}" class="licenser-pkg-period" type="number" min="0" name="license_packages[{{{data.field_id}}}][update_period]" value="{{{data.update_period}}}" placeholder="365" />
					<p class="licenser-help"><?php esc_html_e( 'Enter 0 or leave empty for Lifetime updates.', 'licenser' ); ?></p>
				</div>
				<div class="licenser-field">
					<label for="pkg-limit-{{{data.field_id}}}"><?php esc_html_e( 'Domain Activation Limit', 'licenser' ); ?></label>
					<input id="pkg-limit-{{{data.field_id}}}" class="licenser-pkg-limit" type="number" min="0" name="license_packages[{{{data.field_id}}}][domain_limit]" value="{{{data.domain_limit}}}" placeholder="1" />
					<p class="licenser-help"><?php esc_html_e( 'Enter 0 or leave empty for Unlimited domains.', 'licenser' ); ?></p>
				</div>
			</div>
			<div class="licenser-field licenser-package-uuid-wrap" style="margin-top: 12px;">
				<span class="licenser-field-label"><?php esc_html_e( 'Package UUID', 'licenser' ); ?></span>
				<div class="licenser-key-inline">
					<code class="licenser-code-pill">{{{data.package_id}}}</code>
					<button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="{{{data.package_id}}}" title="<?php esc_attr_e( 'Copy Package UUID', 'licenser' ); ?>">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<!-- Dynamic Release Row Template -->
<template id="licenser-tmpl-release-item">
	<div class="licenser-item-card">
		<div class="licenser-item-card__head">
			<div class="licenser-item-card__head-main">
				<span class="licenser-badge licenser-release-badge {{{data.badge_class}}}">
					<span class="licenser-badge__dot"></span>
					<span class="licenser-release-badge-text">{{{data.badge_label}}}</span>
				</span>
				<strong class="licenser-tabular">v{{{data.version}}}</strong>
				<span class="licenser-item-card__meta licenser-tabular">{{{data.date_formatted}}}</span>
			</div>
			<div class="licenser-item-card__actions">
				<button type="button" class="licenser-icon-btn licenser-edit-release-btn" data-id="{{{data.id}}}" data-version="{{{data.version}}}" data-date="{{{data.release_date_iso}}}" data-filename="{{{data.file_name}}}" data-url="{{{data.download_link}}}" title="<?php esc_attr_e( 'Edit Release', 'licenser' ); ?>">
					<span class="dashicons dashicons-edit"></span>
				</button>
				<button type="button" class="licenser-icon-btn licenser-icon-btn--danger licenser-delete-release-btn" data-id="{{{data.id}}}" data-version="{{{data.version}}}" title="<?php esc_attr_e( 'Delete Release', 'licenser' ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</button>
				<button type="button" class="licenser-icon-btn licenser-item-toggle-btn" aria-label="<?php esc_attr_e( 'Toggle Release Details', 'licenser' ); ?>">
					<span class="dashicons dashicons-arrow-down-alt2 licenser-item-card__chevron"></span>
				</button>
			</div>
		</div>
		<div class="licenser-item-card__body">
			<div class="licenser-form-grid licenser-form-grid--2">
				<div class="licenser-field">
					<span class="licenser-field-label"><?php esc_html_e( 'Archive File', 'licenser' ); ?></span>
					<span class="licenser-code-pill">{{{data.file_name}}}</span>
				</div>
				<div class="licenser-field">
					<span class="licenser-field-label"><?php esc_html_e( 'Package URL', 'licenser' ); ?></span>
					<div class="licenser-key-inline">
						<a href="{{{data.download_link}}}" target="_blank" rel="noopener noreferrer" class="licenser-link-more"><?php esc_html_e( 'Open Archive', 'licenser' ); ?> <span class="dashicons dashicons-external"></span></a>
						<button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="{{{data.download_link}}}" title="<?php esc_attr_e( 'Copy Download URL', 'licenser' ); ?>">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
				</div>
			</div>
			<div class="licenser-field" style="margin-top: 12px;">
				<span class="licenser-field-label"><?php esc_html_e( 'Changelog', 'licenser' ); ?></span>
				<div class="licenser-changelog-box">{{{data.changelog}}}</div>
			</div>
		</div>
	</div>
</template>

<!-- Inline Add / Edit Release Form Template -->
<template id="licenser-tmpl-release-form">
	<div class="licenser-inline-form-card">
		<div class="licenser-inline-form-card__head">
			<h3>{{{data.form_title}}}</h3>
			<button type="button" class="licenser-icon-btn licenser-cancel-release-btn" aria-label="<?php esc_attr_e( 'Cancel', 'licenser' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<input type="hidden" id="licenser-rel-id" value="{{{data.id}}}" />
		<div class="licenser-form-grid licenser-form-grid--2">
			<div class="licenser-field">
				<label for="licenser-rel-version"><?php esc_html_e( 'Version', 'licenser' ); ?></label>
				<input type="text" name="version" id="licenser-rel-version" placeholder="1.0.0" value="{{{data.version}}}" />
			</div>
			<div class="licenser-field">
				<label for="licenser-rel-date"><?php esc_html_e( 'Release Date', 'licenser' ); ?></label>
				<input type="date" name="release_date" id="licenser-rel-date" value="{{{data.release_date}}}" />
			</div>
		</div>
		<div class="licenser-form-grid licenser-form-grid--2" style="margin-top: 14px;">
			<div class="licenser-field">
				<label for="licenser-rel-filename"><?php esc_html_e( 'File Name', 'licenser' ); ?></label>
				<input type="text" name="file_name" id="licenser-rel-filename" placeholder="my-plugin-1.0.0.zip" value="{{{data.file_name}}}" />
			</div>
			<div class="licenser-field">
				<label for="licenser-rel-url"><?php esc_html_e( 'ZIP File URL', 'licenser' ); ?></label>
				<div class="licenser-input-group">
					<input type="url" name="download_link" id="licenser-rel-url" placeholder="https://example.com/uploads/license-manager/plugin.zip" value="{{{data.download_link}}}" />
					<button type="button" class="licenser-btn licenser-btn--secondary trigger_media_frame" data-media-scope="release" data-push_selector="#licenser-rel-url" data-filename_selector="#licenser-rel-filename"><?php esc_html_e( 'Upload ZIP', 'licenser' ); ?></button>
				</div>
			</div>
		</div>
		<div class="licenser-field" style="margin-top: 14px;">
			<label for="licenser-rel-changelog"><?php esc_html_e( 'Changelog', 'licenser' ); ?></label>
			<textarea name="changelog" id="licenser-rel-changelog" placeholder="<?php esc_attr_e( 'Summarize improvements and fixes in this release...', 'licenser' ); ?>">{{{data.changelog}}}</textarea>
		</div>
		<div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
			<button type="button" class="licenser-btn licenser-btn--secondary licenser-btn--sm licenser-cancel-release-btn"><?php esc_html_e( 'Cancel', 'licenser' ); ?></button>
			<button type="button" class="licenser-btn licenser-btn--primary licenser-btn--sm licenser-save-release-btn"><?php esc_html_e( 'Save Release', 'licenser' ); ?></button>
		</div>
	</div>
</template>
