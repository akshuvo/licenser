<?php

namespace Licenser\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Addons loader controller.
 */
class Addons {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Initialize built-in addons and allow Pro/external addons to register.
	 */
	private function __construct() {
		new \Licenser\Addons\Wc_Lite();

		/**
		 * Fires after built-in Licenser addons are loaded.
		 *
		 * @param self $addons Addons controller instance.
		 */
		do_action( 'licenser_addons_register', $this );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
