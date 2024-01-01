<?php
namespace Licenser;

class Licenses {

    // Tables
    public $license_table;
    public $domain_table;
    public $package_table;
    public $product_table;


    /**
     * Initialize the class
     */
    function __construct() {

        add_action( 'admin_init', [ $this, 'delete_license' ] );
        
        // Generate Download
        if ( isset( $_GET['product_slug'] ) && isset( $_GET['license_key'] ) && isset( $_GET['action'] ) && $_GET['action'] == "download" ) {

            $this->download_product( $_GET );
        }
        
        // Set Tables
        $this->license_table = licenser_table('licenses');
        $this->domain_table = licenser_table('license_domains');
        $this->package_table = licenser_table('license_packages');
        $this->product_table = licenser_table('products');
        
    }

    // Section information Field add
    function domain_ajax_add_action(){

        $key = sanitize_text_field( $_POST['key'] );

        ob_start();

        echo self::domain_sections_field( array(
            //'key' => $key,
            'thiskey' => $key,
        ) );

        $output = ob_get_clean();

        echo $output;

        die();
    }

    // Single Section field
    public static function domain_sections_field( $args ){

        $defaults = array (
            'key' => '',
            'url' => '',
            'deactivate' => '',
        );

        // Parse incoming $args into an array and merge it with $defaults
        $args = wp_parse_args( $args, $defaults );

        // Let's extract the array to variable
        extract( $args );

        // Array key
        //$key =  isset( $args['key'] ) ? $args['key'] : "";
        $key =  !empty( $key ) ? $key : wp_generate_password( 3, false );
   
        $field_name = "lmfwppt[domains][$key]";

        ob_start();
        do_action( 'lmfwppt_license_field_before_wrap', $args );
        ?>

         <div id="postimagediv" class="postbox lmfwppt_license_field"> <!-- Wrapper Start -->
            <span id="poststuff">
                <h2 class="hndle">
                     
                    <input id="<?php echo esc_attr( $field_name ); ?>-lmfwppt_domain" class="regular-text" type="text" name="<?php echo esc_attr( $field_name ); ?>[url]" value="<?php echo esc_attr( $url ); ?>" placeholder="<?php echo esc_attr__( 'Enter Domain/URL', 'lmfwppt' ); ?>" required />
                    <label class="lmfwppt_label_space">
                        <input name="<?php echo esc_attr( $field_name ); ?>[deactivate]" type="checkbox" id="<?php echo esc_attr( $field_name ); ?>-lmfwppt_deactivate" <?php checked($deactivate, "on"); ?>><?php esc_html_e( 'Deactivate', 'lmfwppt' ); ?>
                    </label>
                    <span class="delete_field">&times;</span>
                </h2>
            </span>
        </div>
        <?php
        $output = ob_get_clean();

        return do_action( 'lmfwppt_license_field_after_wrap', $output, $args );
    }

