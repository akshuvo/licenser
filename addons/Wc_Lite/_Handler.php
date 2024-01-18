<?php 
namespace Licenser\Addons\Wc_Lite;
/**
 * WooCommerce Handler
 */
class Admin_Handler{

	function __construct(){



        

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
        
        //Add text to cart item.
        add_filter( 'woocommerce_add_cart_item_data', [$this, 'lmfwpptwcext_add_engraving_text_to_cart_item'], 10, 3 );

        //Display custom data on cart and checkout page
        add_filter( 'woocommerce_get_item_data', [$this, 'lmfwpptwcext_get_item_data'], 25, 2 );

        add_action( 'woocommerce_checkout_create_order_line_item', [$this, 'products_order_items'], 10, 4 );

        //wp_ajax product package load ajax
		add_action( 'wp_ajax_get_product_package_wc', [ $this, 'get_product_package' ] );

        add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ $this, 'handle_license_query_var' ], 20, 2 );

        // Add License key in the order email
        add_action( 'woocommerce_order_item_meta_end', [$this, 'license_key_order_email'], 1000, 4 );

	}



    /*
    *
    * product list select option pass
    */
    public static function lmfwpptwcext_generate( $data_arr ){
        $return = array();
        $return[''] = __('Select Product','lmfwpptwcext');
        foreach ( $data_arr as $data ) {
            $return[$data->id] = $data->name;
        }
        return $return;
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

        echo sprintf("<h4>%s</h4>", __('License Manager', 'lmfwpptwcext'));

        if ( !empty( $get_licenses ) ) : ?>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
            <thead>
                <tr>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Item', 'lmfwpptwcext'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('License Details', 'lmfwpptwcext'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Actions', 'lmfwpptwcext'); ?></span></th>
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
                        $expire_date = esc_html('Lifetime', 'lmfwpptwcext');
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
                       $domain_limit = esc_html('Unlimited', 'lmfwpptwcext');
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
                        
                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Item', 'lmfwpptwcext'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html( $product_name, "lmfwpptwcext" ); ?></strong></div>
                            <div class="license_key">
                                <input type="text" value="<?php echo esc_attr($license_key, "lmfwpptwcext"); ?>" readonly="readonly" style=" width: 100%; ">
                            </div>

                            <!-- activations button -->
                            <div class="show_manage_activations_details">
                                <a><?php echo esc_html__( "Manage Activations", "lmfwpptwcext" ); ?></a>
                            </div>

                            <!-- activations value show -->
                             
                            <div class="manage-activations">

                                <a class="activations-close-modal" title="Close">&times;</a>

                                <h5 style="margin:0px;"><?php echo esc_html__( "Manage License:", "lmfwpptwcext" ); ?></h5>

                                <ul class="am-list-ul">
                                    <li><strong><?php echo esc_html__( "License Key", "lmfwpptwcext" ); ?></strong>: <code><?php echo esc_html( $license_key ); ?></code></li>
                                    <li><strong><?php echo esc_html__( "Product", "lmfwpptwcext" ); ?></strong>: <?php echo esc_html( $product_name ); ?></li>
                                </ul>
                              
                                

                                <table style="border-width: 1px 1px 1px 1px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo esc_html__( "Site URL", "lmfwpptwcext" ); ?></th>
                                            <th><?php echo esc_html__( "Status", "lmfwpptwcext" ); ?></th>
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
                                                <td><?php echo $status == "1" ? esc_html__( "Active", "lmfwpptwcext" ) : esc_html__( "Inactive", "lmfwpptwcext" ); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="2"><?php echo esc_html__( "No Domains", "lmfwpptwcext" ); ?></td> 
                                    </tr>
                                    <?php endif; ?>  
                                       
                                   </tbody>
                                </table>
                            </div>
                            <div class="am-overlay"></div>

                        </td>

                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('License Details', 'lmfwpptwcext'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html__( "Product Name",'lmfwpptwcext' ); ?>:</strong> <?php echo esc_html( $product_name ); ?> (<?php echo esc_html($pack_label); ?>) </div>
                            <div class="license_details">
                                <strong><?php echo esc_html__( "Domain Limit", 'lmfwpptwcext' ); ?>:</strong> <?php echo esc_html($domain_limit)?><br>
                                <strong><?php echo esc_html__( "Product Type", 'lmfwpptwcext' ); ?>:</strong> <?php echo esc_html(ucwords($product_type)); ?> <br>
                                <strong><?php echo esc_html__( "Expires", 'lmfwpptwcext' ); ?>:</strong> <?php echo esc_html($expire_date); ?>
                            </div>
                        </td>

                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Actions', 'lmfwpptwcext'); ?>">
                            <a target="_blank" href="<?php echo esc_url( $download_link ); ?>" class="woocommerce-button button view"><?php esc_html_e('Download', 'lmfwpptwcext'); ?></a>                                                 
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
                <a class="woocommerce-Button button" href="<?php echo esc_url( $product_page_url ); ?>"><?php esc_html_e('Browse products', 'lmfwpptwcext'); ?></a>
                <?php esc_html_e('No licenses available yet.', 'lmfwpptwcext'); ?>
            </div>
        <?php  endif;
    }

    /**
     * Add text to cart item.
     *
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     *
     * @return array
     */
    function lmfwpptwcext_add_engraving_text_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        
        $product_id = isset( $variation_id ) && $variation_id != "0" ? sanitize_text_field( $variation_id ) : sanitize_text_field( $product_id );

        $is_active = get_post_meta( $product_id, 'licenser_active_license_management', true );
        if( !$is_active ) {
            return $cart_item_data;
        }else{
            $cart_item_data['is_active'] = $is_active;
        }

        $product_type = get_post_meta( $product_id, 'licenser_product_type', true );
        if( isset( $product_type ) ) {
            $cart_item_data['product_type'] = $product_type;
        }

        $theme_id = get_post_meta( $product_id, 'theme_product_list', true );
        if( isset( $theme_id ) ) {
            $cart_item_data['theme_id'] = $theme_id;
        }

        $plugin_id = get_post_meta( $product_id, 'plugin_product_list', true );
        if( isset( $plugin_id ) ) {
            $cart_item_data['plugin_id'] = $plugin_id;
        }

        $package_id = get_post_meta( $product_id, 'select_package', true );
        if( isset( $package_id ) ) {
            $cart_item_data['package_id'] = $package_id;
        }

        return $cart_item_data;
    }

    /*
    * Display custom data on cart and checkout page.
    *
    */
    function lmfwpptwcext_get_item_data ( $item_data, $cart_item ) {

        if( licenser_get_option('hide_wclm_info_from_cart') == "on" ) {
            return;
        }
         
        $product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] != "0" ? sanitize_text_field( $cart_item['variation_id'] ) : sanitize_text_field( $cart_item['product_id'] );

        $is_active = isset($cart_item['is_active']) ? sanitize_text_field($cart_item['is_active']) : null;

        if( !$is_active ) {
            return $item_data;
        }

        $product_type = isset( $cart_item['product_type'] ) ? sanitize_text_field( $cart_item['product_type'] ) : null;
        if ( $product_type ) {
            $item_data[] = array(
                'key'     => __( 'Product Type', 'lmfwpptwcext' ),
                'value'   =>  ucwords($product_type),
                'display' => '',
            );
        }

        
        if ( $product_type == "theme" && isset( $cart_item['theme_id'] ) && $cart_item['theme_id'] ) {
            $theme_name = LMFWPPT_ProductsHandler::get_product($cart_item['theme_id'])['name'];
            $item_data[] = array(
                'key'     => __( 'Theme Name', 'lmfwpptwcext' ),
                'value'   => $theme_name,
                'display' => '',
            );
        }
        
         
        if ( $product_type == "plugin" && isset( $cart_item['plugin_id'] ) && $cart_item['plugin_id'] ) {
            $plugin_name = LMFWPPT_ProductsHandler::get_product($cart_item['plugin_id'])['name'];
                $item_data[] = array(
                'key'     => __( 'Plugin Name', 'lmfwpptwcext' ),
                'value'   => $plugin_name,
                'display' => '',
            );
        }

        if( isset( $cart_item['package_id'] ) && $cart_item['package_id'] ) {
            $package_name = LMFWPPT_ProductsHandler::get_package_name($cart_item['package_id']);
               
                $item_data[] = array(
                'key'     => __( 'Package', 'lmfwpptwcext' ),
                'value'   => $package_name,
                'display' => '',
            );
        }

        return $item_data;
    }


    /**
     * Add text to order.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    function products_order_items( $item, $cart_item_key, $values, $order ) {
        
        if( licenser_get_option('hide_wclm_info_from_ordermeta') == "on"){
            return;
        }

        $order_id = $order->get_id(); // Get the order ID

        $is_active = isset($values['is_active']) ? sanitize_text_field($values['is_active']) : null;

        if( !$is_active ) {
            return;
        }

        $product_type = isset( $values['product_type'] ) ? sanitize_text_field( $values['product_type'] ) : null;
        if($product_type){
            $item->add_meta_data( __( 'Product Type', 'lmfwpptwcext' ), ucwords( $product_type ) );
        }

        if ( $product_type == "theme" && isset( $values['theme_id'] ) && $values['theme_id'] ){
            $theme_name = LMFWPPT_ProductsHandler::get_product($values['theme_id'])['name'];
            $item->add_meta_data( __( 'Theme Name', 'lmfwpptwcext' ), $theme_name );
        }

        if ( $product_type == "plugin" && isset( $values['plugin_id'] ) && $values['plugin_id'] ){
            $plugin_name = LMFWPPT_ProductsHandler::get_product($values['plugin_id'])['name'];
            $item->add_meta_data( __( 'Plugin Name', 'lmfwpptwcext' ), $plugin_name );
        }

        if( isset( $values['package_id'] ) && $values['package_id'] ){
            $package_name = LMFWPPT_ProductsHandler::get_package_name($values['package_id']);
            $item->add_meta_data( __( 'Package Name', 'lmfwpptwcext' ), $package_name );
        }

       
    }

    // Attach License key in order item
    function license_key_order_email( $item_id, $item, $order, $plain_text ){

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
        echo sprintf('<ul class="wc-item-meta"><li><strong class="wc-item-meta-label">%s</strong>: <code>%s</code></li></ul>',
            __( 'License Key', 'lmfwpptwcext' ),
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
                __( 'Download Link', 'lmfwpptwcext' ),
                $download_link,
                __( 'Download', 'lmfwpptwcext' )
            );
        }

    }


    /**
     * Get product package
     */
	function get_product_package(){

        ?>
        <option value="" class="blank"><?php esc_html_e( 'Select Package', 'lmfwppt' ); ?></option>
        <?php 

		if( isset( $_POST['id'] ) ) {

            $package_list = LMFWPPT_ProductsHandler::get_packages($_POST['id']);

            if( $package_list ) {

                foreach( $package_list as $result ):
                    $package_id = $result['package_id'];
                    $label = $result['label'];
                    ?>
                    <option value="<?php echo $package_id; ?>"><?php echo $label; ?></option> 
                    <?php 
                endforeach;
            }
         
        }
        die();
	}
	
}