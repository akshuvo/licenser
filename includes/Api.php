<?php

namespace Licenser;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API bootstrapper class.
 */
class Api {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Hook REST route registration.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register routes for all Licenser REST controllers.
	 *
	 * @return void
	 */
	public function register_routes() {
		$controllers = [
			'products' => 'Products',
			'licenses' => 'Licenses',
			'settings' => 'Settings',
			'import'   => 'Import',
			'public'   => 'Public_Apis',
		];

		foreach ( $controllers as $controller ) {
			$class_name = __NAMESPACE__ . '\\Api\\' . $controller;
			$instance   = new $class_name();
			$instance->register_routes();
		}
	}
}
