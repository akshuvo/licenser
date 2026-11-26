<?php

namespace Licenser\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\Settings;

/**
 * Addons & store integrations registry and conditional loader.
 *
 * Disabled integrations (and integrations whose parent plugin is inactive)
 * are never instantiated, preventing PHP from autoloading or including their files.
 */
class Addons {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Instantiated integration objects keyed by slug.
	 *
	 * @var array<string, object>
	 */
	private $loaded = [];

	/**
	 * Initialize enabled built-in integrations and allow Pro/external addons to register.
	 */
	private function __construct() {
		$this->load_enabled_integrations();

		/**
		 * Fires after built-in Licenser integrations have been evaluated and loaded.
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

	/**
	 * Return all registered store integrations.
	 *
	 * Third-party or Pro addons (e.g., Easy Digital Downloads, SureCart) can
	 * register or override entries via the `licenser_integrations` filter.
	 *
	 * @return array<string, array>
	 */
	public function get_integrations() {
		$integrations = [
			'woocommerce' => [
				'slug'             => 'woocommerce',
				'name'             => __( 'WooCommerce Lite', 'licenser' ),
				'badge_origin'     => __( 'Built-in', 'licenser' ),
				'description'      => __( 'Sell licensed plugins and themes directly with WooCommerce simple and variable products — HPOS compatible out of the box.', 'licenser' ),
				'icon'             => 'dashicons-cart',
				'icon_style'       => 'purple',
				'class'            => '\\Licenser\\Addons\\WooLite',
				'dependency_name'  => 'WooCommerce',
				'dependency_check' => static function () {
					return class_exists( 'WooCommerce' );
				},
				'available'        => true,
				'default_enabled'  => true,
				'settings_url'     => admin_url( 'admin.php?page=licenser-settings&tab=woocommerce' ),
				'features'         => [
					__( 'Automatic key delivery in order emails & HPOS meta box', 'licenser' ),
					__( 'Multi-quantity licensing & auto-suspend on refund/cancel', 'licenser' ),
					__( 'Customer My Account portal with self-service deactivation', 'licenser' ),
				],
			],
			'edd'         => [
				'slug'             => 'edd',
				'name'             => __( 'Easy Digital Downloads', 'licenser' ),
				'badge_origin'     => __( 'Coming Soon', 'licenser' ),
				'description'      => __( 'Connect Easy Digital Downloads products to Licenser for automated license generation, purchase receipts, and customer self-service.', 'licenser' ),
				'icon'             => 'dashicons-download',
				'icon_style'       => 'blue',
				'class'            => '',
				'dependency_name'  => 'Easy Digital Downloads',
				'dependency_check' => static function () {
					return class_exists( 'Easy_Digital_Downloads' );
				},
				'available'        => false,
				'default_enabled'  => false,
				'settings_url'     => '',
				'features'         => [
					__( 'Map EDD Downloads & Variable Prices to Licenser packages', 'licenser' ),
					__( 'Automatic license key delivery on payment completion', 'licenser' ),
					__( 'Zero-overhead conditional loading when disabled', 'licenser' ),
				],
			],
		];

		/**
		 * Filter the registered Licenser store integrations.
		 *
		 * @param array<string, array> $integrations Integration definitions keyed by slug.
		 */
		$filtered = apply_filters( 'licenser_integrations', $integrations );

		return is_array( $filtered ) ? $filtered : $integrations;
	}

	/**
	 * Retrieve a single integration definition by slug.
	 *
	 * @param string $slug Integration slug.
	 * @return array|null
	 */
	public function get_integration( $slug ) {
		$slug         = sanitize_key( (string) $slug );
		$integrations = $this->get_integrations();

		return isset( $integrations[ $slug ] ) ? $integrations[ $slug ] : null;
	}

	/**
	 * Check whether an integration ships executable code in this installation.
	 *
	 * @param string $slug Integration slug.
	 * @return bool
	 */
	public function is_available( $slug ) {
		$integration = $this->get_integration( $slug );

		return ! empty( $integration['available'] ) && ! empty( $integration['class'] );
	}

	/**
	 * Check whether an integration is enabled in Licenser settings.
	 *
	 * @param string $slug Integration slug.
	 * @return bool
	 */
	public function is_enabled( $slug ) {
		$slug        = sanitize_key( (string) $slug );
		$integration = $this->get_integration( $slug );

		if ( ! $integration || empty( $integration['available'] ) ) {
			return false;
		}

		$saved = Settings::instance()->get( 'integrations' );
		if ( is_array( $saved ) && array_key_exists( $slug, $saved ) ) {
			return 'on' === $saved[ $slug ];
		}

		return ! empty( $integration['default_enabled'] );
	}

	/**
	 * Check whether the integration's host plugin dependency is active.
	 *
	 * @param string $slug Integration slug.
	 * @return bool
	 */
	public function is_dependency_active( $slug ) {
		$integration = $this->get_integration( $slug );

		if ( ! $integration ) {
			return false;
		}

		if ( isset( $integration['dependency_check'] ) && is_callable( $integration['dependency_check'] ) ) {
			return (bool) call_user_func( $integration['dependency_check'] );
		}

		return true;
	}

	/**
	 * Check whether an integration is enabled and its dependency is active.
	 *
	 * @param string $slug Integration slug.
	 * @return bool
	 */
	public function is_active( $slug ) {
		return $this->is_available( $slug ) && $this->is_enabled( $slug ) && $this->is_dependency_active( $slug );
	}

	/**
	 * Check whether an integration's PHP bootstrap class was actually loaded in this request.
	 *
	 * @param string $slug Integration slug.
	 * @return bool
	 */
	public function is_loaded( $slug ) {
		$slug = sanitize_key( (string) $slug );

		return isset( $this->loaded[ $slug ] );
	}

	/**
	 * Instantiate only enabled integrations whose dependencies are active.
	 *
	 * Because Composer PSR-4 autoloads classes lazily on first reference,
	 * skipping disabled integrations guarantees none of their files are included.
	 *
	 * @return void
	 */
	private function load_enabled_integrations() {
		foreach ( $this->get_integrations() as $slug => $integration ) {
			if ( empty( $integration['available'] ) || empty( $integration['class'] ) ) {
				continue;
			}

			if ( ! $this->is_enabled( $slug ) ) {
				continue;
			}

			if ( ! $this->is_dependency_active( $slug ) ) {
				continue;
			}

			$class_name = (string) $integration['class'];
			if ( class_exists( $class_name ) ) {
				$this->loaded[ $slug ] = new $class_name();
			}
		}
	}
}