    // Get domains by license id
    function get_domains( $license_id = 0 ){
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare("SELECT * FROM $this->domain_table WHERE license_id = %d ", $license_id), ARRAY_A );
    }

    // Generate html from Domain array
    public static function get_domains_html( $get_domains = [] ){
        if( empty($get_domains) ){
            return;
        }

        // loop all domain
        foreach ($get_domains as $domain) {
            $key = isset( $domain['id'] ) ? sanitize_text_field( $domain['id'] ) : '';
            $url = isset( $domain['domain'] ) ? sanitize_text_field( $domain['domain'] ) : '';
            $status = isset( $domain['status'] ) ? sanitize_text_field( $domain['status'] ) : '1';

            self::domain_sections_field( array(
                'key' => $key,
                'url' => $url,
                'deactivate' => $status == "0" ? 'on' : '',
            ) );
        }

    }

    // Get Product details by id
    public function get_license( $id = null ){

        if( !$id ){
            return;
        }

        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM {$this->license_table} WHERE id = %d", $id);
        return $wpdb->get_row( $query, ARRAY_A );
    }

    // Get Product key by id
    public function get_license_key( $id = null ){

        if( !$id ){
            return;
        }

        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare("SELECT license_key FROM {$this->license_table} WHERE id = %d", $id) );
    }


    // License Package Field add
    function get_license_details( $license_key = null ){
        $response = array();

        if ( !$license_key ) {
            return false;
        }

        global $wpdb;

        $get_license = $wpdb->get_row( $wpdb->prepare("SELECT package_id, dated FROM {$this->license_table} WHERE license_key = %s", $license_key), ARRAY_A );

        $package_id = isset( $get_license['package_id'] ) ? $get_license['package_id'] : null;
        $license_date = isset( $get_license['dated'] ) ? $get_license['dated'] : null;

        if ( !$package_id ) {
            return false;
        }

        $get_product = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$this->package_table} as lp INNER JOIN {$this->product_table} as p ON p.id = lp.product_id WHERE lp.package_id = %s", $package_id), ARRAY_A );

        // change download url
        $get_product['license_key'] = $license_key;
        $get_product['license_date'] = $license_date;

        return $get_product;
    }

    // Get Product details by product slug
    function get_product_details_by_slug( $slug = null ){

        if ( !$slug ) {
            return false;
        }

        global $wpdb;

        $get_product = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$this->product_table} WHERE slug = %s", $slug), ARRAY_A );

        return $get_product;
    }


    // License Package Field add
    function get_wp_license_details( $posted_args = array() ){
        $response = array();

        if ( !is_array( $posted_args ) ) {
            return false;
        }

        // Parse Args
        $args = wp_parse_args( $posted_args, array(
            'product_slug' => '',
            'license_key' => '',
        ) );

        $download_link = add_query_arg( array(
            'product_slug' => $args['product_slug'],
            'license_key' => $args['license_key'],
            'action' => 'download',
        ), lmfwppt_api_url() );

        // Get product details
        $get_product = $this->get_product_details_by_slug( $args['product_slug'] );

        // change download url
        if ( isset( $get_product ) && is_array( $get_product ) ) {
           $get_product['download_link'] = $download_link;
        }

        // change date to last_updated
        if ( isset( $get_product['dated'] ) ) {
           $get_product['last_updated'] = $get_product['dated'];
           unset( $get_product['dated'] );
        }
        
        // Remove ID
        if ( isset( $get_product['id'] ) ) {
            unset( $get_product['id'] );
        }

        // Remove created_by
        if ( isset( $get_product['created_by'] ) ) {
            unset( $get_product['created_by'] );
        }
        
        // Remove product_type
        if ( isset( $get_product['product_type'] ) ) {
            unset( $get_product['product_type'] );
        }

        // Process serialize banners
        if ( isset( $get_product['banners'] ) ) {
            $get_product['banners'] = unserialize( $get_product['banners'] );
        }

        // Process serialize sections
        if ( isset( $get_product['sections'] ) ) {
            $get_product['sections'] = unserialize( $get_product['sections'] );
        }

        // Return Data to API
        return $get_product;
    }

    // Product add form action
    function license_add(){
   
        if ( isset( $_POST['lmaction'] ) && $_POST['lmaction'] == "license_add_form" ) {

            $license_id = $this->create_license( $_POST['lmfwppt'] );

            echo $license_id;

        }

        die();
    }

    // Create License function
    public function create_license( $post_data = array() ){
        
        // Default args
        $default_args = array(
            'id' => 0,
            'status' => "1",
            'license_key' => '',
            'package_id' => "0",
            'order_id' => "0",
            'end_date' => '',
            'is_lifetime' => "0",
            'domain_limit' => "0",
            'domains' => [],
        );

        // Parse args
        $args = wp_parse_args( $post_data, $default_args );

        // Set lifetime value
        if( empty( $args['end_date'] ) ){
            $args['is_lifetime'] = "1";
        }

        // Make the id integer
        if ( $args['id'] ) {
            $args['id'] = intval( $args['id'] );
        }

        // Insert data
        $data = array(
            'status' => sanitize_text_field( $args['status'] ),
            'license_key' => sanitize_text_field( $args['license_key'] ),
            'package_id' => sanitize_text_field( $args['package_id'] ),
            'order_id' => sanitize_text_field( $args['order_id'] ),
            'end_date' => sanitize_text_field( $args['end_date'] ),
            'is_lifetime' => sanitize_text_field( $args['is_lifetime'] ),
            'domain_limit' => sanitize_text_field( $args['domain_limit'] ),
        );

        global $wpdb;
       
        // Add/Edit
        if ( $args['id'] && is_int( $args['id'] ) ) {
            $insert_id = $args['id'];
            $wpdb->update( $this->license_table, $data, array( 'id'=> $insert_id ) );
        } else {
            $wpdb->insert( $this->license_table, $data);
            $insert_id = $wpdb->insert_id;
        }

        // If inserted/edited
        if ( !empty( $insert_id ) ) {

            // Delete Old Domain for $license_id
            $wpdb->delete( $this->domain_table, ['license_id' => $insert_id], ['%d'] );
            
            // Insert domain if not empty
            if ( !empty( $args['domains'] ) ) {

                foreach ( $args['domains'] as $key => $value ) {
                    
                    // Set insert data
                    $insert_data = array(
                        'license_id' => $insert_id,
                        'domain'     => isset( $value['url'] ) ? sanitize_text_field( $value['url'] ) : '',
                        'status'     => isset( $value['deactivate'] ) ? "0" : "1",
                    );

                    // Insert domain
                    $this->insert_domain( $insert_data );
                }

            }
        }

        // Returns
        return $insert_id ? $insert_id : null;

    }

    // Insert Domain Function
    function insert_domain( $args = array() ){

        // Default args
        $default_args = array(
            'id' => 0,
            'license_id' => '',
            'domain' => '',
            'status' => "1",
        );

        // Parse args
        $args = wp_parse_args( $args, $default_args );

        // Remove HTTP/ HTTPS
        if ( !empty( $args['domain'] ) ) {
            $args['domain'] = preg_replace("(^https?://)", "", $args['domain'] );
        }

        // Insert data
        $data = array(
            'license_id' => sanitize_text_field( $args['license_id'] ),
            'domain'     => sanitize_text_field( $args['domain'] ),
            'status'     => sanitize_text_field( $args['status'] ),
        );

        // Make the id integer
        if ( $args['id'] ) {
            $args['id'] = intval( $args['id'] );
        }

        global $wpdb;
        
        // Add / Update
        if ( $args['id'] && is_int( $args['id'] ) ) {
            $insert_id = $args['id'];
            $wpdb->update( $this->domain_table, $data, array( 'id'=> $insert_id ) );
        } else {
            $wpdb->insert( $this->domain_table, $data);
            $insert_id = $wpdb->insert_id;
        }

        // Returns
        return $insert_id ? $insert_id : null;

    }

    // Check if License Already hase domain
    function license_already_has_domain( $args = array() ){

        // Default args
        $default_args = array(
            'id' => 0,
            'license_id' => '',
            'domain' => '',
            'status' => "1",
        );

        // Parse args
        $args = wp_parse_args( $args, $default_args );

        // Remove HTTP/ HTTPS
        if ( !empty( $args['domain'] ) ) {
            // $args['domain'] = preg_replace("(^https?://)", "", $args['domain'] );
            $args['domain'] = lmfwppt_get_clean_url( $args['domain'] );
        }

        global $wpdb;
        
        // Delete Old Data if status inactive
        $wpdb->query( $wpdb->prepare("DELETE FROM {$this->domain_table} WHERE license_id = %d AND domain = %s AND status != 1 ", $args['license_id'], $args['domain'] ));

        // Search and Return Status active data
        $domain_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$this->domain_table} WHERE license_id = %d AND domain = %s AND status = %d ", $args['license_id'], $args['domain'], "1" ));

        // Returns
        return !empty( $domain_id ) ? sanitize_text_field( $domain_id  ) : null;

    }

    // Check if License Domains Count
    function license_domains_count( $args = array() ){

        // Default args
        $default_args = array(
            'id' => 0,
            'license_id' => '',
            'domain' => '',
            'status' => "1",
        );

        // Parse args
        $args = wp_parse_args( $args, $default_args );

        // Return if no license ID
        if ( empty( $args['license_id'] ) ) {
            return null;
        }

        global $wpdb;
 
        // Search and Return Status active data
        $total_domains = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id) FROM {$this->domain_table} WHERE license_id = %d ", $args['license_id'] ));

        // Returns
        return !empty( $total_domains ) ? sanitize_text_field( $total_domains  ) : null;

    }

    // Select Package 
    function product_package() {
        // Product Class
        $product_handler = new Products();
        ?>
        <option value="" class="blank"><?php esc_html_e( 'Select Package', 'lmfwppt' ); ?></option>
        <?php
        if( isset($_POST['id']) ) {
            $package_list = $product_handler->get_packages($_POST['id']);
            $selected = isset( $_POST['selected'] ) ? sanitize_text_field( $_POST['selected'] ) : '';
            
            if( $package_list ) {
                foreach( $package_list as $result ):
                    $package_id = $result['package_id'];
                    $label = $result['label'];
                    ?>
                    <option value="<?php echo $package_id; ?>"<?php selected( $selected, $package_id );?>><?php echo $label; ?></option> 
                    <?php 
                endforeach;
            }
        }
        die();
    }

    // License Key Genarate Ajax Hook
    function ajax_generate_license_key(){
        echo self::generate_license_key();
        die();
    }

    // License Key Genarate function
    public static function generate_license_key() {
        

        $method = licenser_get_option('license_generate_method');
        if ( $method == 'wp_generate' ) {
            $limit = licenser_get_option('license_code_character_limit');
            $key = wp_generate_password( $limit, false, false );
        } else {
            $key = md5( microtime() . rand() );
        }

        $prefix = licenser_get_option('license_code_prefix');
        
        return $prefix.$key;
    }

    // Delete License Id
    function lmfwppt_delete_license( $id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->license_table,
            [ 'id' => $id ],
            [ '%d' ]
        );
    }

    // Get The Action
    function delete_license() {

        if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == "lmfwppt-delete-license" ){
            if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'lmfwppt-delete-license' ) ) {
                wp_die( 'Are you cheating?' );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Are you cheating?' );
            }

            $id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0; 

            if ( $this->lmfwppt_delete_license( $id ) ) {
                $redirected_to = admin_url( 'admin.php?page=licenser-licenses&deleted=true' );
            } else {
                $redirected_to = admin_url( 'admin.php?page=licenser-licenses&deleted=false' );
            }

            wp_redirect( $redirected_to );
            exit;

        }    
    } 

    public static function get_licenses_by_order_ids( $order_ids = array() ){

        $order_ids = wp_parse_args( $order_ids, array() );

        if( count( $order_ids ) < 1 ) {
            return;
        }

        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM {$this->license_table} WHERE 1=%d AND order_id IN ( " . implode(',', $order_ids) . " ) ORDER BY dated DESC", 1 );
        return $wpdb->get_results( $query, ARRAY_A );
    }

    // Handle Download Product
    function download_product( $posted_args = array(), $admin_check = true ){
        $response = array('status' => true);

        if ( !is_array( $posted_args ) ) {
            return false;
        }

        // Parse args
        $args = wp_parse_args( $posted_args, array(
            'product_slug' => '',
            'license_key' => '',
            'domain' => '',
            'action' => 'download',
        ) );
       
        global $wpdb;

        // Get license package by key
        $get_package = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$this->license_table} WHERE license_key = %s ", $args['license_key']) );

        // If empty: license not found
        if( empty( $get_package ) ){
            // Response for license validation
            $response['status'] = false;
            $response['msg'] = 'Invalid license key';

            if ( $args['action'] == 'download' ){
                wp_die('Invalid package.');
            }
            
        }

        // Check if expired
        $end_date = isset( $get_package->end_date ) ? sanitize_text_field( $get_package->end_date ) : null;
        $is_lifetime = isset( $get_package->is_lifetime ) ? sanitize_text_field( $get_package->is_lifetime ) : 0;
        $license_id = isset( $get_package->id ) ? sanitize_text_field( $get_package->id ) : 0;
        $domain_limit = isset( $get_package->domain_limit ) ? sanitize_text_field( $get_package->domain_limit ) : 0;

        // Domain
        $domain = isset( $args['domain'] ) ? sanitize_text_field( $args['domain'] ) : '';

        // Check Expiry 
        if( $is_lifetime == "0" && !empty( $end_date ) && date('Y-m-d H:i:s') > $end_date ){

            // Response for license validation
            $response['status'] = false;
            $response['msg'] = 'License Expired';

            // Response for Download
            if ( $args['action'] == 'download' ){
                wp_die('Invalid request. Date Expired');
            }
        }

        // Check License Status
        $status = isset( $get_package->status ) ? sanitize_text_field( $get_package->status ) : 0;
        if( $status != "1" ){

            // Response for license validation
            $response['status'] = false;
            $response['msg'] = 'License Inactive';

            // Response for Download
            if ( $args['action'] == 'download' ){
                wp_die('Invalid request.');
            }
        }

        // For validate license
        if ( $args['action'] != 'download' ) {
            
            // If response is true
            if ( $response['status'] ) {

                // Lifetime or Expiry Date
                if ( $is_lifetime == "1" ) {
                    $response['expiry'] = 'lifetime';
                    // Add Msg
                    $response['msg'] = 'License is valid. Expiry date: Lifetime';
                } else {
                    $response['expiry'] = $end_date;

                    // Add Msg
                    $response['msg'] = 'License is valid. Expiry date: ' . wp_date("M d, Y h:ia", strtotime($end_date));
                }

                
                // Finally insert domain
                if ( !empty( $domain ) && $license_id ) {

                    // Insert Data
                    $insert_data = array(
                        'license_id' => $license_id,
                        'domain'     => $domain,
                        'status'     => "1",
                    );

                    // Insert domain
                    if ( !$this->license_already_has_domain( $insert_data ) ) {

                        // Domain Count Check
                        if ( !empty( $domain_limit ) && $this->license_domains_count( $insert_data ) >= $domain_limit ) {
                            $response['msg'] = "Domain Limit Exceeded. You already used this key on $domain_limit site(s)";
                            $response['status'] = false;
                            $response['expiry'] = '';

                        } else {
                            // Checks done, Insert new domain
                            $this->insert_domain( $insert_data );
                        }

                        
                    }
                    
                }
                
            }

            // Send response
            return apply_filters( 'lmfwppt_license_validation_response', $response, $args, $get_package );

        }

        
        
        // Get Download Link
        $download_link = $wpdb->get_var( $wpdb->prepare("
            SELECT p.download_link 
            FROM {$this->package_table} as lp 
            INNER JOIN {$this->product_table} as p ON p.id = lp.product_id WHERE lp.package_id = %s AND slug = %s", 
            $get_package->package_id, 
            $args['product_slug']
        ) );
        
        // Parse file path from download link
        $parsed_file_path = lmfwppt_parse_file_path( $download_link );

        /**
         * Fallback on force download method for remote files.
         */
        if ( $parsed_file_path['remote_file'] ) {
            header("Location: $download_link");
            exit;
        }

        // Download file name
        //$download_file_name = sanitize_text_field( $args['product_slug'].'.zip' );
        $download_file_name = basename($download_link);

        // File path
        $file_path = isset( $parsed_file_path['file_path'] ) ? sanitize_text_field( $parsed_file_path['file_path'] ) : '';
       
        // Download from own server
        if( !empty( $download_link ) && !empty( $file_path ) ) {
            //readfile($download_link);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"".$download_file_name."\"");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($file_path));
            ob_end_flush();
            @readfile($file_path);
            
        }

        exit;

    }

    /**
     * Get the License Count
     *
     * @return Int
     */
    public function license_count(){
        global $wpdb;
        return (int) $wpdb->get_var("SELECT count(id) FROM {$this->license_table}");
    }

}