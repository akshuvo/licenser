<?php 
namespace Licenser\Addons\Wc_Lite;
/**
 * WooCommerce Handler
 */
class Shortcode{

    // License Endpoint Content
    public function output() {

        // User ID
        $user_id = get_current_user_id();

        // Get latest 3 orders.
        $args = array(
            'limit' => 100,
            'customer_id' => $user_id,
            'return' => 'ids',
            'status' => ['wc-processing', 'wc-completed'],
            'is_license_order' => 'yes'
        );
        $orders = wc_get_orders( $args );

        echo '<pre>'; print_r($orders); echo '</pre>';

        // Licenser Unique User ID
        // $licenser_user_id = get_user_meta( $user_id, 'licenser_user_id', true );

        $get_licenses = \Licenser\Models\License::instance()->get_all( [
            'number' => -1,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'count_total' => true,
            // 'user_uuid' => $licenser_user_id,
            'source' => 'wc',
            'source_id' => (array) $orders
        ] );

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

                    $license = (array) $license;

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
                    $get_product = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}licenser_license_packages as lp INNER JOIN {$wpdb->prefix}licenser_products as p ON p.id = lp.product_id WHERE lp.id = %s", $package_id), ARRAY_A );

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
                    $get_domains = [];

                    //ppr($get_product);
                    ?>
                    <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                        
                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Item', 'licenser'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html( $product_name, "licenser" ); ?></strong></div>
                            <div class="license_key">
                                <code><?php echo esc_html( $license_key ); ?></code>
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
            $product_page_url = apply_filters( 'licenser_wclite_products_page_url', $shop_page_url );

            ?>
            <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
                <a class="woocommerce-Button button" href="<?php echo esc_url( $product_page_url ); ?>"><?php esc_html_e('Browse products', 'licenser'); ?></a>
                <?php esc_html_e('No licenses available yet.', 'licenser'); ?>
            </div>
        <?php  endif;
    }

}