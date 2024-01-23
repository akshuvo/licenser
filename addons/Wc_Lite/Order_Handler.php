<?php 
namespace Licenser\Addons\Wc_Lite;

/**
 * WooCommerce Handler
 */
class Order_Handler{

	function __construct(){

        // Add Licensing data to cart item
        add_filter( 'woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3 );

        // Display Licensing data on cart
        add_filter( 'woocommerce_get_item_data', [$this, 'display_item_data_cart'], 25, 2 );

        // Display Licensing data on checkout
        add_action( 'woocommerce_checkout_create_order_line_item', [$this, 'display_item_data_checkout'], 10, 4 );

        // Add License Manager Menu on My Account
        add_filter ( 'woocommerce_account_menu_items', [$this, 'acc_menu_item'] );

        // Set License Manager Menu URL
        add_filter( 'woocommerce_get_endpoint_url', [$this, 'licenser_menu_url'], 10, 4 );

        // Add Shortcode
        add_shortcode( 'licenser_wc_manager', [$this, 'licenses_endpoint_content'], 30 );

        // Content Show on my-account dashboard
        if ( isset( $_GET['wc_tab'] ) && $_GET['wc_tab'] == 'license_manager' ) {
            add_action( 'woocommerce_account_dashboard', [$this, 'licenses_endpoint_content'], 30 );
        }

	}

    /**
     * Add Licensing data to cart item
     *
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     *
     * @return array
     */
    function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        
        $product_id = isset( $variation_id ) && $variation_id != "0" ? sanitize_text_field( $variation_id ) : sanitize_text_field( $product_id );

        // Check if active licensing
        $licenser_active_licensing = get_post_meta( $product_id, 'licenser_active_licensing', true );
        if( $licenser_active_licensing != "yes" ) {
            return $cart_item_data;
        } else {
            $cart_item_data['licenser_active_licensing'] = true;
        }

        // Meta Data
        $product_type = get_post_meta( $product_id, 'licenser_product_type', true );
        $licenser_product_id = get_post_meta( $product_id, 'licenser_product_id', true );
        $package_id = get_post_meta( $product_id, 'licenser_package_id', true );

        // Product instance
        $product_model = \Licenser\Models\Product::instance();

        // Get product name
        $product_name = $product_model->get( $licenser_product_id, [
            'inc_stable_release' => false,
            'inc_releases' => false,
            'inc_packages' => false,
            'columns' => 'name',
        ] );
        $product_name = isset( $product_name->name ) ? sanitize_text_field( $product_name->name ) : '';

        // Get Package
        $package = \Licenser\Models\LicensePackage::instance()->get( $package_id );

        // Package Label
        $package_label = isset( $package->label ) ? sanitize_text_field( $package->label ) : '';

        // Set Cart Item Data
        $cart_item_data['licenser_product_id'] = $licenser_product_id;
        $cart_item_data['product_type'] = $product_type;
        $cart_item_data['package_id'] = $package_id;
        $cart_item_data['licenser_product_name'] = $product_name;
        $cart_item_data['licenser_package_label'] = $package_label;
        

        return $cart_item_data;
    }


    /**
     * Display Licensing data on cart and checkout page
     *
     * @param array $item_data
     * @param array $cart_item
     *
     * @return array
     */
    function display_item_data_cart ( $item_data, $cart_item ) {

        // If hide from cart
        if( licenser_get_option('hide_wclm_info_from_cart') == "on" ) {
            return;
        }
        
        // Product ID
        $product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] != "0" ? sanitize_text_field( $cart_item['variation_id'] ) : sanitize_text_field( $cart_item['product_id'] );

        // Check if active licensing
        $licenser_active_licensing = isset( $cart_item['licenser_active_licensing'] ) ? sanitize_text_field( $cart_item['licenser_active_licensing'] ) : null;

        if( !$licenser_active_licensing ) {
            return $item_data;
        }

