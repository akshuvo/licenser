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
	 * Sanitize and persist plugin settings.
	 *
	 * @param array $data Raw settings input.
	 * @return array Sanitized settings saved to the database.
	 */
	public function update( $data ) {
		$data = is_array( $data ) ? $data : [];

		$allowed_methods = [ 'microtime', 'wp_generate', 'uuid' ];
		$method          = isset( $data['license_generate_method'] ) ? sanitize_key( $data['license_generate_method'] ) : 'microtime';
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			$method = 'microtime';
		}

		$char_limit = isset( $data['license_code_character_limit'] ) ? absint( $data['license_code_character_limit'] ) : 32;
		if ( $char_limit < 8 ) {
			$char_limit = 8;
		}

		$sanitized = [
			'license_code_prefix'           => isset( $data['license_code_prefix'] ) ? sanitize_text_field( $data['license_code_prefix'] ) : '',
			'license_generate_method'       => $method,
			'license_code_character_limit'  => $char_limit,
			'hide_wclm_info_from_cart'      => ( isset( $data['hide_wclm_info_from_cart'] ) && 'on' === $data['hide_wclm_info_from_cart'] ) ? 'on' : '',
			'hide_wclm_info_from_ordermeta' => ( isset( $data['hide_wclm_info_from_ordermeta'] ) && 'on' === $data['hide_wclm_info_from_ordermeta'] ) ? 'on' : '',
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
	 * Get all saved settings.
	 *
	 * @return array
	 */
	public function get_all() {
		$settings = get_option( self::OPTION_NAME, [] );
		return is_array( $settings ) ? $settings : [];
	}
}
