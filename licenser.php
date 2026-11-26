<?php
/**
 * Plugin Name: Licenser – Self-Hosted License & Update Server
 * Plugin URI: https://github.com/akshuvo/licenser
 * Description: Self-hosted license manager & automatic update server for WordPress plugins and themes — with domain limits, client SDK & WooCommerce delivery.
 * Author: AddonMaster
 * Author URI: https://addonmaster.com
 * Version: 1.0.8
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 11.3
 * Text Domain: licenser
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Licenser
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Autoload.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * The main plugin class.
 */
final class Licenser {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.8';

	/**
	 * Class constructor.
	 */
	private function __construct() {
		$this->define_constants();

		register_activation_hook( __FILE__, [ $this, 'activate' ] );

		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
		add_action( 'before_woocommerce_init', [ $this, 'declare_woocommerce_compatibility' ] );
	}

	/**
	 * Initializes a singleton instance.
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
	 * Define the required plugin constants.
	 *
	 * @return void
	 */
	public function define_constants() {
		define( 'LICENSER_VERSION', self::VERSION );
		define( 'LICENSER_FILE', __FILE__ );
		define( 'LICENSER_PATH', __DIR__ );
		define( 'LICENSER_URL', plugins_url( '', LICENSER_FILE ) );
		define( 'LICENSER_ASSETS', LICENSER_URL . '/assets' );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init_plugin() {
		$this->maybe_upgrade();

		if ( is_admin() ) {
			new Licenser\Admin();
		}

		// REST API.
		new Licenser\Api();

		// Addons.
		Licenser\Controllers\Addons::instance();

		/**
		 * Fires after Licenser core has initialized.
		 *
		 * @since 1.0.8
		 */
		do_action( 'licenser_loaded' );
	}

	/**
	 * Declare WooCommerce feature compatibility (including HPOS).
	 *
	 * @return void
	 */
	public function declare_woocommerce_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', LICENSER_FILE, true );
		}
	}

	/**
	 * Run database upgrades when the stored DB version lags behind the schema.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( (int) get_option( 'licenser_db_version', 0 ) < Licenser\Installer::DB_VERSION ) {
			$installer = new Licenser\Installer();
			$installer->run();
		}
	}

	/**
	 * Do stuff upon plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$installer = new Licenser\Installer();
		$installer->run();
	}
}

/**
 * Initialize the plugin and attach custom table names to $wpdb.
 *
 * @return \Licenser
 */
function licenser() { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
	global $wpdb;

	foreach (
		[
			'products',
			'product_releases',
			'license_packages',
			'licenses',
			'license_meta',
			'license_domains',
		] as $table
	) {
		$wpdb->__set( 'licenser_' . $table, $wpdb->prefix . 'licenser_' . $table );
	}

	return Licenser::init();
}

// Run the plugin.
licenser();
