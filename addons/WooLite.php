<?php

namespace Licenser\Addons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Lite built-in integration addon.
 */
class WooLite {

	/**
	 * Initialize the WooCommerce Lite addon.
	 */
	public function __construct() {
		$this->defines();

		if ( is_admin() ) {
			$this->admin_handler();
		} else {
			new WooLite\Order_Handler();
		}

		// Handle license issuance, refund suspension, and reactivation on order transitions.
		add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_change' ], 150, 3 );

		// Display license key and download link in order item emails.
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'add_license_key_in_order_email' ], 20, 4 );

		// Authenticated customer self-service domain deactivation (admin-ajax runs under is_admin()).
		add_action( 'wp_ajax_licenser_customer_deactivate_domain', [ $this, 'handle_customer_deactivate_domain' ] );
	}

	/**
	 * Define addon path and URL constants.
	 *
	 * @return void
	 */
	public function defines() {
		if ( ! defined( 'LICENSER_WCLITE_PLUGIN_URL' ) ) {
			define( 'LICENSER_WCLITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'WooLite/' );
		}

		if ( ! defined( 'LICENSER_WCLITE_PLUGIN_PATH' ) ) {
			define( 'LICENSER_WCLITE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}
	}

	/**
	 * Instantiate the WooCommerce admin product and order handler.
	 *
	 * @return WooLite\Admin_Handler
	 */
	public function admin_handler() {
		return new WooLite\Admin_Handler();
	}

	/**
	 * Handle WooCommerce order status changes (issue, suspend on refund/cancel, or restore).
	 *
	 * @param int    $order_id   WooCommerce order ID.
	 * @param string $old_status Previous order status.
	 * @param string $new_status New order status.
	 * @return void
	 */
	public function handle_order_status_change( $order_id, $old_status = '', $new_status = '' ) {
		$order_handler = new WooLite\Order_Handler();
		$order_handler->handle_order_status_change( $order_id, $old_status, $new_status );
	}

	/**
	 * Generate license keys for an order (backward-compatible wrapper).
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function generate_license_key( $order_id ) {
		$order_handler = new WooLite\Order_Handler();
		$order_handler->generate_license( $order_id );
	}

	/**
	 * Output license key details inside WooCommerce order item emails.
	 *
	 * @param int            $item_id    Order item ID.
	 * @param \WC_Order_Item $item       Order item object.
	 * @param \WC_Order      $order      Order object.
	 * @param bool           $plain_text Whether the email is plain text.
	 * @return void
	 */
	public function add_license_key_in_order_email( $item_id, $item, $order, $plain_text ) {
		$order_handler = new WooLite\Order_Handler();
		$order_handler->license_key_order_email( $item_id, $item, $order, $plain_text );
	}

	/**
	 * Handle customer self-service domain deactivation AJAX requests.
	 *
	 * @return void
	 */
	public function handle_customer_deactivate_domain() {
		$order_handler = new WooLite\Order_Handler();
		$order_handler->ajax_deactivate_domain();
	}
}
