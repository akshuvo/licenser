<?php

namespace Licenser\Addons\WooLite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;

/**
 * WooCommerce product data and order meta box admin handler.
 */
class Admin_Handler {

	/**
	 * Register WooCommerce product and order admin hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields_simple_product' ], 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_variation_custom_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_fields_variable_product' ], 10, 2 );
		add_action( 'add_meta_boxes', [ $this, 'register_order_meta_box' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Enqueue WooCommerce product and order editor assets strictly on WC product/order screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_product_screen = in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && 'product' === $screen->post_type;
		$is_order_screen   = in_array( $screen->id, [ 'shop_order', 'woocommerce_page_wc-orders' ], true );

		if ( ! $is_product_screen && ! $is_order_screen ) {
			return;
		}

		wp_enqueue_style(
			'licenser-wc-admin-style',
			LICENSER_WCLITE_PLUGIN_URL . 'assets/css/admin.css',
			[],
			LICENSER_VERSION
		);

		wp_enqueue_script(
			'licenser-wc-admin',
			LICENSER_WCLITE_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			LICENSER_VERSION,
			true
		);

		wp_localize_script(
			'licenser-wc-admin',
			'Licenser',
			[
				'rest_url' => esc_url_raw( rest_url( 'licenser/v1/' ) ),
				'root'     => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'i18n'     => [
					'select_package'   => __( 'Select Package', 'licenser' ),
					'no_package_found' => __( 'No Package Found', 'licenser' ),
					'copied'           => __( 'Copied!', 'licenser' ),
					'copy'             => __( 'Copy', 'licenser' ),
				],
			]
		);
	}

	/**
	 * Register the Licenser Licenses meta box on both legacy CPT and HPOS WooCommerce Order screens.
	 *
	 * @return void
	 */
	public function register_order_meta_box() {
		$screens = [ 'shop_order' ];

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$hpos_screen = wc_get_page_screen_id( 'shop-order' );
			if ( $hpos_screen && ! in_array( $hpos_screen, $screens, true ) ) {
				$screens[] = $hpos_screen;
			}
		}

