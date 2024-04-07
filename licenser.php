<?php
/**
 * Plugin Name: Licenser - Self-Hosted License Manager
 * Plugin URI: https://github.com/akshuvo/licenser
 * Description: A self-hosted license manager for your digital products.
 * Author: AddonMaster
 * Author URI: https://addonmaster.com
 * Version: 1.0.8
 * Text Domain: licenser
 * Domain Path: /lang
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Autoload
require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

/**
 * The main plugin class
 */
final class Licenser {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.0.0';

    /**
     * Class construcotr
     */
    private function __construct() {
        $this->define_constants();

        // add_action( 'muplugins_loaded', [ $this, 'muplugins_loaded' ] );

        register_activation_hook( __FILE__, [ $this, 'activate' ] );

        add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
    }

    /**
     * Initializes a singleton instance
     *
     * @return \Licenser
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Define the required plugin constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'LICENSER_VERSION', self::version );
        define( 'LICENSER_FILE', __FILE__ );
        define( 'LICENSER_PATH', __DIR__ );
        define( 'LICENSER_URL', plugins_url( '', LICENSER_FILE ) );
        define( 'LICENSER_ASSETS', LICENSER_URL . '/assets' );
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init_plugin() {

        // new Licenser\Assets();

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            new Licenser\Ajax();
        }

        if ( is_admin() ) {
            new Licenser\Admin();
        } else {
            // new Licenser\Frontend();
        }

        // API
        new Licenser\Api();

        // Addons
        $addons = Licenser\Controllers\Addons::instance();
    }

    /**
     * Do stuff upon plugin activation
     *
     * @return void
     */
    public function activate() {
        $installer = new Licenser\Installer();
        $installer->run();
    }
}

// Initialize the plugin
function licenser() {
    // Access the global $wpdb instance
    global $wpdb;

    /**
     * Set the table names
     * 
     * @uses $wpdb->licenser_products
     */
    foreach ( [
        'products',
        'product_releases',
        'license_packages',
        'licenses',
        'license_meta',
        'license_domains',
    ] as $table ) {
        $wpdb->__set( 'licenser_' . $table, $wpdb->prefix . 'licenser_' . $table );
    }

    // Initialize the plugin
    return Licenser::init();
}

// run the plugin
licenser();