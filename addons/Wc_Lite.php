<?php

namespace Licenser\Addons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Lite built-in integration addon.
 */
class Wc_Lite {

	/**
	 * Initialize the WooCommerce Lite addon.
	 */
	public function __construct() {
		$this->defines();

		if ( is_admin() ) {
			$this->admin_handler();
		} else {
			new Wc_Lite\Order_Handler();
		}

		// Generate license keys when order status changes.
		add_action( 'woocommerce_order_status_changed', [ $this, 'generate_license_key' ], 150 );

		// Display license key and download link in order item emails.
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'add_license_key_in_order_email' ], 20, 4 );
	}

	/**
	 * Define addon path and URL constants.
	 *
	 * @return void
	 */
	public function defines() {
		if ( ! defined( 'LICENSER_WCLITE_PLUGIN_URL' ) ) {
			define( 'LICENSER_WCLITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'Wc_Lite/' );
		}

		if ( ! defined( 'LICENSER_WCLITE_PLUGIN_PATH' ) ) {
			define( 'LICENSER_WCLITE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}
	}

	/**
	 * Instantiate the WooCommerce admin product handler.
	 *
	 * @return Wc_Lite\Admin_Handler
	 */
	public function admin_handler() {
		return new Wc_Lite\Admin_Handler();
	}

	/**
	 * Generate license keys for an order on status transition.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function generate_license_key( $order_id ) {
		$order_handler = new Wc_Lite\Order_Handler();
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
		$order_handler = new Wc_Lite\Order_Handler();
		$order_handler->license_key_order_email( $item_id, $item, $order, $plain_text );
	}
}
