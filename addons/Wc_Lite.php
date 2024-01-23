<?php
namespace Licenser\Addons;

class Wc_Lite {

    // constructor
    public function __construct() {
        // Defines
        $this->defines();

        // Admin Handler
        if ( is_admin() ) {
            $this->admin_handler();
        } else {
            new \Licenser\Addons\Wc_Lite\Order_Handler();
        }

        // $order_handler = new \Licenser\Addons\Wc_Lite\Order_Handler();
        
        // Generate License
        add_action( 'woocommerce_order_status_changed', [$this, 'generate_license_key'], 150 );
        
        // Add License key in the order email of each item
        add_action( 'woocommerce_email_after_order_table', [$this, 'add_license_key_in_order_email'], 10, 4 );
    }

    // Defines
    public function defines() {
        define( 'LICENSER_WCLITE_PLUGIN_URL', plugin_dir_url( __FILE__ ) . 'Wc_Lite/' );
        define( 'LICENSER_WCLITE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    }

    // Admin_Handler
    public function admin_handler() {
        new \Licenser\Addons\Wc_Lite\Admin_Handler();
    }

    // Generate License
    public function generate_license_key( $order_id ) {
        $order_handler = new \Licenser\Addons\Wc_Lite\Order_Handler();
        $order_handler->generate_license( $order_id );
    }

    // Add License key in the order email of each item
    public function add_license_key_in_order_email( $order, $sent_to_admin, $plain_text, $email ) {
        $order_handler = new \Licenser\Addons\Wc_Lite\Order_Handler();
        $order_handler->add_license_key_in_order_email( $order, $sent_to_admin, $plain_text, $email );
    }
}