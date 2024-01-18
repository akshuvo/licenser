<?php
namespace Licenser\Addons;

/**
 * The Menu handler class
 */
class WcLite {

	/**
	 * Initialize the class
	 */
	function __construct() {
				require_once( dirname( __FILE__ ) . '/functions.php' );
		require_once( dirname( __FILE__ ) . '/WC_Handler.php' );
	}

	// Controller
	

}

// /**
//  * Plugin Name: Licenser - WooCommerce Addon
//  * Plugin URI: https://github.com/akshuvo/license-manager-for-wordpress-plugins-themes-wcextension
//  * Github Plugin URI: https://github.com/akshuvo/license-manager-for-wordpress-plugins-themes-wcextension
//  * Description: Self-Hosted WcExtension license manager for WordPress Plugins and Themes
//  * Author: AddonMaster
//  * Author URI: https://addonmaster.com
//  * Version: 1.0.2
//  * Text Domain: lmfwpptwcext
//  * Domain Path: /lang
//  *
//  */

// /**
// * Including Plugin file for security
// * Include_once
// *
// * @since 1.0.0
// */
// include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// define( 'LMFWPPTWCEXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
// define( 'LMFWPPTWCEXT_PLUGIN_VERSION', '1.0' );

// /**
//  *	EDDNSTANT Functions
//  */
// //require_once( dirname( __FILE__ ) . '/inc/functions.php' );


// /**
//  *	Plugin Main Class
//  */

// final class LMFWPPTWCEXT {

// 	private function __construct() {
// 		// Loaded textdomain
// 		add_action('plugins_loaded', array( $this, 'plugin_loaded_action' ), 10, 2);

// 		// Enqueue frontend scripts
// 		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
// 		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 100 );

// 		// Added plugin action link
// 		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

// 		// trigger upon plugin activation/deactivation
// 		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
// 		//register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

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
// 	 * Adds plugin action links.
// 	 */
// 	function action_links( $links ) {
// 		$plugin_links = array(
// 			'<a href="' . admin_url( 'admin.php?page=license-manager-wppt' ) . '">' . esc_html__( 'License Manager', 'lmfwpptwcext' ) . '</a>',
// 		);
// 		return array_merge( $plugin_links, $links );
// 	}

// 	/**
// 	 * Plugin Loaded Action
// 	 */
// 	function plugin_loaded_action() {
// 		// Loading Text Domain for Internationalization
// 		load_plugin_textdomain( 'lmfwpptwcext', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

// 		require_once( dirname( __FILE__ ) . '/functions.php' );
// 		require_once( dirname( __FILE__ ) . '/WC_Handler.php' );

// 	}

// 	/**
// 	 * Enqueue Frontend Scripts
// 	 */
// 	function enqueue_scripts() {
// 		$ver = current_time( 'timestamp' );

// 	    wp_enqueue_style( 'lmfwpptwcext-styles', LMFWPPTWCEXT_PLUGIN_URL . 'assets/css/styles.css', null, $ver );
// 	    wp_enqueue_script( 'lmfwpptwcext-scripts', LMFWPPTWCEXT_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), $ver );

// 		wp_localize_script( 'lmfwpptwcext-scripts', 'lmfwpptwcext_params',
//          	array(
//          	    'nonce' => wp_create_nonce( 'lmfwpptwcext_nonce' ),
//          	    'ajaxurl' => admin_url( 'admin-ajax.php' ),
//          	)
//          );

// 	}

// 	/**
// 	*  Plugin Activation
// 	*/
// 	function plugin_activation() {

//         if ( ! get_option( 'lmfwpptwcext_installed' ) ) {
//             update_option( 'lmfwpptwcext_installed', time() );
//         }

//         update_option( 'lmfwpptwcext_plugin_version', LMFWPPTWCEXT_PLUGIN_VERSION );

// 	}

// 	/**
// 	*  Plugin Deactivation
// 	*/
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

// 	    wp_enqueue_style( 'lmfwpptwcext-admin-styles', LMFWPPTWCEXT_PLUGIN_URL . 'admin/assets/css/admin.css', null, $ver );
// 	    wp_enqueue_script( 'lmfwpptwcext-admin-scripts', LMFWPPTWCEXT_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), $ver );
// 	}

// }


// /**
//  * Initialize plugin
//  */
// function lmfwpptwcext(){
// 	return LMFWPPTWCEXT::init();
// }

// lmfwpptwcext();

// // Let's start it
// // lmfwpptwcext();

// if( is_plugin_active( 'license-manager-for-wordpress-plugins-themes/license-manager-for-wordpress-plugins-themes.php' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	
// } else {
// 	add_action( 'admin_notices', 'license_manager_for_themes_plugins_inactive_notice' );
// 	add_action( 'admin_notices', 'license_manager_for_themes_plugins_wc_inactive_notice' );
// }
