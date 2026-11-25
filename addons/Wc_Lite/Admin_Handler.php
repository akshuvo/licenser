<?php

namespace Licenser\Addons\Wc_Lite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce product data admin handler.
 */
class Admin_Handler {

	/**
	 * Register WooCommerce product admin hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields_simple_product' ], 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_variation_custom_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_fields_variable_product' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Enqueue WooCommerce product editor scripts on product screens only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

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
			]
		);
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
	 * Render license management form controls.
	 *
	 * @param int        $post_id      Product or variation post ID.
	 * @param bool       $is_variation Whether rendering inside a variation row.
	 * @param int|string $loop         Variation loop index.
	 * @return void
	 */
	public function license_management_fields( $post_id, $is_variation = false, $loop = '' ) {
		$product_model = \Licenser\Models\Product::instance();

		$products = $product_model->get_all(
			[
				'status'       => 'active',
				'number'       => -1,
				'inc_packages' => true,
				'columns'      => 'id, name, product_type',
			]
		);

		$product_list = [];
		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$product_list[ $product->id ] = $product->name;
			}
		}

		$loop_index = $is_variation ? '_' . $loop : '';

		echo '<div id="licenser_product_data" class="licenser_product_data panel woocommerce_options_panel">';

		woocommerce_wp_checkbox(
			[
				'id'          => 'licenser_active_licensing' . $loop_index,
				'class'       => 'licenser_wcaddon_checkbox',
				'value'       => get_post_meta( $post_id, 'licenser_active_licensing', true ),
				'label'       => __( 'Enable License Management', 'licenser' ),
				'description' => __( 'Enable this option to manage license for this product.', 'licenser' ),
			]
		);

		echo '<div class="licenser_wcaddon_product_fields">';

		woocommerce_wp_select(
			[
				'id'      => 'licenser_product_type' . $loop_index,
				'class'   => 'licenser_product_type',
				'value'   => get_post_meta( $post_id, 'licenser_product_type', true ),
				'label'   => __( 'Select Products Type', 'licenser' ),
				'options' => array_merge( [ '' => __( 'Please select', 'licenser' ) ], $product_model->get_types() ),
			]
		);

		woocommerce_wp_select(
			[
				'id'      => 'licenser_product_id' . $loop_index,
				'class'   => 'select_product_list licenser_product_id licenser_load_package',
				'value'   => get_post_meta( $post_id, 'licenser_product_id', true ),
				'label'   => __( 'Select Product', 'licenser' ),
				'options' => [ '' => __( 'Please select', 'licenser' ) ] + $product_list,
			]
		);

		woocommerce_wp_select(
			[
				'id'                => 'licenser_package_id' . $loop_index,
				'class'             => 'licenser_select_package',
				'value'             => get_post_meta( $post_id, 'licenser_package_id', true ),
				'label'             => __( 'Select Package', 'licenser' ),
				'options'           => [ '' => __( 'Please select', 'licenser' ) ],
				'custom_attributes' => [
					'data-selected-val' => get_post_meta( $post_id, 'licenser_package_id', true ),
				],
			]
		);

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
