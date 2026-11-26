<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product model class.
 */
class Product {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Allowed columns in the products table.
	 *
	 * @var string[]
	 */
	const ALLOWED_COLUMNS = [
		'id',
		'name',
		'slug',
		'uuid',
		'product_type',
		'tested',
		'requires',
		'requires_php',
		'banners',
		'description',
		'author_name',
		'homepage_url',
		'demo_url',
		'icon_url',
		'created_by',
		'status',
		'dated',
	];

	/**
	 * Product default fields.
	 *
	 * @var array
	 */
	public $default_fields = [
		'name'         => '',
		'uuid'         => '',
		'slug'         => '',
		'product_type' => '',
		'tested'       => '',
		'requires'     => '',
		'requires_php' => '',
		'banners'      => [
			'low'  => '',
			'high' => '',
		],
		'description'  => '',
		'author_name'  => '',
		'homepage_url' => '',
		'demo_url'     => '',
		'icon_url'     => '',
		'created_by'   => '',
		'status'       => 'active',
	];

	/**
	 * Sanitize a column list against allowed table columns.
	 *
	 * @param array|string $columns Requested columns.
	 * @return string Safe SQL column list.
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
	 * Get registered product types.
	 *
	 * @return array
	 */
	public function get_types() {
		return apply_filters(
			'licenser_product_types',
			[
				'plugin' => __( 'Plugin', 'licenser' ),
				'theme'  => __( 'Theme', 'licenser' ),
			]
		);
	}

