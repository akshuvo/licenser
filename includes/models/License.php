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

		$columns = $this->sanitize_columns( $args['columns'], self::ALLOWED_COLUMNS );

		if ( 'key' === $args['get_by'] ) {
			$where = $wpdb->prepare( 'license_key = %s', (string) $id );
		} else {
			$where = $wpdb->prepare( 'id = %d', absint( $id ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$license = $wpdb->get_row( "SELECT {$columns} FROM {$wpdb->licenser_licenses} WHERE {$where} LIMIT 1" );

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

		$args  = wp_parse_args( $args, $defaults );
		$where = $this->build_where_clause( $args );

		if ( false === $where ) {
			return false;
		}

		$orderby = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$limit_sql = '';
		if ( -1 !== (int) $args['number'] ) {
			$limit_sql = $wpdb->prepare( ' LIMIT %d, %d', absint( $args['offset'] ), absint( $args['number'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->licenser_licenses} WHERE {$where} ORDER BY {$orderby} {$order}{$limit_sql}" );

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

		$where = $this->build_where_clause( $args );
		if ( false === $where ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->licenser_licenses} WHERE {$where}" );
	}

	/**
	 * Build a prepared SQL WHERE fragment for license queries.
	 *
	 * @param array $args Filter arguments.
	 * @return string|false SQL fragment or false if filter matches nothing.
	 */
	protected function build_where_clause( $args = [] ) {
		global $wpdb;

		$where = '1=1';

		if ( ! empty( $args['product_id'] ) ) {
			$where .= $wpdb->prepare( ' AND product_id = %d', absint( $args['product_id'] ) );
		}

		if ( ! empty( $args['package_id'] ) ) {
			$where .= $wpdb->prepare( ' AND package_id = %d', absint( $args['package_id'] ) );
		}

		if ( ! empty( $args['source'] ) ) {
			$where .= $wpdb->prepare( ' AND source = %s', sanitize_text_field( $args['source'] ) );
		}

		if ( ! empty( $args['source_id'] ) ) {
			if ( is_array( $args['source_id'] ) ) {
				$source_ids = array_values( array_filter( array_map( 'absint', $args['source_id'] ) ) );
				if ( empty( $source_ids ) ) {
					return false;
				}
				$placeholders = implode( ', ', array_fill( 0, count( $source_ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$where .= $wpdb->prepare( " AND source_id IN ({$placeholders})", $source_ids );
			} else {
				$where .= $wpdb->prepare( ' AND source_id = %d', absint( $args['source_id'] ) );
			}
		}

		if ( isset( $args['status'] ) && '' !== $args['status'] ) {
			$where .= $wpdb->prepare( ' AND status = %d', intval( $args['status'] ) );
		}

		if ( ! empty( $args['license_key'] ) ) {
			$where .= $wpdb->prepare( ' AND license_key = %s', sanitize_text_field( $args['license_key'] ) );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where .= $wpdb->prepare( ' AND end_date = %s', sanitize_text_field( $args['end_date'] ) );
		}

		if ( isset( $args['is_lifetime'] ) && '' !== $args['is_lifetime'] ) {
			$where .= $wpdb->prepare( ' AND is_lifetime = %d', intval( $args['is_lifetime'] ) );
		}

		if ( isset( $args['domain_limit'] ) && '' !== $args['domain_limit'] ) {
			$where .= $wpdb->prepare( ' AND domain_limit = %d', intval( $args['domain_limit'] ) );
		}

		if ( ! empty( $args['dated'] ) ) {
			$where .= $wpdb->prepare( ' AND dated = %s', sanitize_text_field( $args['dated'] ) );
		}

		return $where;
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

		$columns = $this->sanitize_columns( $args['columns'], self::ALLOWED_DOMAIN_COLUMNS );
		$where   = '1=1';

		if ( ! empty( $args['license_id'] ) ) {
			$where .= $wpdb->prepare( ' AND license_id = %d', absint( $args['license_id'] ) );
		} elseif ( $args['license_id_check'] ) {
			return $args['count_total'] ? 0 : false;
		}

		if ( '' !== $args['status'] && null !== $args['status'] ) {
			$where .= $wpdb->prepare( ' AND status = %d', intval( $args['status'] ) );
		}

		if ( $args['count_total'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->licenser_license_domains} WHERE {$where}" );
		}

		$orderby = in_array( $args['orderby'], self::ALLOWED_DOMAIN_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$limit_sql = '';
		if ( -1 !== (int) $args['number'] ) {
			$limit_sql = $wpdb->prepare( ' LIMIT %d, %d', absint( $args['offset'] ), absint( $args['number'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT {$columns} FROM {$wpdb->licenser_license_domains} WHERE {$where} ORDER BY {$orderby} {$order}{$limit_sql}" );
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

		$where = $wpdb->prepare( 'domain = %s', $clean_domain );

		if ( ! empty( $license_id ) ) {
			$where .= $wpdb->prepare( ' AND license_id = %d', absint( $license_id ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM {$wpdb->licenser_license_domains} WHERE {$where} LIMIT 1" );
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
