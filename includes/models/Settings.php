<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings model class.
 */
class Settings {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Option name storing plugin settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'licenser_settings';

	/**
	 * Default settings values.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return [
			'license_code_prefix'                => '',
			'license_generate_method'            => 'microtime',
			'license_code_character_limit'       => 32,
			'hide_wclm_info_from_cart'           => '',
			'hide_wclm_info_from_ordermeta'      => '',
			'wc_quantity_mode'                   => 'separate_keys',
			'allow_customer_domain_deactivation' => 'on',
			'auto_suspend_on_refund'             => 'on',
			'integrations'                       => [
				'woocommerce' => 'on',
			],
		];
	}

	/**
	 * Sanitize an integrations enabled/disabled map.
	 *
	 * @param array $raw Raw integrations map.
	 * @return array
	 */
	private function sanitize_integrations( $raw ) {
		$sanitized = [];

		if ( ! is_array( $raw ) ) {
			return $sanitized;
		}

		foreach ( $raw as $slug => $state ) {
			$clean_slug = sanitize_key( (string) $slug );
			if ( '' === $clean_slug ) {
				continue;
			}

			$is_on = ( true === $state || 'on' === $state || '1' === (string) $state || 'true' === $state );

			$sanitized[ $clean_slug ] = $is_on ? 'on' : 'off';
		}

		return $sanitized;
	}

	/**
	 * Sanitize and persist plugin settings.
	 *
	 * @param array $data Raw settings input.
	 * @return array Sanitized settings saved to the database.
	 */
	public function update( $data ) {
		$data     = is_array( $data ) ? $data : [];
		$existing = $this->get_all();

		$allowed_methods = [ 'microtime', 'wp_generate', 'uuid' ];
		$method          = isset( $data['license_generate_method'] ) ? sanitize_key( $data['license_generate_method'] ) : 'microtime';
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			$method = 'microtime';
		}

		$char_limit = isset( $data['license_code_character_limit'] ) ? absint( $data['license_code_character_limit'] ) : 32;
		if ( $char_limit < 8 ) {
			$char_limit = 8;
		}
		if ( $char_limit > 64 ) {
			$char_limit = 64;
		}

		$allowed_qty_modes = [ 'separate_keys', 'multiply_domains', 'single_legacy' ];
		$qty_mode          = isset( $data['wc_quantity_mode'] ) ? sanitize_key( $data['wc_quantity_mode'] ) : 'separate_keys';
		if ( ! in_array( $qty_mode, $allowed_qty_modes, true ) ) {
			$qty_mode = 'separate_keys';
		}

		$integrations = isset( $existing['integrations'] ) && is_array( $existing['integrations'] ) ? $existing['integrations'] : [];
		if ( isset( $data['integrations'] ) && is_array( $data['integrations'] ) ) {
			$integrations = array_merge( $integrations, $this->sanitize_integrations( $data['integrations'] ) );
		}

		$sanitized = [
			'license_code_prefix'                => isset( $data['license_code_prefix'] ) ? sanitize_text_field( $data['license_code_prefix'] ) : '',
			'license_generate_method'            => $method,
			'license_code_character_limit'       => $char_limit,
			'hide_wclm_info_from_cart'           => ( isset( $data['hide_wclm_info_from_cart'] ) && 'on' === $data['hide_wclm_info_from_cart'] ) ? 'on' : '',
			'hide_wclm_info_from_ordermeta'      => ( isset( $data['hide_wclm_info_from_ordermeta'] ) && 'on' === $data['hide_wclm_info_from_ordermeta'] ) ? 'on' : '',
			'wc_quantity_mode'                   => $qty_mode,
			'allow_customer_domain_deactivation' => ( isset( $data['allow_customer_domain_deactivation'] ) && 'on' === $data['allow_customer_domain_deactivation'] ) ? 'on' : '',
			'auto_suspend_on_refund'             => ( isset( $data['auto_suspend_on_refund'] ) && 'on' === $data['auto_suspend_on_refund'] ) ? 'on' : '',
			'integrations'                       => $integrations,
		];

		/**
		 * Filter sanitized settings before saving.
		 *
		 * @param array $sanitized Sanitized settings array.
		 * @param array $data      Raw input payload.
		 */
		$sanitized = apply_filters( 'licenser_sanitize_settings', $sanitized, $data );

		update_option( self::OPTION_NAME, $sanitized );

		return $sanitized;
	}

	/**
	 * Enable or disable a single integration without modifying other settings.
	 *
	 * @param string $slug    Integration identifier (e.g., 'woocommerce', 'edd').
	 * @param bool   $enabled Whether the integration should be enabled.
	 * @return array Updated settings array.
	 */
	public function set_integration_enabled( $slug, $enabled ) {
		$clean_slug = sanitize_key( (string) $slug );
		$settings   = $this->get_all();

		if ( '' === $clean_slug ) {
			return $settings;
		}

		if ( ! isset( $settings['integrations'] ) || ! is_array( $settings['integrations'] ) ) {
			$settings['integrations'] = [];
		}

		$settings['integrations'][ $clean_slug ] = $enabled ? 'on' : 'off';

		update_option( self::OPTION_NAME, $settings );

		return $settings;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $name Setting key.
	 * @return mixed
	 */
	public function get( $name = '' ) {
		$settings = $this->get_all();

		return isset( $settings[ $name ] ) ? $settings[ $name ] : '';
	}

	/**
	 * Get all saved settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_all() {
		$defaults = $this->get_defaults();
		$settings = get_option( self::OPTION_NAME, [] );
		$settings = is_array( $settings ) ? $settings : [];

		$merged = wp_parse_args( $settings, $defaults );

		$saved_integrations     = isset( $settings['integrations'] ) && is_array( $settings['integrations'] ) ? $settings['integrations'] : [];
		$merged['integrations'] = wp_parse_args( $saved_integrations, $defaults['integrations'] );

		return $merged;
	}
}
