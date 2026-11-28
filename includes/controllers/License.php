<?php

namespace Licenser\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License as License_Model;
use WP_Error;
use WP_REST_Request;

/**
 * License lifecycle controller.
 */
class License {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Validate a license key against status, product ownership, expiry, and optional domain limits.
	 *
	 * @param string $license_key License key string.
	 * @param array  $args        Validation options (`url`, `product_id`, `enforce_domain_limit`, `reject_inactive_domain`).
	 * @return object|WP_Error License record object on success, or WP_Error on failure.
	 */
	public function check( $license_key, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'url'                    => '',
				'product_id'             => 0,
				'enforce_domain_limit'   => false,
				'reject_inactive_domain' => true,
			]
		);

		$license_key = sanitize_text_field( (string) $license_key );

		if ( '' === $license_key ) {
			return new WP_Error( 'invalid_license', __( 'License key is required.', 'licenser' ) );
		}

		$license = License_Model::instance()->get( $license_key, [ 'get_by' => 'key' ] );

		if ( empty( $license ) ) {
			return new WP_Error( 'invalid_license', __( 'Invalid license key.', 'licenser' ) );
		}

		// Verify license is active.
		if ( 1 !== (int) $license->status ) {
			return new WP_Error( 'inactive_license', __( 'License key is inactive.', 'licenser' ) );
		}

		// Verify license belongs to the requested product when product_id is supplied.
		if ( ! empty( $args['product_id'] ) ) {
			$matches_product = ( (int) $license->product_id === (int) $args['product_id'] );
			/**
			 * Filter whether a license is authorized for the requested product ID (e.g. Bundle / All-Access passes).
			 *
			 * @param bool   $matches_product Whether license product_id directly matches.
			 * @param object $license         License record.
			 * @param int    $product_id      Requested product ID.
			 * @param array  $args            Validation arguments.
			 */
			if ( ! apply_filters( 'licenser_license_matches_product', $matches_product, $license, (int) $args['product_id'], $args ) ) {
				return new WP_Error( 'invalid_product', __( 'License key is not valid for this product.', 'licenser' ) );
			}
		}

		// Verify expiration for non-lifetime licenses.
		if ( 1 !== (int) $license->is_lifetime && ! empty( $license->end_date ) ) {
			$end_date = strtotime( (string) $license->end_date );
			if ( false !== $end_date && $end_date < time() ) {
				return new WP_Error( 'expired_license', __( 'License is expired.', 'licenser' ) );
			}
		}

		// Inspect domain registration if URL was provided.
		$domain_row                = ! empty( $args['url'] ) ? License_Model::instance()->get_domain( $args['url'], $license->id ) : null;
		$license->exists_domain_id = $domain_row ? (int) $domain_row->id : false;
		$license->domain_status    = $domain_row ? (int) $domain_row->status : null;

		if ( $args['reject_inactive_domain'] && $domain_row && 1 !== (int) $domain_row->status ) {
			return new WP_Error( 'domain_inactive', __( 'License is deactivated for this domain.', 'licenser' ) );
		}

		// Enforce domain activation limit when activating a new/inactive domain.
		$enforce_limit = apply_filters( 'licenser_should_enforce_domain_limit', (bool) $args['enforce_domain_limit'], $license, $args );

		if ( $enforce_limit && (int) $license->domain_limit > 0 ) {
			$domain_count = (int) License_Model::instance()->get_domains(
				[
					'license_id'  => $license->id,
					'status'      => 1,
					'count_total' => true,
				]
			);

			/**
			 * Filter the counted active domains used for activation-limit enforcement.
			 *
			 * @param int    $domain_count Raw active domain count.
			 * @param object $license      License record.
			 * @param string $context      Call context (`check`, `refresh`, `admin_edit`, `portal`).
			 * @param array  $args         Optional request arguments.
			 */
			$domain_count      = max( 0, (int) apply_filters( 'licenser_active_domain_count', $domain_count, $license, 'check', $args ) );
			$is_already_active = ( $domain_row && 1 === (int) $domain_row->status );

			if ( ! $is_already_active && $domain_count >= (int) $license->domain_limit ) {
				return new WP_Error( 'domain_limit_exceeded', __( 'Domain activation limit exceeded.', 'licenser' ) );
			}
		}

		return $license;
	}

	/**
	 * Public check endpoint handler (validates status, expiry, product match, and domain state).
	 *
	 * @param array $args Request parameters (`license_key`, `url`, `product_id`).
	 * @return array
	 */
	public function check_public( $args ) {
		$args = wp_parse_args(
			$args,
			[
				'license_key' => '',
				'url'         => '',
				'product_id'  => 0,
			]
		);

		$license = $this->check(
			$args['license_key'],
			[
				'url'                    => $args['url'],
				'product_id'             => $args['product_id'],
				'enforce_domain_limit'   => false,
				'reject_inactive_domain' => true,
			]
		);

		if ( is_wp_error( $license ) ) {
			/**
			 * Fires when a public license check fails validation.
			 *
			 * @param WP_Error $license Validation error.
			 * @param array    $args    Request parameters.
			 * @param string   $action  Action name (`check` or `activate`).
			 */
			do_action( 'licenser_license_check_failed', $license, $args, 'check' );

			return [
				'success' => false,
				'error'   => $license->get_error_message(),
			];
		}

		/**
		 * Fires after a public license check succeeds.
		 *
		 * @param object $license License record.
		 * @param array  $args    Request parameters.
		 */
		do_action( 'licenser_license_checked', $license, $args );

		return $this->refresh( $license->id, 'id' );
	}

	/**
	 * Activate a license for a site URL.
	 *
	 * @param array $args Activation parameters (`license_key`, `url`, `product_id`, `is_local`, `client`).
	 * @return array
	 */
	public function activate( $args ) {
		$args = wp_parse_args(
			$args,
			[
				'license_key' => '',
				'url'         => '',
				'product_id'  => 0,
				'is_local'    => false,
				'client'      => '',
			]
		);

		if ( empty( $args['product_id'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Invalid product ID.', 'licenser' ),
			];
		}

		$clean_url = licenser_get_clean_url( $args['url'] );
		if ( '' === $clean_url ) {
			return [
				'success' => false,
				'error'   => __( 'Site URL is required to activate a license.', 'licenser' ),
			];
		}

		$license = $this->check(
			$args['license_key'],
			[
				'url'                    => $args['url'],
				'product_id'             => $args['product_id'],
				'is_local'               => (bool) $args['is_local'],
				'enforce_domain_limit'   => true,
				'reject_inactive_domain' => false,
			]
		);

		if ( is_wp_error( $license ) ) {
			/**
			 * Fires when a public license activation fails validation.
			 *
			 * @param WP_Error $license Validation error.
			 * @param array    $args    Activation parameters.
			 * @param string   $action  Action name (`check` or `activate`).
			 */
			do_action( 'licenser_license_check_failed', $license, $args, 'activate' );

			return [
				'success' => false,
				'error'   => $license->get_error_message(),
			];
		}

		// Idempotent: skip DB write if this domain is already active on this license.
		if ( ! ( $license->exists_domain_id && 1 === (int) $license->domain_status ) ) {
			License_Model::instance()->add_domain(
				[
					'id'         => $license->exists_domain_id ? $license->exists_domain_id : '',
					'license_id' => $license->id,
					'domain'     => $args['url'],
					'status'     => 1,
				]
			);
		}

		/**
		 * Fires after a license is activated for a domain.
		 *
		 * @param object $license License record.
		 * @param array  $args    Activation parameters.
		 */
		do_action( 'licenser_license_activated', $license, $args );

		return $this->refresh( $license->id, 'id' );
	}

	/**
	 * Build the public license state payload expected by LicenserWP/client SDK.
	 *
	 * @param int|string $license_key License key or numeric ID.
	 * @param string     $get_by      Lookup mode (`key` or `id`).
	 * @return array
	 */
	public function refresh( $license_key, $get_by = 'key' ) {
		$license = License_Model::instance()->get( $license_key, [ 'get_by' => $get_by ] );

		if ( ! $license ) {
			return [
				'success' => false,
				'error'   => __( 'Invalid license key.', 'licenser' ),
			];
		}

		$domain_count = (int) License_Model::instance()->get_domains(
			[
				'license_id'  => $license->id,
				'status'      => 1,
				'count_total' => true,
			]
		);
		$domain_count = max( 0, (int) apply_filters( 'licenser_active_domain_count', $domain_count, $license, 'refresh', [] ) );

		$license->domain_count = $domain_count;

		$domain_limit = (int) $license->domain_limit;
		$remaining    = $domain_limit > 0 ? max( 0, $domain_limit - $domain_count ) : 0;
		$is_lifetime  = ( 1 === (int) $license->is_lifetime );

		$expiry_days = false;
		$expiry_date = false;

		if ( ! $is_lifetime && ! empty( $license->end_date ) ) {
			$end_timestamp = strtotime( (string) $license->end_date );
			if ( false !== $end_timestamp ) {
				$expiry_days = (int) ceil( ( $end_timestamp - time() ) / DAY_IN_SECONDS );
				$expiry_date = $license->end_date;
			}
		}

		return apply_filters(
			'licenser_refresh_license_public_response',
			[
				'success'          => true,
				'status'           => (int) $license->status,
				'remaining'        => $remaining,
				'activation_count' => $domain_count,
				'activation_limit' => $domain_limit,
				'expiry_days'      => $expiry_days,
				'expiry_date'      => $expiry_date,
				'title'            => '',
			],
			$license
		);
	}

	/**
	 * Deactivate a license for a calling site URL and free its activation slot.
	 *
	 * @param WP_REST_Request|array $args Request object or array (`license_key`, `url`, `product_id`).
	 * @return array
	 */
	public function deactivate( $args ) {
		if ( $args instanceof WP_REST_Request ) {
			$params = [
				'license_key' => (string) $args->get_param( 'license_key' ),
				'url'         => (string) $args->get_param( 'url' ),
				'product_id'  => absint( $args->get_param( 'product_id' ) ),
			];
		} else {
			$params = wp_parse_args(
				is_array( $args ) ? $args : [],
				[
					'license_key' => '',
					'url'         => '',
					'product_id'  => 0,
				]
			);
		}

		$license_key = sanitize_text_field( (string) $params['license_key'] );
		if ( '' === $license_key ) {
			return [
				'success' => false,
				'error'   => __( 'License key is required.', 'licenser' ),
			];
		}

		$license = License_Model::instance()->get( $license_key, [ 'get_by' => 'key' ] );
		if ( empty( $license ) ) {
			return [
				'success' => false,
				'error'   => __( 'Invalid license key.', 'licenser' ),
			];
		}

		if ( ! empty( $params['product_id'] ) ) {
			$matches_product = ( (int) $license->product_id === (int) $params['product_id'] );
			if ( ! apply_filters( 'licenser_license_matches_product', $matches_product, $license, (int) $params['product_id'], $params ) ) {
				return [
					'success' => false,
					'error'   => __( 'License key is not valid for this product.', 'licenser' ),
				];
			}
		}

		$domain_row = ! empty( $params['url'] ) ? License_Model::instance()->get_domain( $params['url'], $license->id ) : null;
		if ( $domain_row ) {
			License_Model::instance()->delete_domain( (int) $domain_row->id );
		}

		/**
		 * Fires after a license is deactivated for a domain.
		 *
		 * @param object $license License record.
		 * @param string $url     Deactivated site URL.
		 */
		do_action( 'licenser_license_deactivated', $license, $params['url'] );

		return [
			'success' => true,
			'message' => __( 'License deactivated successfully.', 'licenser' ),
		];
	}
}
