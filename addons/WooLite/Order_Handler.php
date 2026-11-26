<?php

namespace Licenser\Addons\WooLite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;

/**
 * WooCommerce storefront cart, order, email, self-service AJAX, and My Account handler.
 */
class Order_Handler {

	/**
	 * Register WooCommerce frontend and My Account hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 3 );
		add_filter( 'woocommerce_get_item_data', [ $this, 'display_item_data_cart' ], 25, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'display_item_data_checkout' ], 10, 4 );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'acc_menu_item' ] );
		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'licenser_menu_url' ], 10, 4 );
		add_filter( 'woocommerce_account_menu_item_classes', [ $this, 'account_menu_item_classes' ], 10, 2 );
		add_shortcode( 'licenser_wc_manager', [ $this, 'licenses_endpoint_content' ] );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only My Account tab navigation query arg.
		$wc_tab = isset( $_GET['wc_tab'] ) ? sanitize_key( wp_unslash( $_GET['wc_tab'] ) ) : '';

		if ( 'license_manager' === $wc_tab ) {
			add_filter( 'body_class', [ $this, 'add_account_tab_body_class' ] );
			add_action( 'woocommerce_account_dashboard', [ $this, 'render_account_licenses' ], 30 );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}
	}

	/**
	 * Highlight `License Manager` in My Account navigation when `?wc_tab=license_manager` is active.
	 *
	 * @param array  $classes  Existing menu item classes.
	 * @param string $endpoint Menu item endpoint key.
	 * @return array
	 */
	public function account_menu_item_classes( $classes, $endpoint ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only My Account tab highlight.
		$wc_tab = isset( $_GET['wc_tab'] ) ? sanitize_key( wp_unslash( $_GET['wc_tab'] ) ) : '';
		if ( 'license_manager' !== $wc_tab ) {
			return $classes;
		}

		if ( 'licenses' === $endpoint && ! in_array( 'is-active', $classes, true ) ) {
			$classes[] = 'is-active';
		} elseif ( 'dashboard' === $endpoint ) {
			$classes = array_values( array_diff( $classes, [ 'is-active' ] ) );
		}

		return $classes;
	}

