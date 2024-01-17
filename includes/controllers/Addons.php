<?php
namespace Licenser\Controllers;

class Addons {
    /**
     * Singleton instance
     *
     * @var DB
     */
    private static $instance;

    /**
     * Initialize the class
     */
    private function __construct() {

        // Load File
        // require_once LICENSER_PATH . '/addons/wc-lite/wc-lite.php';
        $wc_addons = new \Licenser\Addons\Wc_Lite();
        
    }

    /**
     * Get the singleton instance
     *
     * @return DB
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}