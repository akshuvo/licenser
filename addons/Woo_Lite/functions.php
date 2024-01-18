<?php
//add_action( 'woocommerce_thankyou', 'wc_generate_license', 15 );
add_action( 'woocommerce_order_status_changed', 'wc_generate_license', 150 );
//add_action( 'woocommerce_checkout_update_order_meta', 'wc_generate_license', 15 );

function wc_generate_license( $order_id ) {

	$order = wc_get_order( $order_id );
	//$order_id = $order->get_id(); // Get the order ID
	$order_status = $order->get_status();

	// Check If order status = completed, processing
	if ( !in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
		return false;
	}

	// Create License for Each Order Items
	foreach ( $order->get_items() as $item_id => $item ) {
		// Stop creating license key fro same order
		if ( !get_post_meta( $order_id, "license_generated_item_id_{$item_id}", true ) ) {

			$product_id = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			$product_id = isset( $variation_id ) && $variation_id != "0" ? sanitize_text_field( $variation_id ) : sanitize_text_field( $product_id );

			// Check if product has license management activated
			$is_active = get_post_meta( $product_id, 'licenser_active_license_management', true );
			if( !isset( $is_active ) && $is_active != "yes" ) {
				return;
			}

			// Get Package ID
			$package_id = get_post_meta( $product_id, 'select_package', true );

			// Get the package date
			$get_package = LMFWPPT_ProductsHandler::get_package_by_package_id( $package_id );

			// Return if $get_package is empty
			if( empty( $get_package ) ) {
				return;
			}

			// Package Period
			$update_period = isset( $get_package['update_period'] ) ? sanitize_text_field( $get_package['update_period'] ) : 0;
			$domain_limit = isset( $get_package['domain_limit'] ) ? sanitize_text_field( $get_package['domain_limit'] ) : 0;

			// Generate end date
			$end_date = date( "Y-m-d H:i:s", strtotime( "+{$update_period} day", current_time('timestamp') ) );

			// License Object Class
			$licenseobj = new LMFWPPT_LicenseHandler();

			// License Key
			$license_key = LMFWPPT_LicenseHandler::generate_license_key();
			
			// Insert Data
			$post_data = array(
				'order_id'     => $order_id,
				'license_key'  => $license_key,
				'package_id'   => $package_id,
				'end_date'     => $end_date,
				'domain_limit' => $domain_limit,
			);

			// Insert License
	 		$insert_id = $licenseobj->create_license($post_data);

			// Product Slug
			$get_product = LMFWPPT_ProductsHandler::get_product_details_by_package_id( $package_id );
			$product_slug = isset( $get_product['slug'] ) ? sanitize_text_field( $get_product['slug'] ) : '';


	 		if ( !empty( $insert_id ) ) {
	 			update_post_meta( $order_id, "license_generated_item_id_{$item_id}", $insert_id );
	 			update_post_meta( $order_id, "license_generated_item_key_{$item_id}", $license_key );
	 			update_post_meta( $order_id, "license_generated_product_slug_{$item_id}", $product_slug );

	 			// Save custom meta for future usages
	 			update_post_meta( $order_id, "is_license_order", 'yes' );
	 		}
	 	}
	}

	// Send order invoice if not already sent
	if ( !get_post_meta( $order_id, "licenser_invoice_sent", true ) ) {
		lmwcext_sent_order_invoice( $order_id );
		update_post_meta( $order_id, "licenser_invoice_sent", '1' );
	}

}

// Send Order Invoice
function lmwcext_sent_order_invoice( $order_id = null ){

	if ( !$order_id || !function_exists('WC') ) {
		return;
	}

	// Check if licensed order
    $is_license_order = get_post_meta( $order_id, "is_license_order", true );
    if ( empty( $is_license_order ) || $is_license_order != "yes" ) {
        return;
    }

	// Order data saved, now get it so we can manipulate status.
	$order = wc_get_order( $order_id );

	do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

	// Send the customer invoice email.
	WC()->payment_gateways();
	WC()->shipping();
	WC()->mailer()->customer_invoice( $order );

	// Note the event.
	$order->add_order_note( __( 'Order details sent to customer via License Manager.', 'lmfwpptwcext' ), false, true );

	do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
}
