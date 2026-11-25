<?php
/**
 * Singleton class trait.
 *
 * @package Licenser
 */

namespace Licenser\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton trait using `self`.
 */
trait SingletonTraitSelf {
	/**
	 * The single instance of the class.
	 *
	 * @var object|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return static Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @return void
	 */
	final public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'licenser' ), '1.0' );
		die();
	}
}
