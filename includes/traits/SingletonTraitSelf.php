<?php
/**
 * Singleton class trait.
 */

namespace Licenser\Traits;

/**
 * Singleton trait.
 */
trait SingletonTraitSelf {
	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
    final public static function instance() {
        if (null === static::$instance) {
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
	 */
	final public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'tinytopcrm' ), '4.6' );
		die();
	}
}
