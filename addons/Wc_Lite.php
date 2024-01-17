<?php
namespace Licenser\Addons;

class Wc_Lite {

    // constructor
    public function __construct() {
        // Admin Handler
        if ( is_admin() ) {
            $this->admin_handler();
        }
        
    }

    // Admin_Handler
    public function admin_handler() {
        new \Licenser\Addons\Wc_Lite\Admin_Handler();
    }
}