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
			'number'       => 20,
			'offset'       => 0,
			'orderby'      => 'id',
			'order'        => 'DESC',
			'count_total'  => true,
			'product_id'   => '',
			'package_id'   => '',
			'source'       => '',
			'source_id'    => '',
			'status'       => '',
			'license_key'  => '',
			'end_date'     => '',
			'is_lifetime'  => '',
			'domain_limit' => '',
			'dated'        => '',
			'inc_domains'  => false,
		];

		$args = wp_parse_args( $args, $defaults );

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
		$has_source       = ! empty( $args['source'] ) ? 1 : 0;
		$has_source_id    = ( ! $is_source_id_array && ! empty( $args['source_id'] ) ) ? 1 : 0;
		$has_source_list  = $is_source_id_array ? 1 : 0;
		$has_status       = ( isset( $args['status'] ) && '' !== $args['status'] ) ? 1 : 0;
		$has_license_key  = ! empty( $args['license_key'] ) ? 1 : 0;
		$has_end_date     = ! empty( $args['end_date'] ) ? 1 : 0;
		$end_date_val     = $has_end_date ? sanitize_text_field( (string) $args['end_date'] ) : '1970-01-01 00:00:00';
		$has_is_lifetime  = ( isset( $args['is_lifetime'] ) && '' !== $args['is_lifetime'] ) ? 1 : 0;
		$has_domain_limit = ( isset( $args['domain_limit'] ) && '' !== $args['domain_limit'] ) ? 1 : 0;
		$has_dated        = ! empty( $args['dated'] ) ? 1 : 0;
		$dated_val        = $has_dated ? sanitize_text_field( (string) $args['dated'] ) : '1970-01-01 00:00:00';

		$orderby      = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$order_by_sql = sanitize_sql_orderby( "{$orderby} {$order}" );
		$has_limit    = ( -1 !== (int) $args['number'] );

		if ( $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$licenses = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->licenser_licenses} WHERE ( 0 = %d OR product_id = %d ) AND ( 0 = %d OR package_id = %d ) AND ( 0 = %d OR source = %s ) AND ( 0 = %d OR source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( source_id, %s ) > 0 ) AND ( 0 = %d OR status = %d ) AND ( 0 = %d OR license_key = %s ) AND ( 0 = %d OR end_date = %s ) AND ( 0 = %d OR is_lifetime = %d ) AND ( 0 = %d OR domain_limit = %d ) AND ( 0 = %d OR dated = %s ) ORDER BY {$order_by_sql} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_product_id,
					absint( $args['product_id'] ),
					$has_package_id,
					absint( $args['package_id'] ),
					$has_source,
					sanitize_text_field( (string) $args['source'] ),
					$has_source_id,
					$is_source_id_array ? 0 : absint( $args['source_id'] ),
					$has_source_list,
					$source_ids_csv,
					$has_status,
					intval( $args['status'] ),
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
					"SELECT * FROM {$wpdb->licenser_licenses} WHERE ( 0 = %d OR product_id = %d ) AND ( 0 = %d OR package_id = %d ) AND ( 0 = %d OR source = %s ) AND ( 0 = %d OR source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( source_id, %s ) > 0 ) AND ( 0 = %d OR status = %d ) AND ( 0 = %d OR license_key = %s ) AND ( 0 = %d OR end_date = %s ) AND ( 0 = %d OR is_lifetime = %d ) AND ( 0 = %d OR domain_limit = %d ) AND ( 0 = %d OR dated = %s ) ORDER BY {$order_by_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_product_id,
					absint( $args['product_id'] ),
					$has_package_id,
					absint( $args['package_id'] ),
					$has_source,
					sanitize_text_field( (string) $args['source'] ),
					$has_source_id,
					$is_source_id_array ? 0 : absint( $args['source_id'] ),
					$has_source_list,
					$source_ids_csv,
					$has_status,
					intval( $args['status'] ),
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
				'product_id'   => '',
				'package_id'   => '',
				'source'       => '',
				'source_id'    => '',
				'status'       => '',
				'license_key'  => '',
				'end_date'     => '',
				'is_lifetime'  => '',
				'domain_limit' => '',
				'dated'        => '',
			]
		);

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
		$has_source       = ! empty( $args['source'] ) ? 1 : 0;
		$has_source_id    = ( ! $is_source_id_array && ! empty( $args['source_id'] ) ) ? 1 : 0;
		$has_source_list  = $is_source_id_array ? 1 : 0;
		$has_status       = ( isset( $args['status'] ) && '' !== $args['status'] ) ? 1 : 0;
		$has_license_key  = ! empty( $args['license_key'] ) ? 1 : 0;
		$has_end_date     = ! empty( $args['end_date'] ) ? 1 : 0;
		$end_date_val     = $has_end_date ? sanitize_text_field( (string) $args['end_date'] ) : '1970-01-01 00:00:00';
		$has_is_lifetime  = ( isset( $args['is_lifetime'] ) && '' !== $args['is_lifetime'] ) ? 1 : 0;
		$has_domain_limit = ( isset( $args['domain_limit'] ) && '' !== $args['domain_limit'] ) ? 1 : 0;
		$has_dated        = ! empty( $args['dated'] ) ? 1 : 0;
		$dated_val        = $has_dated ? sanitize_text_field( (string) $args['dated'] ) : '1970-01-01 00:00:00';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->licenser_licenses} WHERE ( 0 = %d OR product_id = %d ) AND ( 0 = %d OR package_id = %d ) AND ( 0 = %d OR source = %s ) AND ( 0 = %d OR source_id = %d ) AND ( 0 = %d OR FIND_IN_SET( source_id, %s ) > 0 ) AND ( 0 = %d OR status = %d ) AND ( 0 = %d OR license_key = %s ) AND ( 0 = %d OR end_date = %s ) AND ( 0 = %d OR is_lifetime = %d ) AND ( 0 = %d OR domain_limit = %d ) AND ( 0 = %d OR dated = %s )",
				$has_product_id,
				absint( $args['product_id'] ),
				$has_package_id,
				absint( $args['package_id'] ),
				$has_source,
				sanitize_text_field( (string) $args['source'] ),
				$has_source_id,
				$is_source_id_array ? 0 : absint( $args['source_id'] ),
				$has_source_list,
				$source_ids_csv,
				$has_status,
				intval( $args['status'] ),
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
			$key = md5( microtime() . wp_rand() );
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