	/**
	 * Add a scoped body class when viewing the My Account License Manager tab.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public function add_account_tab_body_class( $classes ) {
		$classes[] = 'licenser-account-tab-active';
		return $classes;
	}

	/**
	 * Attach licensing metadata to WooCommerce cart item data.
	 *
	 * @param array $cart_item_data Existing cart item data.
	 * @param int   $product_id     WooCommerce product ID.
	 * @param int   $variation_id   WooCommerce variation ID.
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$target_id = ( ! empty( $variation_id ) && '0' !== (string) $variation_id ) ? absint( $variation_id ) : absint( $product_id );

		$licenser_active_licensing = get_post_meta( $target_id, 'licenser_active_licensing', true );
		if ( 'yes' !== $licenser_active_licensing ) {
			return $cart_item_data;
		}

		$cart_item_data['licenser_active_licensing'] = true;

		$product_type        = get_post_meta( $target_id, 'licenser_product_type', true );
		$licenser_product_id = get_post_meta( $target_id, 'licenser_product_id', true );
		$package_id          = get_post_meta( $target_id, 'licenser_package_id', true );

		$product_obj  = Product::instance()->get(
			$licenser_product_id,
			[
				'inc_stable_release' => false,
				'inc_releases'       => false,
				'inc_packages'       => false,
				'columns'            => [ 'name' ],
			]
		);
		$product_name = ( $product_obj && isset( $product_obj->name ) ) ? sanitize_text_field( $product_obj->name ) : '';

		$package       = LicensePackage::instance()->get( $package_id );
		$package_label = ( $package && isset( $package->label ) ) ? sanitize_text_field( $package->label ) : '';

		$cart_item_data['licenser_product_id']    = $licenser_product_id;
		$cart_item_data['product_type']           = $product_type;
		$cart_item_data['package_id']             = $package_id;
		$cart_item_data['licenser_product_name']  = $product_name;
		$cart_item_data['licenser_package_label'] = $package_label;

		return $cart_item_data;
	}

	/**
	 * Display licensing details in cart and checkout item summaries.
	 *
	 * @param array $item_data Existing displayed item metadata.
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	public function display_item_data_cart( $item_data, $cart_item ) {
		if ( 'on' === licenser_get_option( 'hide_wclm_info_from_cart' ) ) {
			return $item_data;
		}

		$licenser_active_licensing = ! empty( $cart_item['licenser_active_licensing'] );
		if ( ! $licenser_active_licensing ) {
			return $item_data;
		}

		$product_type = isset( $cart_item['product_type'] ) ? sanitize_text_field( $cart_item['product_type'] ) : '';
		$type_label   = ( 'theme' === $product_type ) ? __( 'Theme', 'licenser' ) : __( 'Plugin', 'licenser' );

		if ( $product_type ) {
			$item_data[] = [
				'key'     => __( 'Product Type', 'licenser' ),
				'value'   => $type_label,
				'display' => '',
			];
		}

		if ( ! empty( $cart_item['licenser_product_name'] ) ) {
			$item_data[] = [
				/* translators: %s: Product Type (Plugin or Theme). */
				'key'     => sprintf( __( '%s Name', 'licenser' ), $type_label ),
				'value'   => $cart_item['licenser_product_name'],
				'display' => '',
			];
		}

		if ( ! empty( $cart_item['licenser_package_label'] ) ) {
			$item_data[] = [
				'key'     => __( 'Package', 'licenser' ),
				'value'   => $cart_item['licenser_package_label'],
				'display' => '',
			];
		}

		return $item_data;
	}

	/**
	 * Persist licensing summary metadata onto order line items at checkout.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item values.
	 * @param \WC_Order              $order         Order object.
	 * @return void
	 */
	public function display_item_data_checkout( $item, $cart_item_key, $values, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'on' === licenser_get_option( 'hide_wclm_info_from_ordermeta' ) ) {
			return;
		}

		if ( empty( $values['licenser_active_licensing'] ) ) {
			return;
		}

		$product_type = isset( $values['product_type'] ) ? sanitize_text_field( $values['product_type'] ) : '';
		if ( $product_type ) {
			$item->add_meta_data( __( 'Product Type', 'licenser' ), $product_type );
		}

		if ( ! empty( $values['licenser_product_name'] ) ) {
			$item->add_meta_data( __( 'Plugin Name', 'licenser' ), sanitize_text_field( $values['licenser_product_name'] ) );
		}

		if ( ! empty( $values['licenser_package_label'] ) ) {
			$item->add_meta_data( __( 'Package Name', 'licenser' ), sanitize_text_field( $values['licenser_package_label'] ) );
		}
	}

	/**
	 * Orchestrate license issuance, auto-suspension on refund/cancel, and auto-restore.
	 *
	 * @param int    $order_id   WooCommerce order ID.
	 * @param string $old_status Old order status.
	 * @param string $new_status New order status.
	 * @return void
	 */
	public function handle_order_status_change( $order_id, $old_status = '', $new_status = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$status = '' !== $new_status ? $new_status : $order->get_status();

		if ( in_array( $status, [ 'processing', 'completed' ], true ) ) {
			if ( 'yes' === $order->get_meta( 'licenser_auto_suspended', true ) ) {
				$this->restore_order_licenses( $order );
			}
			$this->generate_license( $order_id );
			return;
		}

		if ( in_array( $status, [ 'refunded', 'cancelled' ], true ) && 'on' === licenser_get_option( 'auto_suspend_on_refund' ) ) {
			$this->suspend_order_licenses( $order );
		}
	}

	/**
	 * Suspend active licenses linked to a refunded or cancelled order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	protected function suspend_order_licenses( $order ) {
		$license_model = License::instance();
		$licenses      = $license_model->get_all(
			[
				'number'    => -1,
				'source'    => [ 'wc', 'woocommerce' ],
				'source_id' => $order->get_id(),
				'status'    => 1,
			]
		);

		if ( empty( $licenses ) || ! is_array( $licenses ) ) {
			return;
		}

		$ids     = wp_list_pluck( $licenses, 'id' );
		$updated = $license_model->bulk_update_status( $ids, 0 );

		if ( $updated > 0 ) {
			$order->update_meta_data( 'licenser_auto_suspended', 'yes' );
			$order->save();
			$order->add_order_note(
				sprintf(
					/* translators: %d: Number of suspended licenses. */
					_n( 'Suspended %d license via Licenser due to order refund/cancellation.', 'Suspended %d licenses via Licenser due to order refund/cancellation.', $updated, 'licenser' ),
					$updated
				)
			);
		}
	}

	/**
	 * Reactivate licenses previously auto-suspended when an order returns to processing/completed.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	protected function restore_order_licenses( $order ) {
		$license_model = License::instance();
		$licenses      = $license_model->get_all(
			[
				'number'    => -1,
				'source'    => [ 'wc', 'woocommerce' ],
				'source_id' => $order->get_id(),
				'status'    => 0,
			]
		);

		if ( ! empty( $licenses ) && is_array( $licenses ) ) {
			$ids     = wp_list_pluck( $licenses, 'id' );
			$updated = $license_model->bulk_update_status( $ids, 1 );
			if ( $updated > 0 ) {
				$order->add_order_note(
					sprintf(
						/* translators: %d: Number of reactivated licenses. */
						_n( 'Reactivated %d previously suspended license via Licenser.', 'Reactivated %d previously suspended licenses via Licenser.', $updated, 'licenser' ),
						$updated
					)
				);
			}
		}

		$order->delete_meta_data( 'licenser_auto_suspended' );
		$order->save();
	}

	/**
	 * Generate licenses for eligible items in a processing or completed order.
	 *
	 * Supports configurable `wc_quantity_mode` (`separate_keys`, `multiply_domains`, `single_legacy`)
	 * and uses WooCommerce CRUD order metadata methods for HPOS compatibility.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return bool
	 */
	public function generate_license( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		if ( ! in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ) {
			return false;
		}

		$qty_mode     = (string) licenser_get_option( 'wc_quantity_mode' );
		$qty_mode     = in_array( $qty_mode, [ 'separate_keys', 'multiply_domains', 'single_legacy' ], true ) ? $qty_mode : 'separate_keys';
		$meta_updated = false;

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}

			// Skip items that already have a generated license.
			if ( $order->get_meta( "license_generated_item_id_{$item_id}", true ) ) {
				continue;
			}

			$product_id   = $item->get_product_id();
			$variation_id = method_exists( $item, 'get_variation_id' ) ? $item->get_variation_id() : 0;
			$target_id    = ( ! empty( $variation_id ) && '0' !== (string) $variation_id ) ? absint( $variation_id ) : absint( $product_id );

			if ( 'yes' !== get_post_meta( $target_id, 'licenser_active_licensing', true ) ) {
				continue;
			}

			$package_id  = get_post_meta( $target_id, 'licenser_package_id', true );
			$get_package = LicensePackage::instance()->get( $package_id );

			if ( empty( $get_package ) ) {
				continue;
			}

			$update_period       = isset( $get_package->update_period ) ? absint( $get_package->update_period ) : 0;
			$base_domain_limit   = isset( $get_package->domain_limit ) ? absint( $get_package->domain_limit ) : 0;
			$end_date            = ( $update_period > 0 ) ? licenser_date( 'Y-m-d H:i:s', time() + ( $update_period * DAY_IN_SECONDS ) ) : null;
			$licenser_product_id = absint( get_post_meta( $target_id, 'licenser_product_id', true ) );
			$item_qty            = method_exists( $item, 'get_quantity' ) ? max( 1, absint( $item->get_quantity() ) ) : 1;

			$keys_to_create = ( 'separate_keys' === $qty_mode ) ? $item_qty : 1;
			$domain_limit   = ( 'multiply_domains' === $qty_mode && $base_domain_limit > 0 ) ? ( $base_domain_limit * $item_qty ) : $base_domain_limit;

			$created_ids  = [];
			$created_keys = [];

			for ( $i = 0; $i < $keys_to_create; $i++ ) {
				$license_key = License::instance()->generate_key();

				$license_id = License::instance()->create(
					[
						'status'       => 1,
						'product_id'   => $licenser_product_id,
						'package_id'   => absint( $package_id ),
						'source'       => 'wc',
						'source_id'    => $order->get_id(),
						'end_date'     => $end_date,
						'is_lifetime'  => ( 0 === $update_period ) ? 1 : 0,
						'domain_limit' => $domain_limit,
						'license_key'  => $license_key,
					]
				);

				if ( ! empty( $license_id ) ) {
					$created_ids[]  = $license_id;
					$created_keys[] = $license_key;
				}
			}

			if ( ! empty( $created_ids ) ) {
				$order->update_meta_data( "license_generated_item_id_{$item_id}", $created_ids[0] );
				$order->update_meta_data( "license_generated_item_key_{$item_id}", $created_keys[0] );
				if ( count( $created_ids ) > 1 ) {
					$order->update_meta_data( "license_generated_item_ids_{$item_id}", $created_ids );
					$order->update_meta_data( "license_generated_item_keys_{$item_id}", $created_keys );
				}
				$order->update_meta_data( "licenser_product_id_{$item_id}", $licenser_product_id );
				$order->update_meta_data( 'is_license_order', 'yes' );
				$meta_updated = true;
			}
		}

		if ( $meta_updated ) {
			$order->save();
		}

		// Send customer invoice email once for licensed orders.
		if ( 'yes' === $order->get_meta( 'is_license_order', true ) && ! $order->get_meta( 'licenser_invoice_sent', true ) ) {
			$this->send_order_invoice( $order->get_id() );
			$order->update_meta_data( 'licenser_invoice_sent', '1' );
			$order->save();
		}

		return true;
	}

	/**
	 * Send the WooCommerce customer invoice email for a licensed order.
	 *
	 * @param int|null $order_id WooCommerce order ID.
	 * @return void
	 */
	public function send_order_invoice( $order_id = null ) {
		if ( ! $order_id || ! function_exists( 'WC' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( 'yes' !== $order->get_meta( 'is_license_order', true ) ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook.
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $order );

		$order->add_order_note( __( 'Order details sent to customer via Licenser.', 'licenser' ), false, true );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook.
		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
	}

	/**
	 * Render generated license key(s) and download link under order item meta in emails.
	 *
	 * @param int            $item_id    Order item ID.
	 * @param \WC_Order_Item $item       Order item object.
	 * @param \WC_Order      $order      Order object.
	 * @param bool           $plain_text Whether the email is plain text.
	 * @return void
	 */
	public function license_key_order_email( $item_id, $item, $order, $plain_text ) {
		if ( 'on' === licenser_get_option( 'hide_wclm_info_from_ordermeta' ) ) {
			return;
		}

		if ( ! $order || ! in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ) {
			return;
		}

		$license_key_id = $order->get_meta( "license_generated_item_id_{$item_id}", true );
		if ( empty( $license_key_id ) ) {
			return;
		}

		$product_id   = $order->get_meta( "licenser_product_id_{$item_id}", true );
		$multi_keys   = $order->get_meta( "license_generated_item_keys_{$item_id}", true );
		$license_keys = [];

		if ( is_array( $multi_keys ) && ! empty( $multi_keys ) ) {
			$license_keys = array_values( array_filter( array_map( 'sanitize_text_field', $multi_keys ) ) );
		} else {
			$single_key = $order->get_meta( "license_generated_item_key_{$item_id}", true );
			if ( empty( $single_key ) ) {
				$license_row = License::instance()->get( absint( $license_key_id ) );
				if ( $license_row && ! empty( $license_row->license_key ) ) {
					$single_key = $license_row->license_key;
					if ( empty( $product_id ) && ! empty( $license_row->product_id ) ) {
						$product_id = $license_row->product_id;
					}
				}
			}
			if ( ! empty( $single_key ) ) {
				$license_keys[] = sanitize_text_field( (string) $single_key );
			}
		}

		if ( empty( $license_keys ) ) {
			return;
		}

		$product_uuid = '';
		if ( ! empty( $product_id ) ) {
			$product = Product::instance()->get(
				absint( $product_id ),
				[
					'inc_stable_release' => false,
					'inc_releases'       => false,
					'inc_packages'       => false,
				]
			);

			if ( $product && ! empty( $product->uuid ) ) {
				$product_uuid = (string) $product->uuid;
			}
		}

		$download_link = $product_uuid ? licenser_product_download_url( $product_uuid, $license_keys[0] ) : '';

		if ( $plain_text ) {
			foreach ( $license_keys as $idx => $key_val ) {
				$label = count( $license_keys ) > 1
					/* translators: %d: License number index. */
					? sprintf( __( 'License Key #%d', 'licenser' ), $idx + 1 )
					: __( 'License Key', 'licenser' );
				echo "\n" . esc_html( $label ) . ': ' . esc_html( $key_val );
			}
			if ( $download_link ) {
				echo "\n" . esc_html__( 'Download Link', 'licenser' ) . ': ' . esc_url_raw( $download_link );
			}
			echo "\n";
			return;
		}

		echo '<ul class="wc-item-meta">';
		foreach ( $license_keys as $idx => $key_val ) {
			$label = count( $license_keys ) > 1
				/* translators: %d: License number index. */
				? sprintf( __( 'License Key #%d', 'licenser' ), $idx + 1 )
				: __( 'License Key', 'licenser' );
			printf(
				'<li><strong class="wc-item-meta-label">%1$s</strong>: <code>%2$s</code></li>',
				esc_html( $label ),
				esc_html( $key_val )
			);
		}

		if ( $download_link ) {
			printf(
				'<li><strong class="wc-item-meta-label">%1$s</strong>: <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></li>',
				esc_html__( 'Download Link', 'licenser' ),
				esc_url( $download_link ),
				esc_html__( 'Download', 'licenser' )
			);
		}
		echo '</ul>';
	}

	/**
	 * Handle authenticated customer self-service domain deactivation via AJAX.
	 *
	 * @return void
	 */
	public function ajax_deactivate_domain() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Please sign in to manage your activations.', 'licenser' ) ], 403 );
		}

		if ( ! check_ajax_referer( 'licenser_customer_deactivate_domain', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed. Please refresh and try again.', 'licenser' ) ], 403 );
		}

		if ( 'on' !== licenser_get_option( 'allow_customer_domain_deactivation' ) ) {
			wp_send_json_error( [ 'message' => __( 'Self-service domain deactivation is currently disabled.', 'licenser' ) ], 403 );
		}

		$domain_id = isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0;
		if ( $domain_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid domain ID.', 'licenser' ) ], 400 );
		}

		$license_model = License::instance();
		$domain_row    = $license_model->get_domain_by_id( $domain_id );
		if ( ! $domain_row ) {
			wp_send_json_error( [ 'message' => __( 'Activated domain not found.', 'licenser' ) ], 404 );
		}

		$license = $license_model->get( absint( $domain_row->license_id ) );
		if ( ! $license ) {
			wp_send_json_error( [ 'message' => __( 'Associated license not found.', 'licenser' ) ], 404 );
		}

		$source    = strtolower( trim( (string) $license->source ) );
		$source_id = absint( $license->source_id );
		if ( ! in_array( $source, [ 'wc', 'woocommerce' ], true ) || $source_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not authorized to modify this activation.', 'licenser' ) ], 403 );
		}

		$order = wc_get_order( $source_id );
		if ( ! $order || (int) $order->get_customer_id() !== get_current_user_id() ) {
			wp_send_json_error( [ 'message' => __( 'You do not own the order linked to this license.', 'licenser' ) ], 403 );
		}

		$license_model->delete_domain( $domain_id );

		/**
		 * Fires after a customer deactivates a domain from My Account.
		 *
		 * @param object $license License record.
		 * @param string $url     Deactivated domain string.
		 */
		do_action( 'licenser_license_deactivated', $license, (string) $domain_row->domain );

		$active_count = (int) $license_model->get_domains(
			[
				'license_id'  => $license->id,
				'status'      => 1,
				'count_total' => true,
			]
		);

		wp_send_json_success(
			[
				'message'      => __( 'Site deactivated successfully. Activation slot is now available.', 'licenser' ),
				'domain_id'    => $domain_id,
				'active_count' => $active_count,
				'domain_limit' => absint( $license->domain_limit ),
			]
		);
	}

	/**
	 * Add the License Manager item to WooCommerce My Account navigation.
	 *
	 * @param array $menu_links Existing menu items.
	 * @return array
	 */
	public function acc_menu_item( $menu_links ) {
		return array_slice( $menu_links, 0, 3, true )
			+ [ 'licenses' => __( 'License Manager', 'licenser' ) ]
			+ array_slice( $menu_links, 3, null, true );
	}

	/**
	 * Map the `licenses` My Account endpoint to the dashboard tab URL.
	 *
	 * @param string $url       Endpoint URL.
	 * @param string $endpoint  Endpoint slug.
	 * @param string $value     Endpoint value.
	 * @param string $permalink Base permalink.
	 * @return string
	 */
	public function licenser_menu_url( $url, $endpoint, $value, $permalink ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'licenses' === $endpoint ) {
			$url = add_query_arg(
				[ 'wc_tab' => 'license_manager' ],
				wc_get_account_endpoint_url( 'dashboard' )
			);
		}

		return $url;
	}

	/**
	 * Enqueue frontend styles and scripts for the My Account License Manager tab.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		self::enqueue_portal_assets();
	}

	/**
	 * Enqueue and localize frontend portal assets safely (idempotent).
	 *
	 * @return void
	 */
	public static function enqueue_portal_assets() {
		wp_enqueue_style(
			'licenser-wclite-styles',
			LICENSER_WCLITE_PLUGIN_URL . 'assets/css/styles.css',
			[],
			LICENSER_VERSION
		);

		wp_enqueue_script(
			'licenser-wclite-scripts',
			LICENSER_WCLITE_PLUGIN_URL . 'assets/js/scripts.js',
			[ 'jquery' ],
			LICENSER_VERSION,
			true
		);

		wp_localize_script(
			'licenser-wclite-scripts',
			'LicenserWc',
			[
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'licenser_customer_deactivate_domain' ),
				'allow_deactivation' => 'on' === licenser_get_option( 'allow_customer_domain_deactivation' ),
				'i18n'               => [
					'copied'       => __( 'Copied!', 'licenser' ),
					'copy'         => __( 'Copy', 'licenser' ),
					'deactivating' => __( 'Deactivating...', 'licenser' ),
					'deactivate'   => __( 'Deactivate', 'licenser' ),
					'no_domains'   => __( 'No sites currently activated for this license.', 'licenser' ),
					'generic_err'  => __( 'Could not deactivate site. Please try again.', 'licenser' ),
				],
			]
		);
	}

	/**
	 * Shortcode callback returning the customer License Manager HTML.
	 *
	 * @return string
	 */
	public function licenses_endpoint_content() {
		$shortcode = new Shortcode();
		return $shortcode->output();
	}

	/**
	 * Action callback echoing the customer License Manager inside My Account dashboard.
	 *
	 * @return void
	 */
	public function render_account_licenses() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside Shortcode::output().
		echo $this->licenses_endpoint_content();
	}
}
