<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License model class.
 */
class License {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Allowed columns in the licenses table.
	 *
	 * @var string[]
	 */
	const ALLOWED_COLUMNS = [
		'id',
		'status',
		'license_key',
		'product_id',
		'package_id',
		'source',
		'source_id',
		'end_date',
		'is_lifetime',
		'domain_limit',
		'dated',
	];

	/**
	 * Allowed columns in the license_domains table.
	 *
	 * @var string[]
	 */
	const ALLOWED_DOMAIN_COLUMNS = [
		'id',
		'license_id',
		'domain',
		'status',
		'dated',
	];

	/**
	 * Default license field values.
	 *
	 * @var array
	 */
	public $default_fields = [
		'status'       => 1,
		'license_key'  => '',
		'product_id'   => '',
		'package_id'   => '',
		'source'       => '',
		'source_id'    => '',
		'end_date'     => '',
		'is_lifetime'  => 0,
		'domain_limit' => '',
		'dated'        => '',
	];

	/**
	 * Sanitize a column list against an allowlist.
	 *
	 * @param array|string $columns Requested columns.
	 * @param string[]     $allowed Allowed column names.
	 * @return string
	 */
	protected function sanitize_columns( $columns, $allowed = self::ALLOWED_COLUMNS ) {
		if ( empty( $columns ) || '*' === $columns ) {
			return '*';
		}

		if ( is_string( $columns ) ) {
			$columns = explode( ',', $columns );
		}

		$safe = [];
		foreach ( (array) $columns as $column ) {
			$column = trim( (string) $column );
			if ( in_array( $column, $allowed, true ) ) {
				$safe[] = $column;
			}
		}

		return ! empty( $safe ) ? implode( ', ', $safe ) : '*';
	}