	/**
	 * Get a single product by integer ID or UUID.
	 *
	 * @param int|string $id   Product ID or UUID.
	 * @param array      $args Query arguments.
	 * @return object|false
	 */
	public function get( $id, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'inc_stable_release' => true,
				'inc_releases'       => false,
				'inc_packages'       => true,
				'columns'            => [],
				'get_by'             => '',
				'cache'              => true,
			]
		);

		global $wpdb;

		$columns   = esc_sql( $this->sanitize_columns( $args['columns'] ) );
		$cache_key = 'product_' . sanitize_key( (string) $id ) . '_' . md5( (string) wp_json_encode( $args ) );
		$product   = $args['cache'] ? licenser_get_cache( $cache_key ) : false;

		if ( false === $product ) {
			if ( 'uuid' === $args['get_by'] ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$product = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT {$columns} FROM {$wpdb->licenser_products} WHERE uuid = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						(string) $id
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$product = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT {$columns} FROM {$wpdb->licenser_products} WHERE id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						absint( $id )
					)
				);
			}

			if ( empty( $product ) ) {
				return false;
			}

			// Decode banners JSON safely.
			if ( property_exists( $product, 'banners' ) ) {
				$decoded          = ! empty( $product->banners ) ? json_decode( $product->banners, true ) : [];
				$product->banners = wp_parse_args(
					is_array( $decoded ) ? $decoded : [],
					[
						'low'  => '',
						'high' => '',
					]
				);
			}

			// Stable release.
			if ( $args['inc_stable_release'] && isset( $product->id ) ) {
				$product->stable_release = ProductRelease::instance()->get_stable( $product->id );
			}

			// Releases.
			if ( $args['inc_releases'] && isset( $product->id ) ) {
				$product->releases = ProductRelease::instance()->get_all(
					[
						'product_id' => $product->id,
					]
				);
			}

			// Packages.
			if ( $args['inc_packages'] && isset( $product->id ) ) {
				$product->packages = LicensePackage::instance()->get_all(
					[
						'product_id' => $product->id,
						'number'     => -1,
					]
				);
			}

			if ( $args['cache'] ) {
				licenser_set_cache( $cache_key, $product, HOUR_IN_SECONDS );
			}
		}

		return $product;
	}

	/**
	 * Get a collection of products.
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
			'name'         => '',
			'slug'         => '',
			'uuid'         => '',
			'search'       => '',
			'product_type' => '',
			'status'       => 'active',
			'inc_packages' => false,
			'columns'      => '*',
		];

		$args = wp_parse_args( $args, $defaults );

		$has_name         = ! empty( $args['name'] ) ? 1 : 0;
		$has_slug         = ! empty( $args['slug'] ) ? 1 : 0;
		$has_uuid         = ! empty( $args['uuid'] ) ? 1 : 0;
		$has_search       = ! empty( $args['search'] ) ? 1 : 0;
		$search_like      = $has_search ? '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%' : '';
		$has_product_type = ! empty( $args['product_type'] ) ? 1 : 0;
		$has_status       = ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) ? 1 : 0;

		$orderby      = in_array( $args['orderby'], self::ALLOWED_COLUMNS, true ) ? $args['orderby'] : 'id';
		$order        = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$order_by_sql = sanitize_sql_orderby( "{$orderby} {$order}" );
		$columns      = esc_sql( $this->sanitize_columns( $args['columns'] ) );

		if ( -1 !== (int) $args['number'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$columns} FROM {$wpdb->licenser_products} WHERE ( 0 = %d OR name = %s ) AND ( 0 = %d OR slug = %s ) AND ( 0 = %d OR uuid = %s ) AND ( 0 = %d OR name LIKE %s OR slug LIKE %s OR uuid LIKE %s ) AND ( 0 = %d OR product_type = %s ) AND ( 0 = %d OR status = %s ) ORDER BY {$order_by_sql} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_name,
					(string) $args['name'],
					$has_slug,
					(string) $args['slug'],
					$has_uuid,
					(string) $args['uuid'],
					$has_search,
					$search_like,
					$search_like,
					$search_like,
					$has_product_type,
					(string) $args['product_type'],
					$has_status,
					(string) $args['status'],
					absint( $args['offset'] ),
					absint( $args['number'] )
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$columns} FROM {$wpdb->licenser_products} WHERE ( 0 = %d OR name = %s ) AND ( 0 = %d OR slug = %s ) AND ( 0 = %d OR uuid = %s ) AND ( 0 = %d OR name LIKE %s OR slug LIKE %s OR uuid LIKE %s ) AND ( 0 = %d OR product_type = %s ) AND ( 0 = %d OR status = %s ) ORDER BY {$order_by_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$has_name,
					(string) $args['name'],
					$has_slug,
					(string) $args['slug'],
					$has_uuid,
					(string) $args['uuid'],
					$has_search,
					$search_like,
					$search_like,
					$search_like,
					$has_product_type,
					(string) $args['product_type'],
					$has_status,
					(string) $args['status']
				)
			);
		}

		if ( empty( $items ) ) {
			return false;
		}

		if ( $args['inc_packages'] ) {
			foreach ( $items as $item ) {
				$item->packages = isset( $item->id ) ? LicensePackage::instance()->get_all(
					[
						'product_id' => $item->id,
						'number'     => -1,
					]
				) : [];
			}
		}

		return $items;
	}

	/**
	 * Count products matching optional filters.
	 *
	 * @param array $args Filter arguments (`product_type`, `status`).
	 * @return int
	 */
	public function count( $args = [] ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'product_type' => '',
				'status'       => '',
				'search'       => '',
			]
		);

		$has_product_type = ! empty( $args['product_type'] ) ? 1 : 0;
		$has_status       = ( ! empty( $args['status'] ) && 'all' !== $args['status'] ) ? 1 : 0;
		$has_search       = ! empty( $args['search'] ) ? 1 : 0;
		$search_like      = $has_search ? '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->licenser_products} WHERE ( 0 = %d OR product_type = %s ) AND ( 0 = %d OR status = %s ) AND ( 0 = %d OR name LIKE %s OR slug LIKE %s OR uuid LIKE %s )",
				$has_product_type,
				(string) $args['product_type'],
				$has_status,
				(string) $args['status'],
				$has_search,
				$search_like,
				$search_like,
				$search_like
			)
		);
	}

	/**
	 * Fetch grouped dependency counts (releases, packages, licenses) for a batch of product IDs.
	 *
	 * @param int[] $product_ids List of product IDs.
	 * @return array<int, array{releases: int, packages: int, licenses: int}>
	 */
	public function get_dependency_counts( $product_ids ) {
		global $wpdb;

		$ids = array_values( array_filter( wp_parse_id_list( $product_ids ) ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$counts = [];
		foreach ( $ids as $id ) {
			$counts[ $id ] = [
				'releases' => 0,
				'packages' => 0,
				'licenses' => 0,
			];
		}

		$ids_csv = implode( ',', $ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$release_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS total FROM {$wpdb->licenser_product_releases} WHERE FIND_IN_SET( product_id, %s ) > 0 GROUP BY product_id",
				$ids_csv
			)
		);
		foreach ( (array) $release_rows as $row ) {
			$pid = absint( $row->product_id );
			if ( isset( $counts[ $pid ] ) ) {
				$counts[ $pid ]['releases'] = absint( $row->total );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$package_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS total FROM {$wpdb->licenser_license_packages} WHERE FIND_IN_SET( product_id, %s ) > 0 GROUP BY product_id",
				$ids_csv
			)
		);
		foreach ( (array) $package_rows as $row ) {
			$pid = absint( $row->product_id );
			if ( isset( $counts[ $pid ] ) ) {
				$counts[ $pid ]['packages'] = absint( $row->total );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$license_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS total FROM {$wpdb->licenser_licenses} WHERE FIND_IN_SET( product_id, %s ) > 0 GROUP BY product_id",
				$ids_csv
			)
		);
		foreach ( (array) $license_rows as $row ) {
			$pid = absint( $row->product_id );
			if ( isset( $counts[ $pid ] ) ) {
				$counts[ $pid ]['licenses'] = absint( $row->total );
			}
		}

		return $counts;
	}

	/**
	 * Create or update a product along with optional release and license packages.
	 *
	 * @param array $data Product payload.
	 * @return int Product ID.
	 */
	public function create( $data ) {
		$data = wp_parse_args(
			$data,
			[
				'name'             => '',
				'slug'             => '',
				'product_type'     => '',
				'tested'           => '',
				'requires'         => '',
				'requires_php'     => '',
				'banners'          => [],
				'description'      => '',
				'author_name'      => '',
				'homepage_url'     => '',
				'demo_url'         => '',
				'icon_url'         => '',
				'created_by'       => '',
				'status'           => 'active',
				'version'          => '',
				'changelog'        => '',
				'file_name'        => '',
				'download_link'    => '',
				'release_date'     => '',
				'license_packages' => [],
			]
		);

		// Sanitize banners URLs before JSON encoding.
		$banners_json = '';
		if ( ! empty( $data['banners'] ) && is_array( $data['banners'] ) ) {
			$clean_banners = [
				'low'  => isset( $data['banners']['low'] ) ? esc_url_raw( $data['banners']['low'] ) : '',
				'high' => isset( $data['banners']['high'] ) ? esc_url_raw( $data['banners']['high'] ) : '',
			];
			$banners_json  = (string) wp_json_encode( $clean_banners );
		} elseif ( is_string( $data['banners'] ) ) {
			$banners_json = sanitize_text_field( $data['banners'] );
		}

		global $wpdb;

		$product_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( $product_id > 0 ) {
			$update_fields = [
				'name'         => sanitize_text_field( $data['name'] ),
				'slug'         => sanitize_title( $data['slug'] ),
				'product_type' => sanitize_key( $data['product_type'] ),
				'tested'       => sanitize_text_field( $data['tested'] ),
				'requires'     => sanitize_text_field( $data['requires'] ),
				'requires_php' => sanitize_text_field( $data['requires_php'] ),
				'banners'      => $banners_json,
				'description'  => wp_kses_post( $data['description'] ),
				'author_name'  => sanitize_text_field( $data['author_name'] ),
				'homepage_url' => esc_url_raw( $data['homepage_url'] ),
				'demo_url'     => esc_url_raw( $data['demo_url'] ),
				'icon_url'     => esc_url_raw( $data['icon_url'] ),
				'status'       => sanitize_key( $data['status'] ),
			];

			if ( ! empty( $data['created_by'] ) ) {
				$update_fields['created_by'] = absint( $data['created_by'] );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->licenser_products,
				$update_fields,
				[ 'id' => $product_id ]
			);

			$insert_id = $product_id;
			$uuid      = ! empty( $data['uuid'] ) ? sanitize_text_field( $data['uuid'] ) : '';
			$this->clear_cache( $insert_id, $uuid );
		} else {
			$uuid = wp_generate_uuid4();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->licenser_products,
				[
					'name'         => sanitize_text_field( $data['name'] ),
					'slug'         => sanitize_title( $data['slug'] ),
					'uuid'         => $uuid,
					'product_type' => sanitize_key( $data['product_type'] ),
					'tested'       => sanitize_text_field( $data['tested'] ),
					'requires'     => sanitize_text_field( $data['requires'] ),
					'requires_php' => sanitize_text_field( $data['requires_php'] ),
					'banners'      => $banners_json,
					'description'  => wp_kses_post( $data['description'] ),
					'author_name'  => sanitize_text_field( $data['author_name'] ),
					'homepage_url' => esc_url_raw( $data['homepage_url'] ),
					'demo_url'     => esc_url_raw( $data['demo_url'] ),
					'icon_url'     => esc_url_raw( $data['icon_url'] ),
					'created_by'   => get_current_user_id(),
					'status'       => sanitize_key( $data['status'] ),
				]
			);

			$insert_id = (int) $wpdb->insert_id;
		}

		// Optional product release.
		if ( $insert_id > 0 && ! empty( $data['version'] ) && ! empty( $data['download_link'] ) && ! empty( $data['release_date'] ) ) {
			ProductRelease::instance()->create(
				[
					'product_id'    => $insert_id,
					'version'       => $data['version'],
					'changelog'     => $data['changelog'],
					'file_name'     => $data['file_name'],
					'download_link' => $data['download_link'],
					'release_date'  => $data['release_date'],
				]
			);
		}

		// Optional license packages.
		if ( $insert_id > 0 && ! empty( $data['license_packages'] ) && is_array( $data['license_packages'] ) ) {
			foreach ( $data['license_packages'] as $package ) {
				if ( ! is_array( $package ) || empty( $package['label'] ) ) {
					continue;
				}

				LicensePackage::instance()->create(
					[
						'id'            => isset( $package['id'] ) ? $package['id'] : '',
						'product_id'    => $insert_id,
						'package_id'    => ! empty( $package['package_id'] ) ? $package['package_id'] : wp_generate_uuid4(),
						'label'         => $package['label'],
						'update_period' => isset( $package['update_period'] ) ? $package['update_period'] : 0,
						'domain_limit'  => isset( $package['domain_limit'] ) ? $package['domain_limit'] : 0,
					]
				);
			}
		}

		if ( $insert_id > 0 ) {
			$this->clear_cache( $insert_id );

			/**
			 * Fires after a product is created or updated.
			 *
			 * @param int   $insert_id Product ID.
			 * @param array $data      Input payload.
			 */
			do_action( 'licenser_product_saved', $insert_id, $data );
		}

		return $insert_id;
	}

	/**
	 * Clear transient caches for a product by ID and UUID.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $uuid       Optional product UUID.
	 * @return void
	 */
	public function clear_cache( $product_id, $uuid = '' ) {
		$product_id = absint( $product_id );

		if ( $product_id > 0 ) {
			licenser_delete_cache( 'product_' . $product_id, true );

			if ( '' === $uuid ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$uuid = (string) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT uuid FROM {$wpdb->licenser_products} WHERE id = %d LIMIT 1",
						$product_id
					)
				);
			}
		}

		if ( '' !== $uuid ) {
			licenser_delete_cache( 'product_' . sanitize_key( $uuid ), true );
		}
	}

	/**
	 * Delete a product by ID, optionally cascading linked releases and packages.
	 *
	 * @param int  $id      Product ID.
	 * @param bool $cascade Whether to also delete linked releases and packages.
	 * @return bool
	 */
	public function delete( $id, $cascade = false ) {
		global $wpdb;

		$id = absint( $id );
		$this->clear_cache( $id );

		if ( $cascade ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->licenser_product_releases,
				[ 'product_id' => $id ],
				[ '%d' ]
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete(
				$wpdb->licenser_license_packages,
				[ 'product_id' => $id ],
				[ '%d' ]
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->licenser_products,
			[ 'id' => $id ],
			[ '%d' ]
		);

		return true;
	}

	/**
	 * Update product status.
	 *
	 * @param int    $id     Product ID.
	 * @param string $status Target status (`active`, `trash`, `draft`).
	 * @return bool
	 */
	public function update_status( $id, $status ) {
		if ( ! in_array( $status, [ 'active', 'trash', 'draft' ], true ) ) {
			return false;
		}

		global $wpdb;

		$id = absint( $id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->licenser_products,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		$this->clear_cache( $id );

		return true;
	}
}
