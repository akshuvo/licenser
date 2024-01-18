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
        }
        
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
}