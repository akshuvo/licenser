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
            'source_id' => (array) $orders,
        ] );

        $get_licenses = false;

        echo sprintf("<h4>%s</h4>", esc_html__('License Manager', 'licenser'));

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

                    // Expired Text
                    $expired_label = '';

                    // Check if lifetime
                    if ( $is_lifetime != "0" ) {
                        $expire_date = __('Lifetime', 'licenser');
                    } else {
                        // check with $expire_date
                        if ( $expire_date ) {
                            $expired_label = strtotime($expire_date) < time() ? sprintf('<span class="expired-label">(%s)</span>', __('Expired. Want to renew?', 'licenser')) : '';
                        }
                        $expire_date = licenser_date('j F Y',strtotime($expire_date));
                    }

                    // If no package id
                    if ( !$package_id ) {
                        continue;
                    }

                    global $wpdb;
                    $get_product = $wpdb->get_row( 
                        $wpdb->prepare("SELECT product.uuid, product.name, package.label, product.product_type
                            FROM {$wpdb->prefix}licenser_license_packages as package 
                            INNER JOIN {$wpdb->prefix}licenser_products as product ON product.id = package.product_id 
                            WHERE package.id = %s", 
                            $package_id
                        ), 
                    );

                    $product_type = isset( $get_product->product_type ) ? $get_product->product_type : '';
                    $product_name = isset( $get_product->name ) ? $get_product->name : '';
                    $pack_label = isset( $get_product->label ) ? $get_product->label : '';
                    
                    // Domain Limit
                    if ( $domain_limit == "0" ) {
                       $domain_limit = __('Unlimited', 'licenser');
                    }

                    // Download Link
                    $download_link = licenser_product_download_url( $get_product->uuid, $license_key);


                    // Get Domains 
                    $get_domains = \Licenser\Models\License::instance()->get_domains([
                        'license_id' => $license_id,
                    ]);

                    ?>
                    <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
                        
                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('Item', 'licenser'); ?>">
                            <div class="license_product_name"><strong><?php echo esc_html( $product_name, "licenser" ); ?></strong></div>
                            <div class="license_key">
                                <code><?php echo esc_html( $license_key ); ?></code>
                            </div>

                            <!-- activations button -->
                            <div class="show_manage_activations_details">
                                <a><?php esc_html_e( "Manage Activations", "licenser" ); ?></a>
                            </div>

                            <!-- activations value show -->
                             
                            <div class="manage-activations">

                                <a class="activations-close-modal" title="Close">&times;</a>

                                <h5 ><?php esc_html_e( "Manage License:", "licenser" ); ?></h5>

                                <table style="border-width: 1px 1px 1px 1px;">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( "Site URL", "licenser" ); ?></th>
                                            <th><?php esc_html_e( "Status", "licenser" ); ?></th>
                                        </tr>
                                    </thead>
                                   <tbody>
                                    <?php if( !empty( $get_domains ) ) :
                                        foreach( $get_domains as $domain ):
                                            $key = isset( $domain->id ) ? sanitize_text_field( $domain->id ) : '';
                                            $url = isset( $domain->domain ) ? sanitize_text_field( $domain->domain ) : '';
                                            $status = isset( $domain->status ) && $domain->status == 1 ? __('Active', 'licenser') : __('Inactive', 'licenser');
                                            // $dated = isset( $domain->dated ) ? licenser_date('Y-m-d H:i:s', $domain->dated) : '';
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo esc_html( $url ); ?> 
                                                    <a target="_blank" href="<?php echo esc_url( $url ); ?>">↗</a>
                                                </td>
                                                <td>
                                                    <?php echo esc_html( $status ); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="2"><?php esc_html_e( "No Domains", "licenser" ); ?></td> 
                                    </tr>
                                    <?php endif; ?>  
                                       
                                   </tbody>
                                </table>
                            </div>
                            <div class="am-overlay"></div>

                        </td>

                        <td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e('License Details', 'licenser'); ?>">
                            <div class="license_product_name"><strong><?php esc_html_e( "Product Name",'licenser' ); ?>:</strong> <?php echo esc_html( $product_name ); ?> (<?php echo esc_html($pack_label); ?>) </div>
                            <div class="license_details">
                                <strong><?php esc_html_e( "Domain Limit", 'licenser' ); ?>:</strong> <?php echo esc_html($domain_limit)?><br>
                                <strong><?php esc_html_e( "Product Type", 'licenser' ); ?>:</strong> <?php echo esc_html(ucwords($product_type)); ?> <br>
                                <strong><?php esc_html_e( "Expires", 'licenser' ); ?>:</strong> <?php echo esc_html($expire_date); ?> <?php echo $expired_label; // phpcs:ignore ?>
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