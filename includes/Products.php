<?php
namespace Licenser;

class Products {

    // Tables
    public $license_table;
    public $domain_table;
    public $package_table;
    public $product_table;

    /**
     * Initialize the class
     */
    function __construct() {

        //add_action( 'init', [ $this, 'product_add' ] );

        // Product Delete
        add_action( 'admin_init', [ $this, 'delete_product' ] );


        // Set Tables
        $this->license_table = licenser_table('licenses');
        $this->domain_table = licenser_table('license_domains');
        $this->package_table = licenser_table('license_packages');
        $this->product_table = licenser_table('products');

    }

    // License Package Field add
    function license_package_ajax_add_action(){

        $key = sanitize_text_field( $_POST['key'] );

        ob_start();

        echo self::license_package_field( array(
            'key' => $key,
            'thiskey' => $key,
        ) );

        $output = ob_get_clean();

        echo $output;

        die();
    }

    // Single license field
    public static function license_package_field( $args ){

        $defaults = array (
            'key' => '',
            'package_id' => '',
            'label' => '',
            'product_id' => '',
            'update_period' => '',
            'domain_limit' => ''
        );

        // Parse incoming $args into an array and merge it with $defaults
        $args = wp_parse_args( $args, $defaults );

        // Let's extract the array to variable
        extract( $args );

        // Array key
        //$key =  isset( $args['key'] ) ? $args['key'] : "";
        $key =  !empty( $package_id ) ? $package_id : wp_generate_password( 3, false );;
   
        $field_name = "lmfwppt[license_package][$key]";

        ob_start();
        do_action( 'lmfwppt_license_field_before_wrap', $args );
        ?>

        <div id="postimagediv" class="postbox lmfwppt_license_field"> <!-- Wrapper Start -->
            <a class="header lmfwppt-toggle-head" data-toggle="collapse">
                <span id="poststuff">
                    <h2 class="hndle">
                        <input type="text" class="prevent-toggle-head license-package-name regular-text" name="<?php esc_attr_e( $field_name ); ?>[label]" placeholder="<?php esc_attr_e( 'License Title: 1yr unlimited domain.', 'lmfwppt' ); ?>" value="<?php esc_attr_e( $label ); ?>" title="<?php esc_attr_e( 'Change title to anything you like. Make sure they are unique.', 'lmfwppt' ); ?>" required />
                        <span class="dashicons indicator_field"></span>
                        <span class="delete_field">&times;</span>
                    </h2>
                </span>
            </a>
            <div class="collapse lmfwppt-toggle-wrap">
                <div class="inside">
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row">
                                <div class="tf-label">
                                    <label for="<?php esc_attr_e( $field_name ); ?>-package_id"><?php esc_html_e( 'Package ID', 'lmfwppt' ); ?></label>
                                </div>
                            </th>
                            <td>
                                <input id="<?php esc_attr_e( $field_name ); ?>-package_id" class="license-package-id regular-text" type="text" name="<?php esc_attr_e( $field_name ); ?>[package_id]" value="<?php echo esc_attr( $package_id ); ?>" placeholder="<?php echo esc_attr( 'enter-package-id', 'lmfwppt' ); ?>" required /> <button type="button" class="button button-secondary generate-package-id">Generate</button>
                                <p><?php esc_html_e( 'Enter a unique url friendly text. No special characters allowed.', 'lmfwppt' ); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <div class="tf-label">
                                    <label for="<?php esc_attr_e( $field_name ); ?>-update_period"><?php esc_html_e( 'Update Period', 'lmfwppt' ); ?></label>
                                </div>
                            </th>
                            <td>
                                <input id="<?php esc_attr_e( $field_name ); ?>-update_period" class="regular-text" type="number" min="1" name="<?php esc_attr_e( $field_name ); ?>[update_period]" value="<?php echo esc_attr( $update_period ); ?>" placeholder="<?php echo esc_attr( 'Enter in Days', 'lmfwppt' ); ?>"/>
                                <p><?php esc_html_e( 'Leave empty for lifetime updates.', 'lmfwppt' ); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <div class="tf-label">
                                    <label for="<?php esc_attr_e( $field_name ); ?>-domain_limit"><?php esc_html_e( 'Domain Limit', 'lmfwppt' ); ?></label>
                                </div>
                            </th>
                            <td>
                                <input id="<?php esc_attr_e( $field_name ); ?>-domain_limit" class="regular-text" type="number" min="1" name="<?php esc_attr_e( $field_name ); ?>[domain_limit]" value="<?php echo esc_attr( $domain_limit ); ?>" placeholder="<?php echo esc_attr( 'How many domains allowed to get updates?', 'lmfwppt' ); ?>" />
                                <p><?php esc_html_e( 'Leave empty for unlimited domain.', 'lmfwppt' ); ?></p>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        <!-- Wrapper end below -->
        </div>
        <?php
        $output = ob_get_clean();

        return do_action( 'lmfwppt_license_field_after_wrap', $output, $args );
    } 

