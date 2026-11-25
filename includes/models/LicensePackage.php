<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License package model class.
 */
class LicensePackage {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Allowed columns in the license_packages table.
	 *
	 * @var string[]
	 */
	const ALLOWED_COLUMNS = [
		'id',
		'product_id',
		'label',
		'package_id',
		'update_period',
		'domain_limit',
	];

	/**
	 * Get a single license package by integer ID.
	 *
	 * @param int $id Package ID.
	 * @return object|false
	 */
	public function get( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$package = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->licenser_license_packages} WHERE id = %d",
				absint( $id )
			)
		);

		if ( empty( $package ) ) {
			return false;
		}

		return $package;
	}

	/**
	 * Get license packages matching arguments.
	 *
	 * @param array $args Query arguments.
	 * @return array|false
	 */
	public function get_all( $args = [] ) {
		global $wpdb;

		$defaults = [
			'number'      => 20,
			'offset'      => 0,
			'orderby'     => 'id',
			'order'       => 'DESC',
			'count_total' => true,
			'product_id'  => '',
			'package_id'  => '',
		];

		$args  = wp_parse_args( $args, $defaults );
		$where = '1=1';

		if ( ! empty( $args['product_id'] ) ) {
			$where .= $wpdb->prepare( ' AND product_id = %d', absint( $args['product_id'] ) );
		}

		if ( ! empty( $args['package_id'] ) ) {
			$where .= $wpdb->prepare( ' AND package_id = %s', sanitize_text_field( $args['package_id'] ) );
		}

		$orderby = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$limit_sql = '';
		if ( -1 !== (int) $args['number'] ) {
			$limit_sql = $wpdb->prepare( ' LIMIT %d, %d', absint( $args['offset'] ), absint( $args['number'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$packages = $wpdb->get_results( "SELECT * FROM {$wpdb->licenser_license_packages} WHERE {$where} ORDER BY {$orderby} {$order}{$limit_sql}" );

		if ( empty( $packages ) ) {
			return false;
		}

		return $packages;
	}

	/**
	 * Create or update a license package.
	 *
	 * @param array $data Package payload.
	 * @return int Package ID.
	 */
	public function create( $data ) {
		$data = wp_parse_args(
			$data,
			[
				'product_id'    => 0,
				'package_id'    => '',
				'label'         => '',
				'update_period' => 0,
				'domain_limit'  => 0,
			]
		);

		global $wpdb;

		$product_id = absint( $data['product_id'] );
		$package_id = ! empty( $data['package_id'] ) ? sanitize_text_field( $data['package_id'] ) : wp_generate_uuid4();

		if ( ! empty( $data['id'] ) ) {
			$insert_id = absint( $data['id'] );

			$update_fields = [
				'product_id'    => $product_id,
				'label'         => sanitize_text_field( $data['label'] ),
				'update_period' => absint( $data['update_period'] ),
				'domain_limit'  => absint( $data['domain_limit'] ),
			];

			if ( ! empty( $data['package_id'] ) ) {
				$update_fields['package_id'] = $package_id;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->licenser_license_packages,
				$update_fields,
				[ 'id' => $insert_id ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->licenser_license_packages,
				[
					'product_id'    => $product_id,
					'package_id'    => $package_id,
					'label'         => sanitize_text_field( $data['label'] ),
					'update_period' => absint( $data['update_period'] ),
					'domain_limit'  => absint( $data['domain_limit'] ),
				]
			);

			$insert_id = (int) $wpdb->insert_id;
		}

		if ( $product_id > 0 ) {
			Product::instance()->clear_cache( $product_id );
		}

		return $insert_id;
	}

	/**
	 * Delete a license package by ID.
	 *
	 * @param int $id Package ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$id      = absint( $id );
		$package = $this->get( $id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->licenser_license_packages,
			[ 'id' => $id ],
			[ '%d' ]
		);

		if ( $package && ! empty( $package->product_id ) ) {
			Product::instance()->clear_cache( (int) $package->product_id );
		}

		return true;
	}
}