	/**
	 * Get a single license by integer ID or license key.
	 *
	 * @param int|string $id   License ID or key string.
	 * @param array      $args Query arguments.
	 * @return object|false
	 */
	public function get( $id, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'columns' => '*',
				'get_by'  => '',
			]
		);

		global $wpdb;

		$columns = esc_sql( $this->sanitize_columns( $args['columns'], self::ALLOWED_COLUMNS ) );

		if ( 'key' === $args['get_by'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$license = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$columns} FROM {$wpdb->licenser_licenses} WHERE license_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					(string) $id
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$license = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT {$columns} FROM {$wpdb->licenser_licenses} WHERE id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					absint( $id )
				)
			);
		}

		if ( empty( $license ) ) {
			return false;
		}

		return $license;
	}

	/**
	 * Get a collection of licenses.
	 *
	 * @param array $args Query arguments.
	 * @return array|false
	 */
	public function get_all( $args = [] ) {
		global $wpdb;

		$defaults = [
			'number'         => 20,
			'offset'         => 0,
			'orderby'        => 'id',
			'order'          => 'DESC',
			'count_total'    => true,
			'product_id'     => '',
			'package_id'     => '',
			'source'         => '',
			'source_id'      => '',
			'status'         => '',
			'license_status' => '',
			'search'         => '',
			'license_key'    => '',
			'end_date'       => '',
			'is_lifetime'    => '',
			'domain_limit'   => '',
			'dated'          => '',
			'inc_domains'    => false,
		];

		$args = wp_parse_args( $args, $defaults );

		$is_source_array = ! empty( $args['source'] ) && is_array( $args['source'] );
		$sources_csv     = '';
		if ( $is_source_array ) {
			$clean_sources = array_values( array_filter( array_map( 'sanitize_key', $args['source'] ) ) );
			if ( empty( $clean_sources ) ) {
				return false;
			}
			$sources_csv = implode( ',', $clean_sources );
		}

		$is_source_id_array = ! empty( $args['source_id'] ) && is_array( $args['source_id'] );
		$source_ids_csv     = '';
		if ( $is_source_id_array ) {
			$source_ids = array_values( array_filter( wp_parse_id_list( $args['source_id'] ) ) );
			if ( empty( $source_ids ) ) {
				return false;
			}
			$source_ids_csv = implode( ',', $source_ids );
		}

		$has_product_id   = ! empty( $args['product_id'] ) ? 1 : 0;
		$has_package_id   = ! empty( $args['package_id'] ) ? 1 : 0;
		$has_source       = ( ! $is_source_array && ! empty( $args['source'] ) ) ? 1 : 0;
		$has_source_names = $is_source_array ? 1 : 0;
		$has_source_id    = ( ! $is_source_id_array && ! empty( $args['source_id'] ) ) ? 1 : 0;
		$has_source_list  = $is_source_id_array ? 1 : 0;
		$has_status       = ( isset( $args['status'] ) && '' !== $args['status'] ) ? 1 : 0;
		$license_status   = sanitize_key( (string) $args['license_status'] );
		$has_search       = ! empty( $args['search'] ) ? 1 : 0;
		$search_like      = $has_search ? '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%' : '';
		$has_license_key  = ! empty( $args['license_key'] ) ? 1 : 0;
		$has_end_date     = ! empty( $args['end_date'] ) ? 1 : 0;
		$end_date_val     = $has_end_date ? sanitize_text_field( (string) $args['end_date'] ) : '1970-01-01 00:00:00';
		$has_is_lifetime  = ( isset( $args['is_lifetime'] ) && '' !== $args['is_lifetime'] ) ? 1 : 0;
		$has_domain_limit = ( isset( $args['domain_limit'] ) && '' !== $args['domain_limit'] ) ? 1 : 0;
		$has_dated        = ! empty( $args['dated'] ) ? 1 : 0;
		$dated_val        = $has_dated ? sanitize_text_field( (string) $args['dated'] ) : '1970-01-01 00:00:00';

		$now_mysql  = licenser_date( 'Y-m-d H:i:s' );
		$soon_mysql = licenser_date( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) );

		$is_filter_active   = ( 'active' === $license_status ) ? 1 : 0;
		$is_filter_expired  = ( 'expired' === $license_status ) ? 1 : 0;
		$is_filter_expiring = ( 'expiring_soon' === $license_status ) ? 1 : 0;
		$is_filter_inactive = ( 'inactive' === $license_status ) ? 1 : 0;

		$orderby      = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$order_by_sql = sanitize_sql_orderby( "{$orderby} {$order}" );
		$has_limit    = ( -1 !== (int) $args['number'] );

		if ( $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$licenses = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.* FROM {$wpdb->licenser_licenses} AS l WHERE ( 0 = %d OR l.product_id = %d ) AND ( 0 = %d OR l.package_id = %d ) AND ( 0 = %d OR l.source = %s ) AND ( 0 = %d OR FIND_IN_SET( l.source, %s ) > 0 ) AND ( 0 = %d OR l.source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( l.source_id, %s ) > 0 ) AND ( 0 = %d OR l.status = %d ) AND ( 0 = %d OR ( l.status = 1 AND ( l.is_lifetime = 1 OR l.end_date IS NULL OR l.end_date >= %s ) ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date < %s ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date >= %s AND l.end_date <= %s ) ) AND ( 0 = %d OR l.status = 0 ) AND ( 0 = %d OR l.license_key LIKE %s OR CAST( l.source_id AS CHAR ) LIKE %s OR EXISTS ( SELECT 1 FROM {$wpdb->licenser_license_domains} AS d WHERE d.license_id = l.id AND d.domain LIKE %s ) ) AND ( 0 = %d OR l.license_key = %s ) AND ( 0 = %d OR l.end_date = %s ) AND ( 0 = %d OR l.is_lifetime = %d ) AND ( 0 = %d OR l.domain_limit = %d ) AND ( 0 = %d OR l.dated = %s ) ORDER BY l.{$order_by_sql} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_product_id,
					absint( $args['product_id'] ),
					$has_package_id,
					absint( $args['package_id'] ),
					$has_source,
					$is_source_array ? '' : sanitize_text_field( (string) $args['source'] ),
					$has_source_names,
					$sources_csv,
					$has_source_id,
					$is_source_id_array ? 0 : absint( $args['source_id'] ),
					$has_source_list,
					$source_ids_csv,
					$has_status,
					intval( $args['status'] ),
					$is_filter_active,
					$now_mysql,
					$is_filter_expired,
					$now_mysql,
					$is_filter_expiring,
					$now_mysql,
					$soon_mysql,
					$is_filter_inactive,
					$has_search,
					$search_like,
					$search_like,
					$search_like,
					$has_license_key,
					sanitize_text_field( (string) $args['license_key'] ),
					$has_end_date,
					$end_date_val,
					$has_is_lifetime,
					intval( $args['is_lifetime'] ),
					$has_domain_limit,
					intval( $args['domain_limit'] ),
					$has_dated,
					$dated_val,
					absint( $args['offset'] ),
					absint( $args['number'] )
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$licenses = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.* FROM {$wpdb->licenser_licenses} AS l WHERE ( 0 = %d OR l.product_id = %d ) AND ( 0 = %d OR l.package_id = %d ) AND ( 0 = %d OR l.source = %s ) AND ( 0 = %d OR FIND_IN_SET( l.source, %s ) > 0 ) AND ( 0 = %d OR l.source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( l.source_id, %s ) > 0 ) AND ( 0 = %d OR l.status = %d ) AND ( 0 = %d OR ( l.status = 1 AND ( l.is_lifetime = 1 OR l.end_date IS NULL OR l.end_date >= %s ) ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date < %s ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date >= %s AND l.end_date <= %s ) ) AND ( 0 = %d OR l.status = 0 ) AND ( 0 = %d OR l.license_key LIKE %s OR CAST( l.source_id AS CHAR ) LIKE %s OR EXISTS ( SELECT 1 FROM {$wpdb->licenser_license_domains} AS d WHERE d.license_id = l.id AND d.domain LIKE %s ) ) AND ( 0 = %d OR l.license_key = %s ) AND ( 0 = %d OR l.end_date = %s ) AND ( 0 = %d OR l.is_lifetime = %d ) AND ( 0 = %d OR l.domain_limit = %d ) AND ( 0 = %d OR l.dated = %s ) ORDER BY l.{$order_by_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_product_id,
					absint( $args['product_id'] ),
					$has_package_id,
					absint( $args['package_id'] ),
					$has_source,
					$is_source_array ? '' : sanitize_text_field( (string) $args['source'] ),
					$has_source_names,
					$sources_csv,
					$has_source_id,
					$is_source_id_array ? 0 : absint( $args['source_id'] ),
					$has_source_list,
					$source_ids_csv,
					$has_status,
					intval( $args['status'] ),
					$is_filter_active,
					$now_mysql,
					$is_filter_expired,
					$now_mysql,
					$is_filter_expiring,
					$now_mysql,
					$soon_mysql,
					$is_filter_inactive,
					$has_search,
					$search_like,
					$search_like,
					$search_like,
					$has_license_key,
					sanitize_text_field( (string) $args['license_key'] ),
					$has_end_date,
					$end_date_val,
					$has_is_lifetime,
					intval( $args['is_lifetime'] ),
					$has_domain_limit,
					intval( $args['domain_limit'] ),
					$has_dated,
					$dated_val
				)
			);
		}

		if ( empty( $licenses ) ) {
			return false;
		}

		if ( $args['inc_domains'] ) {
			foreach ( $licenses as $key => $license ) {
				$licenses[ $key ]->domains = isset( $license->id ) ? $this->get_domains(
					[
						'license_id' => $license->id,
						'number'     => -1,
					]
				) : [];
			}
		}

		return $licenses;
	}

	/**
	 * Count licenses matching optional filter arguments.
	 *
	 * @param array $args Filter arguments.
	 * @return int
	 */
	public function count( $args = [] ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'product_id'     => '',
				'package_id'     => '',
				'source'         => '',
				'source_id'      => '',
				'status'         => '',
				'license_status' => '',
				'search'         => '',
				'license_key'    => '',
				'end_date'       => '',
				'is_lifetime'    => '',
				'domain_limit'   => '',
				'dated'          => '',
			]
		);

		$is_source_array = ! empty( $args['source'] ) && is_array( $args['source'] );
		$sources_csv     = '';
		if ( $is_source_array ) {
			$clean_sources = array_values( array_filter( array_map( 'sanitize_key', $args['source'] ) ) );
			if ( empty( $clean_sources ) ) {
				return 0;
			}
			$sources_csv = implode( ',', $clean_sources );
		}

		$is_source_id_array = ! empty( $args['source_id'] ) && is_array( $args['source_id'] );
		$source_ids_csv     = '';
		if ( $is_source_id_array ) {
			$source_ids = array_values( array_filter( wp_parse_id_list( $args['source_id'] ) ) );
			if ( empty( $source_ids ) ) {
				return 0;
			}
			$source_ids_csv = implode( ',', $source_ids );
		}

		$has_product_id   = ! empty( $args['product_id'] ) ? 1 : 0;
		$has_package_id   = ! empty( $args['package_id'] ) ? 1 : 0;
		$has_source       = ( ! $is_source_array && ! empty( $args['source'] ) ) ? 1 : 0;
		$has_source_names = $is_source_array ? 1 : 0;
		$has_source_id    = ( ! $is_source_id_array && ! empty( $args['source_id'] ) ) ? 1 : 0;
		$has_source_list  = $is_source_id_array ? 1 : 0;
		$has_status       = ( isset( $args['status'] ) && '' !== $args['status'] ) ? 1 : 0;
		$license_status   = sanitize_key( (string) $args['license_status'] );
		$has_search       = ! empty( $args['search'] ) ? 1 : 0;
		$search_like      = $has_search ? '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%' : '';
		$has_license_key  = ! empty( $args['license_key'] ) ? 1 : 0;
		$has_end_date     = ! empty( $args['end_date'] ) ? 1 : 0;
		$end_date_val     = $has_end_date ? sanitize_text_field( (string) $args['end_date'] ) : '1970-01-01 00:00:00';
		$has_is_lifetime  = ( isset( $args['is_lifetime'] ) && '' !== $args['is_lifetime'] ) ? 1 : 0;
		$has_domain_limit = ( isset( $args['domain_limit'] ) && '' !== $args['domain_limit'] ) ? 1 : 0;
		$has_dated        = ! empty( $args['dated'] ) ? 1 : 0;
		$dated_val        = $has_dated ? sanitize_text_field( (string) $args['dated'] ) : '1970-01-01 00:00:00';

		$now_mysql  = licenser_date( 'Y-m-d H:i:s' );
		$soon_mysql = licenser_date( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) );

		$is_filter_active   = ( 'active' === $license_status ) ? 1 : 0;
		$is_filter_expired  = ( 'expired' === $license_status ) ? 1 : 0;
		$is_filter_expiring = ( 'expiring_soon' === $license_status ) ? 1 : 0;
		$is_filter_inactive = ( 'inactive' === $license_status ) ? 1 : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->licenser_licenses} AS l WHERE ( 0 = %d OR l.product_id = %d ) AND ( 0 = %d OR l.package_id = %d ) AND ( 0 = %d OR l.source = %s ) AND ( 0 = %d OR FIND_IN_SET( l.source, %s ) > 0 ) AND ( 0 = %d OR l.source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( l.source_id, %s ) > 0 ) AND ( 0 = %d OR l.status = %d ) AND ( 0 = %d OR ( l.status = 1 AND ( l.is_lifetime = 1 OR l.end_date IS NULL OR l.end_date >= %s ) ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date < %s ) ) AND ( 0 = %d OR ( l.status = 1 AND l.is_lifetime = 0 AND l.end_date IS NOT NULL AND l.end_date >= %s AND l.end_date <= %s ) ) AND ( 0 = %d OR l.status = 0 ) AND ( 0 = %d OR l.license_key LIKE %s OR CAST( l.source_id AS CHAR ) LIKE %s OR EXISTS ( SELECT 1 FROM {$wpdb->licenser_license_domains} AS d WHERE d.license_id = l.id AND d.domain LIKE %s ) ) AND ( 0 = %d OR l.license_key = %s ) AND ( 0 = %d OR l.end_date = %s ) AND ( 0 = %d OR l.is_lifetime = %d ) AND ( 0 = %d OR l.domain_limit = %d ) AND ( 0 = %d OR l.dated = %s )",
				$has_product_id,
				absint( $args['product_id'] ),
				$has_package_id,
				absint( $args['package_id'] ),
				$has_source,
				$is_source_array ? '' : sanitize_text_field( (string) $args['source'] ),
				$has_source_names,
				$sources_csv,
				$has_source_id,
				$is_source_id_array ? 0 : absint( $args['source_id'] ),
				$has_source_list,
				$source_ids_csv,
				$has_status,
				intval( $args['status'] ),
				$is_filter_active,
				$now_mysql,
				$is_filter_expired,
				$now_mysql,
				$is_filter_expiring,
				$now_mysql,
				$soon_mysql,
				$is_filter_inactive,
				$has_search,
				$search_like,
				$search_like,
				$search_like,
				$has_license_key,
				sanitize_text_field( (string) $args['license_key'] ),
				$has_end_date,
				$end_date_val,
				$has_is_lifetime,
				intval( $args['is_lifetime'] ),
				$has_domain_limit,
				intval( $args['domain_limit'] ),
				$has_dated,
				$dated_val
			)
		);
	}

	/**
	 * Compute the semantic display status for a license record.
	 *
	 * @param object $license License record.
	 * @return string One of `inactive`, `expired`, `expiring_soon`, `active`.
	 */
	public function get_computed_status( $license ) {
		if ( ! $license || 1 !== (int) $license->status ) {
			return 'inactive';
		}

		if ( 1 === (int) $license->is_lifetime || empty( $license->end_date ) ) {
			return 'active';
		}

		$end_ts = strtotime( (string) $license->end_date );
		if ( false === $end_ts ) {
			return 'active';
		}

		$now = time();
		if ( $end_ts < $now ) {
			return 'expired';
		}

		if ( $end_ts <= ( $now + ( 30 * DAY_IN_SECONDS ) ) ) {
			return 'expiring_soon';
		}

		return 'active';
	}

	/**
	 * Fetch aggregate KPI statistics for the Dashboard and filter bar.
	 *
	 * @return array{total: int, active: int, expired: int, expiring_soon: int, inactive: int, active_domains: int}
	 */
	public function get_dashboard_stats() {
		global $wpdb;

		$now_mysql  = licenser_date( 'Y-m-d H:i:s' );
		$soon_mysql = licenser_date( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM( CASE WHEN status = 1 AND ( is_lifetime = 1 OR end_date IS NULL OR end_date >= %s ) THEN 1 ELSE 0 END ) AS active,
					SUM( CASE WHEN status = 1 AND is_lifetime = 0 AND end_date IS NOT NULL AND end_date < %s THEN 1 ELSE 0 END ) AS expired,
					SUM( CASE WHEN status = 1 AND is_lifetime = 0 AND end_date IS NOT NULL AND end_date >= %s AND end_date <= %s THEN 1 ELSE 0 END ) AS expiring_soon,
					SUM( CASE WHEN status = 0 THEN 1 ELSE 0 END ) AS inactive
				FROM {$wpdb->licenser_licenses}",
				$now_mysql,
				$now_mysql,
				$now_mysql,
				$soon_mysql
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_domains = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->licenser_license_domains} WHERE status = %d",
				1
			)
		);

		return [
			'total'          => $row ? absint( $row->total ) : 0,
			'active'         => $row ? absint( $row->active ) : 0,
			'expired'        => $row ? absint( $row->expired ) : 0,
			'expiring_soon'  => $row ? absint( $row->expiring_soon ) : 0,
			'inactive'       => $row ? absint( $row->inactive ) : 0,
			'active_domains' => $active_domains,
		];
	}

	/**
	 * Batch count active domains for a set of license IDs.
	 *
	 * @param int[] $license_ids License IDs.
	 * @return array<int, int>
	 */
	public function get_active_domain_counts( $license_ids ) {
		global $wpdb;

		$ids = array_values( array_filter( wp_parse_id_list( $license_ids ) ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$counts  = array_fill_keys( $ids, 0 );
		$ids_csv = implode( ',', $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT license_id, COUNT(*) AS total FROM {$wpdb->licenser_license_domains} WHERE status = 1 AND FIND_IN_SET( license_id, %s ) > 0 GROUP BY license_id",
				$ids_csv
			)
		);

		foreach ( (array) $rows as $row ) {
			$lid = absint( $row->license_id );
			if ( isset( $counts[ $lid ] ) ) {
				$counts[ $lid ] = absint( $row->total );
			}
		}

		/**
		 * Filter batch active domain counts keyed by license ID.
		 *
		 * @param array<int, int> $counts Active domain counts keyed by license ID.
		 * @param int[]           $ids    Requested license IDs.
		 */
		return (array) apply_filters( 'licenser_active_domain_counts', $counts, $ids );
	}

	/**
	 * Fetch recent domain activations joined with license and product metadata.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array
	 */
	public function get_recent_domains( $limit = 8 ) {
		global $wpdb;

		$limit = max( 1, absint( $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.id, d.license_id, d.domain, d.status, d.dated, l.license_key, l.product_id, p.name AS product_name, p.product_type
				FROM {$wpdb->licenser_license_domains} AS d
				LEFT JOIN {$wpdb->licenser_licenses} AS l ON l.id = d.license_id
				LEFT JOIN {$wpdb->licenser_products} AS p ON p.id = l.product_id
				ORDER BY d.id DESC
				LIMIT %d",
				$limit
			)
		);

		if ( empty( $rows ) ) {
			return [];
		}

		$license_ids = array_values( array_unique( array_filter( wp_list_pluck( $rows, 'license_id' ) ) ) );
		$versions    = [];
		if ( ! empty( $license_ids ) ) {
			$prefix_like = $wpdb->esc_like( 'installed_version_' ) . '%';
			$ids_csv     = implode( ',', wp_parse_id_list( $license_ids ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$meta_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value FROM {$wpdb->licenser_license_meta} WHERE FIND_IN_SET( license_id, %s ) > 0 AND meta_key LIKE %s",
					$ids_csv,
					$prefix_like
				)
			);

			foreach ( (array) $meta_rows as $meta_row ) {
				$did = absint( str_replace( 'installed_version_', '', (string) $meta_row->meta_key ) );
				if ( $did > 0 ) {
					$versions[ $did ] = (string) $meta_row->meta_value;
				}
			}
		}

		foreach ( $rows as $row ) {
			$did                    = absint( $row->id );
			$row->installed_version = isset( $versions[ $did ] ) ? $versions[ $did ] : '';
		}

		return $rows;
	}

	/**
	 * Fetch a single domain row by primary key ID.
	 *
	 * @param int $id Domain row ID.
	 * @return object|null
	 */
	public function get_domain_by_id( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->licenser_license_domains} WHERE id = %d LIMIT 1",
				absint( $id )
			)
		);
	}

	/**
	 * Toggle or set a domain's active status.
	 *
	 * @param int $id     Domain row ID.
	 * @param int $status Target status (`0` or `1`).
	 * @return bool
	 */
	public function update_domain_status( $id, $status ) {
		global $wpdb;

		$id     = absint( $id );
		$status = ( 1 === (int) $status ) ? 1 : 0;

		if ( $id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$wpdb->licenser_license_domains,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);

		return false !== $updated;
	}

	/**
	 * Bulk update license active status.
	 *
	 * @param int[] $ids    License IDs.
	 * @param int   $status Target status (`0` or `1`).
	 * @return int Number of updated rows.
	 */
	public function bulk_update_status( $ids, $status ) {
		global $wpdb;

		$clean_ids = array_values( array_filter( wp_parse_id_list( $ids ) ) );
		if ( empty( $clean_ids ) ) {
			return 0;
		}

		$status  = ( 1 === (int) $status ) ? 1 : 0;
		$ids_csv = implode( ',', $clean_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->licenser_licenses} SET status = %d WHERE FIND_IN_SET( id, %s ) > 0",
				$status,
				$ids_csv
			)
		);

		return false !== $updated ? (int) $updated : 0;
	}

	/**
	 * Bulk delete licenses with cascading domains and metadata cleanup.
	 *
	 * @param int[] $ids License IDs.
	 * @return int Number of deleted licenses.
	 */
	public function bulk_delete( $ids ) {
		$clean_ids = array_values( array_filter( wp_parse_id_list( $ids ) ) );
		$deleted   = 0;

		foreach ( $clean_ids as $id ) {
			if ( $this->delete( $id ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Create or update a license record.
	 *
	 * @param array $data License payload.
	 * @return int License ID.
	 */
	public function create( $data ) {
		$data = wp_parse_args(
			$data,
			[
				'status'       => 1,
				'license_key'  => '',
				'product_id'   => 0,
				'package_id'   => '',
				'source'       => '',
				'source_id'    => 0,
				'end_date'     => null,
				'is_lifetime'  => 0,
				'domain_limit' => 0,
				'dated'        => licenser_date( 'Y-m-d H:i:s' ),
			]
		);

		global $wpdb;

		$is_lifetime = ! empty( $data['is_lifetime'] ) ? 1 : 0;
		$end_date    = null;

		if ( 0 === $is_lifetime && ! empty( $data['end_date'] ) ) {
			$timestamp = strtotime( (string) $data['end_date'] );
			if ( false !== $timestamp ) {
				$end_date = licenser_date( 'Y-m-d H:i:s', $timestamp );
			}
		}

		$package_id = ( '' !== $data['package_id'] && null !== $data['package_id'] ) ? absint( $data['package_id'] ) : null;

		if ( ! empty( $data['id'] ) ) {
			$insert_id = absint( $data['id'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->licenser_licenses,
				[
					'status'       => intval( $data['status'] ),
					'license_key'  => sanitize_text_field( $data['license_key'] ),
					'product_id'   => absint( $data['product_id'] ),
					'package_id'   => $package_id,
					'source'       => sanitize_text_field( $data['source'] ),
					'source_id'    => absint( $data['source_id'] ),
					'end_date'     => $end_date,
					'is_lifetime'  => $is_lifetime,
					'domain_limit' => absint( $data['domain_limit'] ),
				],
				[ 'id' => $insert_id ]
			);

			/**
			 * Fires after a license is updated.
			 *
			 * @param int   $insert_id License ID.
			 * @param array $data      Input payload.
			 */
			do_action( 'licenser_license_updated', $insert_id, $data );
		} else {
			if ( empty( $data['license_key'] ) ) {
				$data['license_key'] = $this->generate_key();
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->licenser_licenses,
				[
					'status'       => intval( $data['status'] ),
					'license_key'  => sanitize_text_field( $data['license_key'] ),
					'product_id'   => absint( $data['product_id'] ),
					'package_id'   => $package_id,
					'source'       => sanitize_text_field( $data['source'] ),
					'source_id'    => absint( $data['source_id'] ),
					'end_date'     => $end_date,
					'is_lifetime'  => $is_lifetime,
					'domain_limit' => absint( $data['domain_limit'] ),
					'dated'        => sanitize_text_field( $data['dated'] ),
				]
			);

			$insert_id = (int) $wpdb->insert_id;

			/**
			 * Fires after a new license is created.
			 *
			 * @param int   $insert_id License ID.
			 * @param array $data      Input payload.
			 */
			do_action( 'licenser_license_created', $insert_id, $data );
		}

		return $insert_id;
	}

	/**
	 * Generate a new license key using configured settings.
	 *
	 * @return string
	 */
	public function generate_key() {
		$method = licenser_get_option( 'license_generate_method' );

		if ( 'wp_generate' === $method ) {
			$limit = absint( licenser_get_option( 'license_code_character_limit' ) );
			$limit = $limit >= 8 ? $limit : 32;
			$key   = wp_generate_password( $limit, false, false );
		} elseif ( 'uuid' === $method ) {
			$key = wp_generate_uuid4();
		} else {
			try {
				$key = bin2hex( random_bytes( 16 ) );
			} catch ( \Exception $e ) {
				$key = md5( microtime() . wp_rand() );
			}
		}

		$prefix = (string) licenser_get_option( 'license_code_prefix' );

		return $prefix . $key;
	}

	/**
	 * Delete a license along with its activated domains and metadata.
	 *
	 * @param int $id License ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );

		$this->delete_domains( $id );
		License_Meta::instance()->delete_all( $id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->licenser_licenses,
			[ 'id' => $id ],
			[ '%d' ]
		);

		/**
		 * Fires after a license is deleted.
		 *
		 * @param int $id License ID.
		 */
		do_action( 'licenser_license_deleted', $id );

		return true;
	}

	/**
	 * Get activated domains or total domain count for a license.
	 *
	 * @param array $args Query arguments.
	 * @return array|int|false
	 */
	public function get_domains( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'license_id'       => '',
				'status'           => '',
				'columns'          => [],
				'count_total'      => false,
				'number'           => 20,
				'offset'           => 0,
				'orderby'          => 'id',
				'order'            => 'DESC',
				'license_id_check' => true,
			]
		);

		global $wpdb;

		if ( empty( $args['license_id'] ) && $args['license_id_check'] ) {
			return $args['count_total'] ? 0 : false;
		}

		$columns        = esc_sql( $this->sanitize_columns( $args['columns'], self::ALLOWED_DOMAIN_COLUMNS ) );
		$has_license_id = ! empty( $args['license_id'] ) ? 1 : 0;
		$has_status     = ( '' !== $args['status'] && null !== $args['status'] ) ? 1 : 0;

		if ( $args['count_total'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->licenser_license_domains} WHERE ( 0 = %d OR license_id = %d ) AND ( 0 = %d OR status = %d )",
					$has_license_id,
					absint( $args['license_id'] ),
					$has_status,
					intval( $args['status'] )
				)
			);
		}

		$orderby      = in_array( $args['orderby'], self::ALLOWED_DOMAIN_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$order_by_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

		if ( -1 !== (int) $args['number'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$columns} FROM {$wpdb->licenser_license_domains} WHERE ( 0 = %d OR license_id = %d ) AND ( 0 = %d OR status = %d ) ORDER BY {$order_by_sql} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_license_id,
					absint( $args['license_id'] ),
					$has_status,
					intval( $args['status'] ),
					absint( $args['offset'] ),
					absint( $args['number'] )
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$wpdb->licenser_license_domains} WHERE ( 0 = %d OR license_id = %d ) AND ( 0 = %d OR status = %d ) ORDER BY {$order_by_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$has_license_id,
				absint( $args['license_id'] ),
				$has_status,
				intval( $args['status'] )
			)
		);
	}

	/**
	 * Delete all domains associated with a license.
	 *
	 * @param int $license_id License ID.
	 * @return bool
	 */
	public function delete_domains( $license_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->licenser_license_domains,
			[ 'license_id' => absint( $license_id ) ],
			[ '%d' ]
		);

		return true;
	}

	/**
	 * Delete a single domain record by its row ID.
	 *
	 * @param int $id Domain row ID.
	 * @return bool
	 */
	public function delete_domain( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->licenser_license_domains,
			[ 'id' => absint( $id ) ],
			[ '%d' ]
		);

		return true;
	}

	/**
	 * Check whether a domain exists for a license and return its row ID.
	 *
	 * @param string     $domain     Raw or normalized domain/URL.
	 * @param int|string $license_id Optional license ID.
	 * @return string|null Domain row ID or null if not found.
	 */
	public function domain_exists( $domain, $license_id = '' ) {
		$row = $this->get_domain( $domain, $license_id );
		return $row ? (string) $row->id : null;
	}

	/**
	 * Fetch a domain record by URL and optional license ID.
	 *
	 * @param string     $domain     Raw or normalized domain/URL.
	 * @param int|string $license_id Optional license ID.
	 * @return object|null
	 */
	public function get_domain( $domain, $license_id = '' ) {
		global $wpdb;

		$clean_domain = licenser_get_clean_url( $domain );
		if ( '' === $clean_domain ) {
			return null;
		}

		if ( ! empty( $license_id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->licenser_license_domains} WHERE domain = %s AND license_id = %d LIMIT 1",
					$clean_domain,
					absint( $license_id )
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->licenser_license_domains} WHERE domain = %s LIMIT 1",
				$clean_domain
			)
		);
	}

	/**
	 * Add or update an activated domain row.
	 *
	 * @param array $args Domain payload.
	 * @return int Domain row ID.
	 */
	public function add_domain( $args ) {
		$args = wp_parse_args(
			$args,
			[
				'id'         => '',
				'license_id' => 0,
				'domain'     => '',
				'status'     => 1,
				'dated'      => licenser_date( 'Y-m-d H:i:s' ),
			]
		);

		global $wpdb;

		if ( ! empty( $args['id'] ) ) {
			$id = absint( $args['id'] );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->licenser_license_domains,
				[
					'license_id' => absint( $args['license_id'] ),
					'domain'     => licenser_get_clean_url( $args['domain'] ),
					'status'     => intval( $args['status'] ),
				],
				[ 'id' => $id ],
				[ '%d', '%s', '%d' ],
				[ '%d' ]
			);

			return $id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->licenser_license_domains,
			[
				'license_id' => absint( $args['license_id'] ),
				'domain'     => licenser_get_clean_url( $args['domain'] ),
				'status'     => intval( $args['status'] ),
				'dated'      => sanitize_text_field( $args['dated'] ),
			],
			[ '%d', '%s', '%d', '%s' ]
		);

		return (int) $wpdb->insert_id;
	}
}