    // Section information Field add
    function product_sections_ajax_add_action(){

        $key = sanitize_text_field( $_POST['key'] );

        ob_start();

        echo self::product_sections_field( array(
            //'key' => $key,
            'thiskey' => $key,
        ) );

        $output = ob_get_clean();

        echo $output;

        die();
    }

    // Single Section field
    public static function product_sections_field( $args ){

        $defaults = array (
            'key' => '',
            'name' => '',
            'content' => '',
        );

        // Parse incoming $args into an array and merge it with $defaults
        $args = wp_parse_args( $args, $defaults );

        // Let's extract the array to variable
        extract( $args );

        // Array key
        //$key =  isset( $args['key'] ) ? $args['key'] : "";
        $key =  !empty( $key ) ? $key : wp_generate_password( 3, false );
   
        $field_name = "lmfwppt[sections][$key]";

        ob_start();
        do_action( 'lmfwppt_license_field_before_wrap', $args );
        ?>

        <div id="postimagediv" class="postbox lmfwppt_license_field"> <!-- Wrapper Start -->
            <a class="header lmfwppt-toggle-head" data-toggle="collapse">
                <span id="poststuff">
                    <h2 class="hndle">
                        <input id="<?php esc_attr_e( $field_name ); ?>-section_name" class="prevent-toggle-head regular-text" type="text" name="<?php esc_attr_e( $field_name ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( 'Section Title', 'lmfwppt' ); ?>" required />
                        <span class="dashicons indicator_field"></span>
                        <span class="delete_field">&times;</span>
                    </h2>
                </span>
            </a>
            <div class="collapse lmfwppt-toggle-wrap">
                <div class="inside">
                    <table class="form-table">

                        <tr valign="top">
                             
                            <div class="section-content">
                                <label for="<?php esc_attr_e( $field_name ); ?>-section_content"><?php esc_html_e( 'Section Content', 'lmfwppt' ); ?></label>
                            </div>
                             
                            <td style="padding: 0; width: 100%;">
                                <textarea style="width: 100%;" id="<?php esc_attr_e( $field_name ); ?>-section_content" name="<?php esc_attr_e( $field_name ); ?>[content]" rows="6" cols="100" placeholder="<?php echo esc_attr( 'Section Content', 'lmfwppt' ); ?>"><?php echo $content; ?></textarea>  
                            </td>
                             
                        </tr>
                    </table>
                </div>
            </div>
        <!-- Wrapper end below -->
        </div>
        <?php
        $output = ob_get_clean();

        return do_action( 'lmfwppt_license_field_after_wrap', $output, $args );
    }
    
    // License Packages Content Render
    function license_content( $output, $args ){
        echo $output;
    }

    // Product add form action
    function product_add(){
        $response = array();
        $response['success'] = false;

        if ( isset( $_POST['lmaction'] ) && $_POST['lmaction'] == "product_add_form" ) {

            $product_id = isset( $_POST['lmfwppt'] ) ? $this->create_product( $_POST['lmfwppt'] ) : null;

            // Create Packages
            if ( isset( $_POST['lmfwppt']['license_package'] ) && count( $_POST['lmfwppt']['license_package'] ) > 0 ) {
                // Delete old data
                global $wpdb;
                $wpdb->delete( $this->package_table, array( 'product_id' => $product_id ) );
                foreach ( $_POST['lmfwppt']['license_package'] as $package ) {
                    $this->create_package( $package, $product_id );
                }
            }

            echo $product_id;
        }

        die();
    }

