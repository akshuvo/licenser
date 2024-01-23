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

        

        //Add A Menu on My Account menu tab 
        add_filter ( 'woocommerce_account_menu_items', [$this, 'licenses_link_my_account'] );

        // hook the external URL
        add_filter( 'woocommerce_get_endpoint_url', [$this, 'hook_endpoint_link'], 10, 4 );


        // Contents Shortcode
        // add_action( 'woocommerce_account_licenses_endpoint', [$this, 'licenses_endpoint_content'], 30 );
        add_shortcode( 'wc_license_manager', [$this, 'licenses_endpoint_content'], 30 );

        // Content Show on my-account dashboard
        if ( isset( $_GET['wc_tab'] ) && $_GET['wc_tab'] == 'license_manager' ) {
            add_action( 'woocommerce_account_dashboard', [$this, 'licenses_endpoint_content'], 30 );
        }
        


  
        add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'handle_license_query_var' ], 20, 2 );

     

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


                // error_log( print_r( $get_package, true ) );

                // exit;
    
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
    


    /**
     * Handle a custom 'is_license_order' query var to get orders with the 'customvar' meta.
     * @param array $query - Args for WP_Query.
     * @param array $query_vars - Query vars from WC_Order_Query.
     * @return array modified $query
     */
    function handle_license_query_var( $query, $query_vars ) {
        if ( ! empty( $query_vars['is_license_order'] ) ) {
            $query['meta_query'][] = array(
                'key' => 'is_license_order',
                'value' => esc_attr( $query_vars['is_license_order'] ),
            );
        }

        return $query;
    }

    /*
    *
    * Add A Menu on My Account menu tab
    */
    public function licenses_link_my_account( $menu_links ){
        
        $menu_links = array_slice( $menu_links, 0, 3, true ) 
        + array( 'licenses' => 'License Manager' )
        + array_slice( $menu_links, 3, NULL, true );
        
        return $menu_links;
    }

    // License Menu Link
    function hook_endpoint_link( $url, $endpoint, $value, $permalink ){
 
        if( 'licenses' === $endpoint ) {
     
            // ok, here is the place for your custom URL, it could be external
            $url = add_query_arg([
                'wc_tab' => 'license_manager',
            ], wc_get_account_endpoint_url('dashboard') );
     
        }
        return $url;
     
    }

    // License Endpoint Content
    public function licenses_endpoint_content() {

        // License Class
        $license_handler = new LMFWPPT_LicenseHandler();

        // Get latest 3 orders.
        $args = array(
            'limit' => -1,
            'customer_id' => get_current_user_id(),
            'return' => 'ids',
            'is_license_order' => 'yes'
        );
        $orders = wc_get_orders( $args );
        $get_licenses = $license_handler->get_licenses_by_order_ids($orders);

        echo sprintf("<h4>%s</h4>", __('License Manager', 'licenser'));

        if ( !empty( $get_licenses ) ) : ?>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
            <thead>
                <tr>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Item', 'licenser'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('License Details', 'licenser'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Actions', 'licenser'); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $get_licenses as $key => $license ) : 

                    $license_id = isset( $license['id'] ) ? sanitize_text_field( $license['id'] ) : 0;
                    $package_id = isset( $license['package_id'] ) ? sanitize_text_field( $license['package_id'] ) : null;
                    $license_key = isset( $license['license_key'] ) ? sanitize_text_field( $license['license_key'] ) : null;
                    $expire_date = isset( $license['end_date'] ) ? sanitize_text_field( $license['end_date'] ) : '';
                    $is_lifetime = isset( $license['is_lifetime'] ) ? sanitize_text_field( $license['is_lifetime'] ) : "0";
                    $status = isset( $license['status'] ) ? sanitize_text_field( $license['status'] ) : '0';

                    // Domain Limit
                    $domain_limit = isset($license['domain_limit']) ? sanitize_text_field($license['domain_limit']) : 0;

                    // Check if lifetime
                    if ( $is_lifetime != "0" ) {
                        $expire_date = esc_html('Lifetime', 'licenser');
                    } else {
                        $expire_date = date('j F Y',strtotime($expire_date));
                    }


                    if ( !$package_id ) {
                        continue;
                    }

                    global $wpdb;
                    $get_product = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}lmfwppt_license_packages as lp INNER JOIN {$wpdb->prefix}lmfwppt_products as p ON p.id = lp.product_id WHERE lp.package_id = %s", $package_id), ARRAY_A );

                    $product_name = isset( $get_product['name'] ) ? sanitize_text_field( $get_product['name'] ) : '';
                    $pack_label = isset( $get_product['label'] ) ? sanitize_text_field( $get_product['label'] ) : '';
                    
                    $product_type = isset($get_product['product_type']) ? sanitize_text_field($get_product['product_type']) : '';

                    

                    if ( $domain_limit == "0" ) {
                       $domain_limit = esc_html('Unlimited', 'licenser');
                    }

                    // Download Link
                    $download_link = add_query_arg( array(
                        'product_slug' => isset( $get_product['slug'] ) ? $get_product['slug'] : "",
                        'license_key' => $license_key,
                        'action' => 'download',
                    ), lmfwppt_api_url() );

                    // Get Domains 
                    $get_domains = $license_handler->get_domains( $license_id );

                    //ppr($get_product);
                    ?>
                    <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                        
                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Item', 'licenser'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html( $product_name, "licenser" ); ?></strong></div>
                            <div class="license_key">
                                <input type="text" value="<?php echo esc_attr($license_key, "licenser"); ?>" readonly="readonly" style=" width: 100%; ">
                            </div>

                            <!-- activations button -->
                            <div class="show_manage_activations_details">
                                <a><?php echo esc_html__( "Manage Activations", "licenser" ); ?></a>
                            </div>

                            <!-- activations value show -->
                             
                            <div class="manage-activations">

                                <a class="activations-close-modal" title="Close">&times;</a>

                                <h5 style="margin:0px;"><?php echo esc_html__( "Manage License:", "licenser" ); ?></h5>

                                <ul class="am-list-ul">
                                    <li><strong><?php echo esc_html__( "License Key", "licenser" ); ?></strong>: <code><?php echo esc_html( $license_key ); ?></code></li>
                                    <li><strong><?php echo esc_html__( "Product", "licenser" ); ?></strong>: <?php echo esc_html( $product_name ); ?></li>
                                </ul>
                              
                                

                                <table style="border-width: 1px 1px 1px 1px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo esc_html__( "Site URL", "licenser" ); ?></th>
                                            <th><?php echo esc_html__( "Status", "licenser" ); ?></th>
                                        </tr>
                                    </thead>
                                   <tbody>
                                    <?php if( !empty( $get_domains ) ) :
                                        foreach( $get_domains as $domain ):
                                            $key = isset( $domain['id'] ) ? sanitize_text_field( $domain['id'] ) : '';
                                            $url = isset( $domain['domain'] ) ? sanitize_text_field( $domain['domain'] ) : '';
                                            $status = isset( $domain['status'] ) ? sanitize_text_field( $domain['status'] ) : '1';
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo esc_html( $url ); ?> 
                                                    <a target="_blank" href="<?php echo esc_url( $url ); ?>">↗</a>
                                                </td>
                                                <td><?php echo $status == "1" ? esc_html__( "Active", "licenser" ) : esc_html__( "Inactive", "licenser" ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="2"><?php echo esc_html__( "No Domains", "licenser" ); ?></td> 
                                    </tr>
                                    <?php endif; ?>  
                                       
                                   </tbody>
                                </table>
                            </div>
                            <div class="am-overlay"></div>

                        </td>

                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('License Details', 'licenser'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html__( "Product Name",'licenser' ); ?>:</strong> <?php echo esc_html( $product_name ); ?> (<?php echo esc_html($pack_label); ?>) </div>
                            <div class="license_details">
                                <strong><?php echo esc_html__( "Domain Limit", 'licenser' ); ?>:</strong> <?php echo esc_html($domain_limit)?><br>
                                <strong><?php echo esc_html__( "Product Type", 'licenser' ); ?>:</strong> <?php echo esc_html(ucwords($product_type)); ?> <br>
                                <strong><?php echo esc_html__( "Expires", 'licenser' ); ?>:</strong> <?php echo esc_html($expire_date); ?>
                            </div>
                        </td>

                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Actions', 'licenser'); ?>">
                            <a target="_blank" href="<?php echo esc_url( $download_link ); ?>" class="woocommerce-button button view"><?php esc_html_e('Download', 'licenser'); ?></a>                                                 
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: 
            $shop_page_url = get_permalink( wc_get_page_id( 'shop' ) );
            $product_page_url = apply_filters( 'license_manager_products_page_url', $shop_page_url );

            ?>
            <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                <a class="woocommerce-Button button" href="<?php echo esc_url( $product_page_url ); ?>"><?php esc_html_e('Browse products', 'licenser'); ?></a>
                <?php esc_html_e('No licenses available yet.', 'licenser'); ?>
            </div>
        <?php  endif;
    }


    // add_license_key_in_order_email
    public function add_license_key_in_order_email( $order, $sent_to_admin, $plain_text, $email ){

        // if( licenser_get_option('hide_wclm_info_from_ordermeta') == "on"){
        //     return;
        // }

        // // Check If order status = completed, processing
        // if ( !in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
        //     return;
        // }

        // Get the order ID
        $order_id = $order->get_id(); 

        // Create License for Each Order Items
        foreach ( $order->get_items() as $item_id => $item ) {

            // Get license key by id
            $license_key_id = get_post_meta( $order_id, "license_generated_item_id_{$item_id}", true );
            $license_key = get_post_meta( $order_id, "license_generated_item_key_{$item_id}", true );
            $product_slug = get_post_meta( $order_id, "license_generated_product_slug_{$item_id}", true );

            if ( empty( $license_key_id ) ) {
                // return;
            }

            // Download Link
            $download_link = add_query_arg( array(
                'product_slug' => $product_slug,
                'license_key' => $license_key,
                'action' => 'download',
            ), lmfwppt_api_url() );

            // Show License key
            echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <code>%s</code></li></ul>',
                __( 'License Key', 'licenser' ),
                $license_key
            );

            if ( $download_link ) {
                echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <a href="%s" target="_blank">%s</a></li></ul>',
                    __( 'Download Link', 'licenser' ),
                    $download_link,
                    __( 'Download', 'licenser' )
                );
            }

        }

    }

    // Attach License key in order item
    public function license_key_order_email( $item_id, $item, $order, $plain_text ){

        if( licenser_get_option('hide_wclm_info_from_ordermeta') == "on"){
            // return;
        }

        // Check If order status = completed, processing
        if ( !in_array( $order->get_status(), [ 'processing', 'completed' ] ) ) {
            // return;
        }

        // Get the order ID
        $order_id = $order->get_id(); 

        // Get license key by id
        $license_key_id = get_post_meta( $order_id, "license_generated_item_id_{$item_id}", true );
        $license_key = get_post_meta( $order_id, "license_generated_item_key_{$item_id}", true );
        $product_slug = get_post_meta( $order_id, "license_generated_product_slug_{$item_id}", true );

        if ( empty( $license_key_id ) ) {
            // return;
        }

        // Show License key
        echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <code>%s</code></li></ul>',
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
            echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <a href="%s" target="_blank">%s</a></li></ul>',
                __( 'Download Link', 'licenser' ),
                $download_link,
                __( 'Download', 'licenser' )
            );
        }

    }


}