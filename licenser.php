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
        define( 'LMFWPPT_PLUGIN_VERSION', self::version );
        define( 'LICENSER_VERSION', self::version );
        define( 'LICENSER_FILE', __FILE__ );
        define( 'LICENSER_PATH', __DIR__ );
        define( 'LMFWPPT_PLUGIN_URL', plugins_url( '', LICENSER_FILE ) );
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
        \Licenser\Api::instance()->init();

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


/**
 *	EDDNSTANT Functions
 */
//require_once( dirname( __FILE__ ) . '/inc/functions.php' );


// /**
//  *	Plugin Main Class
//  */

// final class LicenseManagerForThemesPlugins {

// 	private function __construct() {
// 		// Loaded textdomain
// 		add_action('plugins_loaded', array( $this, 'plugin_loaded_action' ), 10, 2);

// 		// Enqueue frontend scripts
// 		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
// 		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 100 );

// 		// trigger upon plugin activation/deactivation
// 		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
// 		//register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

// 		// File includes
// 		$this->includes();

// 	}

// 	/**
// 	 * Initialization
// 	 */
// 	public static function init(){
//      	static $instance = false;

//         if ( ! $instance ) {
//             $instance = new self();
//         }

//         return $instance;
// 	}


// 	/**
// 	 * Plugin Loaded Action
// 	 */
// 	function plugin_loaded_action() {
// 		// Loading Text Domain for Internationalization
// 		load_plugin_textdomain( 'lmfwppt', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

// 		// REST API File
// 		require_once( dirname( __FILE__ ) . '/admin/API.php' );
// 		$licenseManagerForWPPTRestAPI = new LicenseManagerForWPPTRestAPI();
// 	}

// 	/**
// 	 * File Includes
// 	 */
// 	function includes() {
// 		require_once( dirname( __FILE__ ) . '/admin/functions.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/Menu.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/DBMigration.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/ProductsHandler.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/ProductsListTable.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/LicenseHandler.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/LicenseListTable.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/SettingsHandler.php' );
// 		require_once( dirname( __FILE__ ) . '/admin/SdkGeneratorHandler.php' );

// 	}

// 	/**
// 	 * Enqueue Frontend Scripts
// 	 */
// 	function enqueue_scripts() {
// 		$ver = current_time( 'timestamp' );

// 	    wp_enqueue_style( 'lmfwppt-styles', LMFWPPT_PLUGIN_URL . 'assets/css/styles.css', null, $ver );
// 	    wp_enqueue_script( 'lmfwppt-scripts', LMFWPPT_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), $ver );

// 		wp_localize_script( 'lmfwppt-scripts', 'lmfwppt_params',
//          	array(
//          	    'nonce' => wp_create_nonce( 'lmwppt_nonce' ),
//          	    'ajaxurl' => admin_url( 'admin-ajax.php' ),
//          	)
//          );

// 	}


// 	/**
// 	 *  Plugin Deactivation
// 	 */
// 	function plugin_deactivation() {

// 	}

// 	/**
// 	 * Enqueue admin script
// 	 *
// 	 */
// 	function admin_scripts( $hook ) {
// 	    if ( 'options-permalink.php' != $hook ) {
// 	        //return;
// 	    }

// 	    $ver = current_time( 'timestamp' );
	    
// 	    wp_enqueue_media();

// 	    // Load the datepicker script (pre-registered in WordPress).
// 	    wp_enqueue_script( 'jquery-ui-datepicker' );

// 	    // jQuery UI CSS on a CDN.
// 	    wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
// 	    wp_enqueue_style( 'jquery-ui' );  

// 	    wp_enqueue_style( 'lmfwppt-admin-styles', LMFWPPT_PLUGIN_URL . 'admin/assets/css/admin.css', null, $ver );
// 	    wp_enqueue_script( 'lmwppt-admin-scripts', LMFWPPT_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), $ver );


// 	}

// }