		foreach ( $screens as $screen_id ) {
			add_meta_box(
				'licenser_order_licenses',
				__( 'Licenser Licenses', 'licenser' ),
				[ $this, 'render_order_meta_box' ],
				$screen_id,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render linked Licenser keys inside the WooCommerce Order admin screen.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Current order post or WC_Order object.
	 * @return void
	 */
	public function render_order_meta_box( $post_or_order ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = ( $post_or_order instanceof \WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		$order_id      = $order->get_id();
		$license_model = License::instance();
		$licenses      = $license_model->get_all(
			[
				'number'    => -1,
				'source'    => [ 'wc', 'woocommerce' ],
				'source_id' => $order_id,
				'orderby'   => 'id',
				'order'     => 'ASC',
			]
		);

		echo '<div class="licenser-order-metabox">';

		if ( empty( $licenses ) || ! is_array( $licenses ) ) {
			echo '<p class="licenser-order-metabox__empty">' . esc_html__( 'No license keys have been issued for this order yet.', 'licenser' ) . '</p>';
			echo '</div>';
			return;
		}

		$license_ids   = wp_list_pluck( $licenses, 'id' );
		$domain_counts = $license_model->get_active_domain_counts( $license_ids );
		$product_model = Product::instance();
		$package_model = LicensePackage::instance();

		foreach ( $licenses as $license ) {
			$lid        = absint( $license->id );
			$prod       = $product_model->get(
				absint( $license->product_id ),
				[
					'inc_stable_release' => false,
					'inc_releases'       => false,
					'inc_packages'       => false,
					'columns'            => [ 'name' ],
				]
			);
			$pkg        = absint( $license->package_id ) > 0 ? $package_model->get( absint( $license->package_id ) ) : false;
			$prod_name  = $prod && ! empty( $prod->name ) ? (string) $prod->name : __( 'Unknown Product', 'licenser' );
			$pkg_label  = $pkg && ! empty( $pkg->label ) ? (string) $pkg->label : '';
			$computed   = $license_model->get_computed_status( $license );
			$used_sites = isset( $domain_counts[ $lid ] ) ? $domain_counts[ $lid ] : 0;
			$limit      = absint( $license->domain_limit );
			$limit_text = 0 === $limit ? __( 'Unlimited', 'licenser' ) : (string) $limit;
			$edit_url   = admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . $lid );

			$badge_labels = [
				'active'        => __( 'Active', 'licenser' ),
				'expiring_soon' => __( 'Expiring Soon', 'licenser' ),
				'expired'       => __( 'Expired', 'licenser' ),
				'inactive'      => __( 'Inactive', 'licenser' ),
			];
			$badge_label  = isset( $badge_labels[ $computed ] ) ? $badge_labels[ $computed ] : __( 'Inactive', 'licenser' );
			?>
			<div class="licenser-order-key-card">
				<div class="licenser-order-key-card__head">
					<strong><?php echo esc_html( $prod_name ); ?></strong>
					<span class="licenser-wc-badge licenser-wc-badge--<?php echo esc_attr( $computed ); ?>"><?php echo esc_html( $badge_label ); ?></span>
				</div>
				<?php if ( $pkg_label ) : ?>
					<div class="licenser-order-key-card__pkg"><?php echo esc_html( $pkg_label ); ?></div>
				<?php endif; ?>
				<div class="licenser-order-key-card__key">
					<code><?php echo esc_html( (string) $license->license_key ); ?></code>
					<button type="button" class="button button-small licenser-wc-copy-btn" data-copy="<?php echo esc_attr( (string) $license->license_key ); ?>"><?php esc_html_e( 'Copy', 'licenser' ); ?></button>
				</div>
				<div class="licenser-order-key-card__foot">
					<span>
						<?php
						/* translators: 1: Active domain count, 2: Domain limit or Unlimited. */
						echo esc_html( sprintf( __( 'Sites: %1$d / %2$s', 'licenser' ), $used_sites, $limit_text ) );
						?>
					</span>
					<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Manage', 'licenser' ); ?> &rarr;</a>
				</div>
			</div>
			<?php
		}

		echo '</div>';
	}

	/**
	 * Add the Licenser tab to WooCommerce product data tabs.
	 *
	 * @param array $tabs Existing product data tabs.
	 * @return array
	 */
	public function product_data_tabs( $tabs ) {
		$tabs['licenser'] = [
			'label'    => __( 'Licenser', 'licenser' ),
			'target'   => 'licenser_product_data',
			'class'    => [ 'show_if_simple' ],
			'priority' => 21,
		];

		return $tabs;
	}

	/**
	 * Render Licenser product data panel for simple products.
	 *
	 * @return void
	 */
	public function product_data_panels() {
		$post_id = get_the_ID();
		$this->license_management_fields( $post_id );
	}

	/**
	 * Save Licenser fields for a simple product.
	 *
	 * @param int $post_id Product post ID.
	 * @return void
	 */
	public function save_fields_simple_product( $post_id ) {
		$this->save_fields( $post_id );
	}

	/**
	 * Save Licenser fields for a product variation.
	 *
	 * @param int $post_id Variation post ID.
	 * @param int $loop    Variation loop index.
	 * @return void
	 */
	public function save_fields_variable_product( $post_id, $loop ) {
		$this->save_fields( $post_id, $loop );
	}

	/**
	 * Render Licenser fields inside a WooCommerce variation panel.
	 *
	 * @param int      $loop           Variation loop index.
	 * @param array    $variation_data Variation meta data.
	 * @param \WP_Post $variation      Variation post object.
	 * @return void
	 */
	public function add_variation_custom_fields( $loop, $variation_data, $variation ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		$this->license_management_fields( $variation->ID, true, $loop );
	}

	/**
	 * Render license management form controls cleanly for simple products or variations.
	 *
	 * @param int        $post_id      Product or variation post ID.
	 * @param bool       $is_variation Whether rendering inside a variation row.
	 * @param int|string $loop         Variation loop index.
	 * @return void
	 */
	public function license_management_fields( $post_id, $is_variation = false, $loop = '' ) {
		$product_model = Product::instance();

		$products = $product_model->get_all(
			[
				'status'       => 'active',
				'number'       => -1,
				'inc_packages' => false,
				'columns'      => 'id, name, product_type',
			]
		);
		$products = is_array( $products ) ? $products : [];

		$loop_index       = $is_variation ? '_' . $loop : '';
		$container_id     = $is_variation ? 'licenser_product_data_' . $loop : 'licenser_product_data';
		$container_class  = $is_variation ? 'licenser_product_data licenser-wc-variation-box form-row form-row-full' : 'licenser_product_data licenser-wc-panel panel woocommerce_options_panel';
		$saved_enabled    = (string) get_post_meta( $post_id, 'licenser_active_licensing', true );
		$saved_type       = (string) get_post_meta( $post_id, 'licenser_product_type', true );
		$saved_product_id = (string) get_post_meta( $post_id, 'licenser_product_id', true );
		$saved_package_id = (string) get_post_meta( $post_id, 'licenser_package_id', true );

		echo '<div id="' . esc_attr( $container_id ) . '" class="' . esc_attr( $container_class ) . '">';

		if ( $is_variation ) {
			echo '<div class="licenser-wc-variation-box__header">';
			echo '<span class="dashicons dashicons-tickets-alt"></span>';
			echo '<strong>' . esc_html__( 'Licenser Settings', 'licenser' ) . '</strong>';
			echo '</div>';
		}

		echo '<div class="options_group licenser-wc-options-group">';

		woocommerce_wp_checkbox(
			[
				'id'            => 'licenser_active_licensing' . $loop_index,
				'class'         => 'licenser_wcaddon_checkbox',
				'value'         => $saved_enabled,
				'label'         => __( 'Enable License Management', 'licenser' ),
				'description'   => __( 'Automatically issue Licenser keys when this product is purchased.', 'licenser' ),
				'wrapper_class' => $is_variation ? 'form-row form-row-full licenser-wc-check-row' : '',
			]
		);

		echo '<div class="licenser_wcaddon_product_fields' . ( $is_variation ? ' licenser-wc-variation-grid' : '' ) . '">';

		woocommerce_wp_select(
			[
				'id'            => 'licenser_product_type' . $loop_index,
				'class'         => 'licenser_product_type select short',
				'value'         => $saved_type,
				'label'         => __( 'Product Type', 'licenser' ),
				'options'       => array_merge( [ '' => __( 'Please select', 'licenser' ) ], $product_model->get_types() ),
				'wrapper_class' => $is_variation ? 'form-row form-row-first' : '',
			]
		);

		$product_field_id = 'licenser_product_id' . $loop_index;
		echo '<p class="form-field ' . esc_attr( $product_field_id ) . '_field ' . esc_attr( $is_variation ? 'form-row form-row-last' : '' ) . '">';
		echo '<label for="' . esc_attr( $product_field_id ) . '">' . esc_html__( 'Licenser Product', 'licenser' ) . '</label>';
		echo '<select id="' . esc_attr( $product_field_id ) . '" name="' . esc_attr( $product_field_id ) . '" class="select short select_product_list licenser_product_id licenser_load_package">';
		echo '<option value="">' . esc_html__( 'Please select', 'licenser' ) . '</option>';
		foreach ( $products as $prod ) {
			printf(
				'<option value="%1$d" data-product-type="%2$s" %3$s>%4$s</option>',
				absint( $prod->id ),
				esc_attr( (string) $prod->product_type ),
				selected( $saved_product_id, (string) $prod->id, false ),
				esc_html( (string) $prod->name )
			);
		}
		echo '</select>';
		echo '</p>';

		woocommerce_wp_select(
			[
				'id'                => 'licenser_package_id' . $loop_index,
				'class'             => 'licenser_select_package select short',
				'value'             => $saved_package_id,
				'label'             => __( 'License Package', 'licenser' ),
				'options'           => [ '' => __( 'Please select', 'licenser' ) ],
				'wrapper_class'     => $is_variation ? 'form-row form-row-full' : '',
				'custom_attributes' => [
					'data-selected-val' => $saved_package_id,
				],
			]
		);

		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Persist Licenser product/variation metadata.
	 *
	 * Nonce verification is handled by WooCommerce prior to firing save hooks.
	 *
	 * @param int        $post_id    Product or variation post ID.
	 * @param int|string $loop_index Optional variation loop index.
	 * @return void
	 */
	public function save_fields( $post_id, $loop_index = '' ) {
		$maybe_loop = ( '' !== $loop_index ) ? '_' . $loop_index : '';

		$fields_to_update = [
			'licenser_active_licensing',
			'licenser_product_type',
			'licenser_product_id',
			'licenser_package_id',
		];

		foreach ( $fields_to_update as $field ) {
			$post_key = $field . $maybe_loop;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce before invoking product/variation save hooks.
			$field_value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
			update_post_meta( $post_id, $field, $field_value );
		}
	}
}
