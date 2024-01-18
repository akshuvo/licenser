<?php 
namespace Licenser\Addons\Wc_Lite;
/**
 * WooCommerce Handler
 */
class Admin_Handler{

	function __construct(){
        // Product Page Tab
        add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tabs']);

        // Product Page Tab Content Custom Fields
        add_action('woocommerce_product_data_panels', [$this, 'product_data_panels']);

        // Save Custom Fields: Simple Product
        add_action('woocommerce_process_product_meta', [$this, 'save_fields_simple_product'], 10, 2);

        // Add Custom Fields on Variation
        add_action('woocommerce_product_after_variable_attributes', [$this, 'add_variation_custom_fields'], 10, 3);

        // Save Custom Fields: Variable Product
        add_action('woocommerce_save_product_variation', [$this, 'save_fields_variable_product'], 10, 2);

        // Admin Scripts
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    /**
     * Admin Scripts
     *
     * @param string $hook
     * @return void
     */
	function admin_scripts( $hook ) {
	    if ( 'options-permalink.php' != $hook ) {
	        //return;
	    }

	    $ver = current_time( 'timestamp' );

	    wp_enqueue_style( 'lmfwpptwcext-admin-styles', LICENSER_WCLITE_PLUGIN_URL . 'admin/assets/css/admin.css', null, $ver );
	    wp_enqueue_script( 'lmfwpptwcext-admin-scripts', LICENSER_WCLITE_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), $ver );
	}

    /**
     * Add Product Data Tab
     *
     * @param array $tabs
     * @return array
     */
    public function product_data_tabs($tabs){
        $tabs['licenser'] = [
            'label' => __('Licenser', 'licenser'),
            'target' => 'licenser_product_data',
            'class' => ['show_if_simple'],
            'priority' => 21,
        ];
        return $tabs;
    }

    /**
     * Add Product Data Tab Content
     *
     * @return void
     */
    public function product_data_panels(){
        $post_id = get_the_ID();

        // Custom Fields
        $this->license_management_fields( $post_id );
    }

    /**
     * Save Custom Fields: Simple Product
     *
     * @param int $post_id
     * @return void
     */
    public function save_fields_simple_product($post_id){
        // Save Custom Fields
        $this->save_fields( $post_id );
    }

    /**
     * Save Custom Fields: Variable Product
     *
     * @param int $post_id
     * @return void
     */
    public function save_fields_variable_product($post_id, $loop){
        // Save Custom Fields
        $this->save_fields( $post_id, $loop );
    }

    /**
     * Add Custom Fields on Variation
     *
     * @param int $loop
     * @param array $variation_data
     * @param object $variation
     * @return void
     */
    public function add_variation_custom_fields($loop, $variation_data, $variation){
        $post_id = $variation->ID;

        // Custom Fields
        $this->license_management_fields( $post_id, true, $loop );
    }

    /**
     * License Management Fields
     *
     * @return void
     */
    public function license_management_fields( $post_id, $is_variation = false, $loop = '' ){

        // Product instance
        $product_model = \Licenser\Models\Product::instance();

        // Products
        $products = $product_model->get_all([
            'status' => 'active',
            'number' => -1,
            'inc_packages' => true,
            'columns' => 'id, name, product_type',
        ]);

        // Product List Array
        $product_list = [];
        foreach ($products as $product) {
            $product_list[$product->id] = $product->name;
        }
        
        // Loop Index
        $loop_index = $is_variation ? '_' . $loop : '';
     
        echo '<div id="licenser_product_data" class="licenser_product_data panel woocommerce_options_panel">';
     
            woocommerce_wp_checkbox( array(
                'id'           => 'licenser_active_licensing' . $loop_index,
                'class'       => 'licenser_wcaddon_checkbox',
                'value'        => get_post_meta( $post_id, 'licenser_active_licensing', true ),
                'label'        => __('Enable License Management', 'licenser'),
                'description'  => __('Enable this option to manage license for this product.', 'licenser')
            ) );

            echo '<div class="licenser_wcaddon_product_fields">';

                woocommerce_wp_select( array(
                    'id'          => 'licenser_product_type' . $loop_index,
                    'class'       => 'licenser_product_type',
                    'value'       => get_post_meta( $post_id, 'licenser_product_type', true ),
                    'label'       => __('Select Products Type', 'licenser'),
                    'options'     => array_merge( ['' => 'Please select'], $product_model->get_types() )
                ) );

                woocommerce_wp_select( array(
                    'id'          => 'licenser_product_id' . $loop_index,
                    'class'          => 'select_product_list licenser_product_id licenser_load_package',
                    'value'       => get_post_meta( $post_id, 'licenser_product_id', true ),
                    'label'       => __('Select Product', 'licenser'),
                    'options'     => array( '' => 'Please select') + $product_list
                ) );

                woocommerce_wp_select( array(
                    'id'          => 'licenser_package_id' . $loop_index,
                    'class'          => 'licenser_select_package',
                    'value'       => get_post_meta( $post_id, 'licenser_package_id', true ),
                    'label'       => __('Select Package', 'licenser'),
                    'options'     => array( '' => 'Please select'),
                    'custom_attributes' => ['data-selected-val' => get_post_meta( $post_id, 'licenser_package_id', true )]
                ) );
         
            echo '</div>';
        echo '</div>';
    }

    /**
     * Save Custom Fields
     *
     * @param int $post_id
     * @param string $loop_index
     * @return void
     */
    public function save_fields($post_id, $loop_index = '') {

        // Loop Index
        $maybe_loop = $loop_index || $loop_index === 0 ? '_' . $loop_index : '';

        // Fields to update
        $fields_to_update = array(
            'licenser_active_licensing',
            'licenser_product_type',
            'licenser_product_id',
            'licenser_package_id',
        );

        foreach ($fields_to_update as $field) {
            $field_value = isset($_POST[$field . $maybe_loop]) ? sanitize_text_field($_POST[$field . $maybe_loop]) : '';
            update_post_meta($post_id, $field, $field_value);
        }
    }


}