    // Create product function
    function create_product( $post_data = array() ){
        global $wpdb;

        $sections = array();
        if ( isset( $post_data['sections'] ) ) {
            foreach( $post_data['sections'] as $section ){
                $sec_key = sanitize_title( $section['name'] );
                $sections[ $sec_key ] = $section;
            }
        } 

        // Product data
        $data = array(
            'name' => isset($post_data['name']) ? sanitize_text_field( $post_data['name'] ) : "",
            'slug' => isset($post_data['slug']) ? sanitize_text_field( $post_data['slug'] ) : "",
            'product_type' => isset($post_data['product_type']) ? sanitize_text_field( $post_data['product_type'] ) : "",
            'version' => isset($post_data['version']) ? sanitize_text_field( $post_data['version'] ) : "",
            'tested' => isset($post_data['tested']) ? sanitize_text_field( $post_data['tested'] ) : "",
            'requires' => isset($post_data['requires']) ? sanitize_text_field( $post_data['requires'] ) : "",
            'requires_php' => isset($post_data['requires_php']) ? sanitize_text_field( $post_data['requires_php'] ) : "",
            'download_link' => isset($post_data['download_link']) ? sanitize_text_field( $post_data['download_link'] ) : "",
            'banners' => isset($post_data['banners']) ? serialize( array_map('esc_url_raw', $post_data['banners'])):"",
            'sections' => isset($post_data['sections']) ? serialize( $sections ) : "",
            'author' => isset($post_data['author']) ? sanitize_text_field( $post_data['author'] ) : "",
            'created_by' => isset($post_data['created_by']) ? intval( $post_data['created_by'] ) : "",
            'dated' => date('Y-m-d H:i:s'),
        );

        // Add or Update
        if ( isset( $post_data['product_id'] ) ) {
            $insert_id = intval( $post_data['product_id'] );
            $wpdb->update( $this->product_table, $data, array( 'id'=> $insert_id ) );
        } else {
            $wpdb->insert( $this->product_table, $data);
            $insert_id = $wpdb->insert_id;
        }
        
        return $insert_id ? $insert_id : null;

    }


    // Create package function
    function create_package( $post_data = array(), $product_id = null ){
        global $wpdb;

        $data = array(
            'product_id' => isset($product_id) ? intval( $product_id ) : null,
            'label' => isset($post_data['label']) ? sanitize_text_field( $post_data['label'] ) : "",
            'package_id' => isset($post_data['package_id']) ? sanitize_text_field( $post_data['package_id'] ) : "",
            'update_period' => isset($post_data['update_period']) ? intval( $post_data['update_period'] ) : "",
            'domain_limit' => isset($post_data['domain_limit']) ? intval( $post_data['domain_limit'] ) : "",
        );
        
        $wpdb->insert( $this->package_table, $data);
        $insert_id = $wpdb->insert_id;

        return $insert_id ? $insert_id : null;
    }

    // Get Product details by id
    public function get_product( $id = null ){

        if( !$id ){
            return;
        }

        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM {$this->product_table} WHERE id = %d", $id);
        return $wpdb->get_row( $query, ARRAY_A );
    }

    // Get Product package details by product_id
    public function get_packages( $product_id = null ){ 

        if( !$product_id ){
            return;
        }

        global $wpdb;

        $query = $wpdb->prepare("SELECT * FROM {$this->package_table} WHERE product_id = %d", $product_id);
        return $wpdb->get_results( $query, ARRAY_A );
    }

