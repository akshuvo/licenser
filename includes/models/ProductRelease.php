<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product release model class.
 */
class ProductRelease {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Allowed columns in the product_releases table.
	 *
	 * @var string[]
	 */
	const ALLOWED_COLUMNS = [
		'id',
		'product_id',
		'version',
		'changelog',
		'file_name',
		'download_link',
		'release_date',
	];

	/**
	 * Sanitize a column list against allowed release columns.
	 *
	 * @param array|string $columns Requested columns.
	 * @return string
	 */
	protected function sanitize_columns( $columns ) {
		if ( empty( $columns ) || '*' === $columns ) {
			return '*';
		}

		if ( is_string( $columns ) ) {
			$columns = explode( ',', $columns );
		}

		$safe = [];
		foreach ( (array) $columns as $column ) {
			$column = trim( (string) $column );
			if ( in_array( $column, self::ALLOWED_COLUMNS, true ) ) {
				$safe[] = $column;
			}
		}

		return ! empty( $safe ) ? implode( ', ', $safe ) : '*';
	}

	/**
	 * Get a single release by release ID or product ID.
	 *
	 * @param int   $id   Release ID or product ID.
	 * @param array $args Query arguments.
	 * @return object|null
	 */
	public function get( $id, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'columns' => [],
				'get_by'  => '',
				'version' => '',
			]
		);

		global $wpdb;

		$columns = $this->sanitize_columns( $args['columns'] );
		$where   = '1=1';

		if ( 'product_id' === $args['get_by'] ) {
			$where .= $wpdb->prepare( ' AND product_id = %d', absint( $id ) );
		} else {
			$where .= $wpdb->prepare( ' AND id = %d', absint( $id ) );
		}

		if ( ! empty( $args['version'] ) ) {
			$where .= $wpdb->prepare( ' AND version = %s', sanitize_text_field( $args['version'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT {$columns} FROM {$wpdb->licenser_product_releases} WHERE {$where} ORDER BY id DESC, release_date DESC LIMIT 1" );
	}

	/**
	 * Get the latest stable release for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return object|null
	 */
	public function get_stable( $product_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->licenser_product_releases} WHERE product_id = %d ORDER BY id DESC, release_date DESC LIMIT 1",
				absint( $product_id )
			)
		);
	}

	/**
	 * Get all releases matching arguments.
	 *
	 * @param array $args Query arguments.
	 * @return array
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
		];

		$args  = wp_parse_args( $args, $defaults );
		$where = '1=1';

		if ( ! empty( $args['product_id'] ) ) {
			$where .= $wpdb->prepare( ' AND product_id = %d', absint( $args['product_id'] ) );
		}

		$orderby = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$limit_sql = '';
		if ( ! empty( $args['number'] ) && -1 !== (int) $args['number'] ) {
			$limit_sql = $wpdb->prepare( ' LIMIT %d, %d', absint( $args['offset'] ), absint( $args['number'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$wpdb->licenser_product_releases} WHERE {$where} ORDER BY {$orderby} {$order}{$limit_sql}" );
	}

	/**
	 * Create or update a product release.
	 *
	 * @param array $data Release payload.
	 * @return int Release ID.
	 */
	public function create( $data ) {
		$data = wp_parse_args(
			$data,
			[
				'product_id'    => 0,
				'version'       => '',
				'changelog'     => '',
				'file_name'     => '',
				'download_link' => '',
				'release_date'  => '',
			]
		);

		global $wpdb;

		$product_id   = absint( $data['product_id'] );
		$release_ts   = ! empty( $data['release_date'] ) ? strtotime( (string) $data['release_date'] ) : false;
		$release_date = $release_ts ? licenser_date( 'Y-m-d H:i:s', $release_ts ) : licenser_date( 'Y-m-d H:i:s' );
		$file_name    = ! empty( $data['file_name'] ) ? sanitize_text_field( $data['file_name'] ) : basename( (string) $data['download_link'] );

		$record = [
			'product_id'    => $product_id,
			'version'       => sanitize_text_field( $data['version'] ),
			'changelog'     => wp_kses_post( $data['changelog'] ),
			'file_name'     => $file_name,
			'download_link' => esc_url_raw( $data['download_link'] ),
			'release_date'  => $release_date,
		];

		if ( ! empty( $data['id'] ) ) {
			$insert_id = absint( $data['id'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->licenser_product_releases,
				$record,
				[ 'id' => $insert_id ]
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $wpdb->licenser_product_releases, $record );
			$insert_id = (int) $wpdb->insert_id;
		}

		if ( $product_id > 0 ) {
			Product::instance()->clear_cache( $product_id );
		}

		return $insert_id;
	}
}
