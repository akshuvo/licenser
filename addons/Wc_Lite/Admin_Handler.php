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
        $post_data = $_POST;

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
        $post_data = $_POST;

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
        $post_id = get_the_ID();

        // Product instance
        $product_model = \Licenser\Models\Product::instance();
        
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
                    'class'          => 'select_product_list licenser_product_id',
                    'value'       => get_post_meta( $post_id, 'licenser_product_id', true ),
                    'label'       => __('Select Product', 'licenser'),
                    'options'     => []
                ) );

                woocommerce_wp_select( array(
                    'id'          => 'licenser_package_id' . $loop_index,
                    'class'          => 'select_package',
                    'value'       => get_post_meta( $post_id, 'licenser_package_id', true ),
                    'label'       => __('Select Package', 'licenser'),
                    'options'     => array( '' => 'Please select'),
                    'custom_attributes' => ['data-pack_value' => get_post_meta( $post_id, 'select_package', true )]
                ) );
         
            echo '</div>';
        echo '</div>';
    }

    /**
     * Save Custom Fields
     *
     * @param int $post_id
     * @return void
     */
    public function save_fields( $post_id, $loop_index = '' ){

        $post_data = $_POST;

        // Loop Index
        $maybe_loop = $loop_index || $loop_index == 0 ? '_' . $loop_index : '';

        // Activate License Management
        $active_licensing = isset( $post_data['licenser_active_licensing' . $maybe_loop]  ) ? sanitize_text_field( $post_data['licenser_active_licensing' . $maybe_loop]  ) : '';

        // Product Type
        $product_type = isset( $post_data['licenser_product_type' . $maybe_loop]  ) ? sanitize_text_field( $post_data['licenser_product_type' . $maybe_loop]  ) : '';

        // Product ID
        $product_id = isset( $post_data['licenser_product_id' . $maybe_loop]  ) ? sanitize_text_field( $post_data['licenser_product_id' . $maybe_loop]  ) : '';

        // Package ID
        $package_id = isset( $post_data['licenser_package_id' . $maybe_loop]  ) ? sanitize_text_field( $post_data['licenser_package_id' . $maybe_loop]  ) : '';

        // Update Product Type
        update_post_meta( $post_id, 'licenser_product_type', $product_type );

        // Update Product ID
        update_post_meta( $post_id, 'licenser_product_id', $product_id );

        // Update Package ID
        update_post_meta( $post_id, 'licenser_package_id', $package_id );

        // Update License Management
        update_post_meta( $post_id, 'licenser_active_licensing', $active_licensing );
    }
}