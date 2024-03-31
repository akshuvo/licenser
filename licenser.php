<?php
/**
 * Plugin Name: Licenser - License Manager for WordPress
 * Plugin URI: https://github.com/akshuvo/licenser
 * Github Plugin URI: https://github.com/akshuvo/licenser
 * Description: Self-Hosted license manager for WordPress
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

// Access the global $lwpdb instance
global $lwpdb;

// Create an instance of the lwpdb class
$lwpdb = Licenser\Controllers\DB::instance();

/**
 * The main plugin class
 */
final class Licenser {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.0';

    /**
     * Class construcotr
     */
    private function __construct() {
        $this->define_constants();

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
    return Licenser::init();
}

// run the plugin
licenser();