    // Generate html from packages array
    public static function get_packages_html( $get_packages = null ){
        if( !$get_packages ){
            return;
        }

        // Loop all content
        foreach ($get_packages as $package) {

            // Vars
            $package_id     = isset( $package['package_id'] ) ? sanitize_text_field( $package['package_id'] ) : '';
            $label          = isset( $package['label'] ) ? sanitize_text_field( $package['label'] ) : '';
            $product_id     = isset( $package['product_id'] ) ? sanitize_text_field( $package['product_id'] ) : '';
            $update_period  = isset( $package['update_period'] ) && $package['update_period'] != "0" ? sanitize_text_field( $package['update_period'] ) : '';
            $domain_limit   = isset( $package['domain_limit'] ) && $package['domain_limit'] != "0" ? sanitize_text_field( $package['domain_limit'] ) : '';

            // License Fields
            self::license_package_field( array(
                'key' => $package_id,
                'package_id' => $package_id,
                'label' => $label,
                'product_id' => $product_id,
                'update_period' => $update_period,
                'domain_limit' => $domain_limit
            ) );
        }

    }

    // Generate html from Section array
    public static function get_section_html( $get_sections = null ){
        if( !$get_sections ){
            return;
        }

        foreach ($get_sections as $section) {
            self::product_sections_field( array(
                'key' => sanitize_title($section['name']),
                'name' => $section['name'],
                'content' => $section['content']
            ) );
        }

    }

    // Get Id Product Delete
    function lmfwppt_delete_product_id( $id ) {
        global $wpdb;

        $deleted_package = $wpdb->delete(
            $this->package_table,
            [ 'product_id' => $id ],
            [ '%d' ]
        );

        $deleted_product = $wpdb->delete(
            $this->product_table,
            [ 'id' => $id ],
            [ '%d' ]
        );

        if( $deleted_package && $deleted_product ){
            return true;
        } else {
            return false;
        }
        
    }

    // Get The Action
    function delete_product() {
        
        if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == "lmfwppt-delete-product" ){
            if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'lmfwppt-delete-product' ) ) {
                wp_die( 'Are you cheating?' );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Are you cheating?' );
            }

            $id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0; 

            $active_page = $_REQUEST['redirect_url'];

            if ( $this->lmfwppt_delete_product_id( $id ) ) {
                $redirected_to = admin_url( 'admin.php?page='.$active_page.'&deleted=true' );
            } else {
                $redirected_to = admin_url( 'admin.php?page='.$active_page.'&deleted=false' );
            }

            wp_redirect( $redirected_to );
            exit;

        }    
    } 

    // Get package name by Package ID
    public static function get_package_name( $package_id ){

        if( !$package_id ){
            return;
        }

        global $wpdb;
        $query = $wpdb->prepare("SELECT label FROM {$this->package_table} WHERE package_id = %s", $package_id);
        return $wpdb->get_var( $query );
    }

    // Get package Column by Package ID
    public static function get_package_by_package_id( $package_id, $column_name = '*' ){

        if( !$package_id ){
            return;
        }

        global $wpdb;
        $query = $wpdb->prepare("SELECT {$column_name} FROM {$this->package_table} WHERE package_id = %s", $package_id);

        $result = $wpdb->get_row( $query, ARRAY_A );

        if ( $column_name != '*' ) {
            return isset( $result[$column_name] ) ? $result[$column_name] : null;
        }
        return $result;
    }

    // Get package value 
    public function get_product_details_by_package_id( $package_id = null ){

        if( !$package_id ) {
            return;
        }

        global $wpdb;
        $get_product = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$this->package_table} as lp INNER JOIN {$this->product_table} as p ON p.id = lp.product_id WHERE lp.package_id = %s", $package_id), ARRAY_A );

        return $get_product;
    }

    /**
     * Get the Product Item Count
     *
     * @return Int
     */
    public function product_count( $product_type ){

        global $wpdb;

        $defaults = [
            'number' => 500,
            'product_type' => $product_type
        ];

        $args = wp_parse_args( $defaults );
        $product_list = $wpdb->prepare("SELECT count(id) FROM {$this->product_table} WHERE product_type = %s 
            LIMIT %d",
            $args['product_type'], 
            $args['number'] );
        return (int) $wpdb->get_var( $product_list );

    }
}