        $product_type = isset( $cart_item['product_type'] ) ? sanitize_text_field( $cart_item['product_type'] ) : null;
        // Type Label
        $type_label = $product_type == "theme" ? __( 'Theme', 'licenser' ) : __( 'Plugin', 'licenser' );
        if ( $product_type ) {
            $item_data[] = array(
                'key'     => __( 'Product Type', 'licenser' ),
                'value'   =>  $type_label,
                'display' => '',
            );
        }
        
        // Product Name
        if ( isset( $cart_item['licenser_product_name'] ) && $cart_item['licenser_product_name'] ) {
            $item_data[] = array(
                'key'     => sprintf( __( '%s Name', 'licenser' ), $type_label ),
                'value'   => $cart_item['licenser_product_name'],
                'display' => '',
            );
        }

        if( isset( $cart_item['licenser_package_label'] ) && $cart_item['licenser_package_label'] ) {
            $item_data[] = array(
                'key'     => __( 'Package', 'licenser' ),
                'value'   => $cart_item['licenser_package_label'],
                'display' => '',
            );
        }

        return $item_data;
    }

    /**
     * Display Licensing data on checkout pages
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    function display_item_data_checkout( $item, $cart_item_key, $values, $order ) {
        
        if( licenser_get_option('hide_wclm_info_from_ordermeta') == "on"){
            return;
        }

        $order_id = $order->get_id(); // Get the order ID

        // Check if active licensing
        $licenser_active_licensing = isset( $values['licenser_active_licensing'] ) ? sanitize_text_field( $values['licenser_active_licensing'] ) : null;

        if( !$licenser_active_licensing ) {
            return;
        }

        $product_type = isset( $values['product_type'] ) ? sanitize_text_field( $values['product_type'] ) : null;
        if($product_type){
            $item->add_meta_data( __( 'Product Type', 'licenser' ),  $product_type );
        }

        if ( isset( $values['licenser_product_name'] ) && $values['licenser_product_name'] ){
            $item->add_meta_data( __( 'Plugin Name', 'licenser' ), $values['licenser_product_name'] );
        }

        if( isset( $values['licenser_package_label'] ) && $values['licenser_package_label'] ){
            $item->add_meta_data( __( 'Package Name', 'licenser' ), $values['licenser_package_label'] );
        }
       
    }

    /**
     * Generate License Key
     *
     * @param int $order_id
     */
    function generate_license( $order_id ) {

        $order = wc_get_order( $order_id );
        //$order_id = $order->get_id(); // Get the order ID
        $order_status = $order->get_status();
    
        // Check If order status = completed, processing
        if ( !in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
            // return false;
        }
    
        // Create License for Each Order Items
        foreach ( $order->get_items() as $item_id => $item ) {
            // Stop creating license key fro same order
            // if ( !get_post_meta( $order_id, "license_generated_item_id_{$item_id}", true ) ) {
    
                $product_id = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $product_id = $variation_id != "0" ? intval( $variation_id ) : intval( $product_id );
    
                // Check if product has license management activated
                $is_active = get_post_meta( $product_id, 'licenser_active_licensing', true );
                if( $is_active != "yes" ) {
                    return;
                }
    
                // Get Package ID
                $package_id = get_post_meta( $product_id, 'licenser_package_id', true );

                // Get the package date
                $get_package = \Licenser\Models\LicensePackage::instance()->get( $package_id );

                // Return if $get_package is empty
                if( empty( $get_package ) ) {
                    return;
                }
    
                // Package Period
                $update_period = isset( $get_package->update_period ) ? intval( $get_package->update_period ) : 0;
                $domain_limit = isset( $get_package->domain_limit ) ? intval( $get_package->domain_limit ) : 0;
    
                // Calculate End Date
                $end_date = date( "Y-m-d H:i:s", strtotime( "+{$update_period} day", current_time('timestamp') ) );

                // Generate License Key
                $license_key = \Licenser\Models\License::instance()->generate_key();
    
                // Insert License
                $license_id = \Licenser\Models\License::instance()->create( [
                    'status' => 1,
                    'package_id' => $package_id,
                    'source' => 'wc',
                    'source_id' => $order_id,
                    'end_date' => $update_period == "0" ? null : $end_date,
                    'is_lifetime' => $update_period == "0" ? 1 : 0,
                    'domain_limit' => $domain_limit,
                    'license_key' => $license_key,
                ] );
    
                // Product Slug
                // $get_product = LMFWPPT_ProductsHandler::get_product_details_by_package_id( $package_id );
                $licenser_product_id = get_post_meta( $product_id, 'licenser_product_id', true );
    
    
                if ( !empty( $license_id ) ) {
                    update_post_meta( $order_id, "license_generated_item_id_{$item_id}", $license_id );
                    update_post_meta( $order_id, "license_generated_item_key_{$item_id}", $license_key );
                    update_post_meta( $order_id, "license_generated_product_slug_{$item_id}", $licenser_product_id );
    
                    // Save custom meta for future usages
                    update_post_meta( $order_id, "is_license_order", 'yes' );
                }
            // }
        }
    
        // Send order invoice if not already sent
        if ( !get_post_meta( $order_id, "licenser_invoice_sent", true ) ) {
            $this->send_order_invoice( $order_id );
            update_post_meta( $order_id, "licenser_invoice_sent", '1' );
        }
    
    }
    
    // Send Order Invoice
    function send_order_invoice( $order_id = null ){
    
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
        $order->add_order_note( __( 'Order details sent to customer via License Manager.', 'licenser' ), false, true );
    
        do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
    }


    // Attach License key in order item
    public function license_key_order_email( $item_id, $item, $order, $plain_text ){

        if( licenser_get_option('hide_wclm_info_from_ordermeta') == "on"){
            return;
        }

        // Check If order status = completed, processing
        if ( !in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
            return;
        }

        // Get the order ID
        $order_id = $order->get_id(); 

        // Get license key by id
        $license_key_id = get_post_meta( $order_id, "license_generated_item_id_{$item_id}", true );
        $license_key = get_post_meta( $order_id, "license_generated_item_key_{$item_id}", true );
        $product_slug = get_post_meta( $order_id, "license_generated_product_slug_{$item_id}", true );

        if ( empty( $license_key_id ) ) {
            return;
        }

        // Show License key
        echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <code>%s</code></li>',
            __( 'License Key', 'licenser' ),
            $license_key
        );

        // Download Link
        $download_link = add_query_arg( array(
            'product_slug' => $product_slug,
            'license_key' => $license_key,
            'action' => 'download',
        ), lmfwppt_api_url() );

        if ( $download_link ) {
            echo sprintf('<li><strong class="wc-item-meta-label">%s</strong>: <a href="%s" target="_blank">%s</a></li></ul>',
                __( 'Download Link', 'licenser' ),
                $download_link,
                __( 'Download', 'licenser' )
            );
        }

    }

    /**
     * Add License Manager Menu on My Account
     *
     * @param array $menu_links
     * @return array
     */
    public function acc_menu_item( $menu_links ){
        
        $menu_links = array_slice( $menu_links, 0, 3, true ) 
        + array( 'licenses' => 'License Manager' )
        + array_slice( $menu_links, 3, NULL, true );
        
        return $menu_links;
    }

    // License Menu Link
    function licenser_menu_url( $url, $endpoint, $value, $permalink ){
 
        if( 'licenses' === $endpoint ) {
            $url = add_query_arg([
                'wc_tab' => 'license_manager',
            ], wc_get_account_endpoint_url('dashboard') );
     
        }
        
        return $url;
    }

    // License Endpoint Content
    public function licenses_endpoint_content() {
        $shortcode = new Shortcode();
        return $shortcode->output();
    }
}