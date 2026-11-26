<?php

namespace Licenser\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License metadata model class.
 */
class License_Meta {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Default fields.
	 *
	 * @var array
	 */
	public $default_fields = [
		'license_id' => '',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Column name in custom licenser_license_meta table.
		'meta_key'   => '',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Column name in custom licenser_license_meta table.
		'meta_value' => '',
	];

	/**
	 * Get a single meta value for a license.
	 *
	 * @param int    $license_id License ID.
	 * @param string $meta_key   Meta key.
	 * @return string|null
	 */
	public function get_value( $license_id, $meta_key = '' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->licenser_license_meta} WHERE license_id = %d AND meta_key = %s LIMIT 1",
				absint( $license_id ),
				sanitize_text_field( $meta_key )
			)
		);
	}

	/**
	 * Get a single metadata row by license ID and meta key.
	 *
	 * @param int    $license_id License ID.
	 * @param string $meta_key   Meta key.
	 * @return object|null
	 */
	public function get_row( $license_id, $meta_key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, meta_key, meta_value FROM {$wpdb->licenser_license_meta} WHERE license_id = %d AND meta_key = %s LIMIT 1",
				absint( $license_id ),
				sanitize_text_field( $meta_key )
			)
		);
	}

	/**
	 * Get installed product versions mapped by domain ID for a license.
	 *
	 * @param int $license_id License ID.
	 * @return array<int, string>
	 */
	public function get_installed_versions_for_license( $license_id ) {
		global $wpdb;

		$prefix_like = $wpdb->esc_like( 'installed_version_' ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->licenser_license_meta} WHERE license_id = %d AND meta_key LIKE %s",
				absint( $license_id ),
				$prefix_like
			)
		);

		$versions = [];
		foreach ( (array) $rows as $row ) {
			$domain_id = absint( str_replace( 'installed_version_', '', (string) $row->meta_key ) );
			if ( $domain_id > 0 ) {
				$versions[ $domain_id ] = (string) $row->meta_value;
			}
		}

		return $versions;
	}

	/**
	 * Get all metadata rows for a license.
	 *
	 * @param int $license_id License ID.
	 * @return array
	 */
	public function get_all( $license_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, meta_key, meta_value FROM {$wpdb->licenser_license_meta} WHERE license_id = %d",
				absint( $license_id )
			)
		);
	}

	/**
	 * Update a meta value (inserting if it does not exist).
	 *
	 * @param int    $license_id License ID.
	 * @param string $meta_key   Meta key.
	 * @param string $meta_value Meta value.
	 * @return int|false
	 */
	public function update( $license_id, $meta_key, $meta_value ) {
		global $wpdb;

		$license_id = absint( $license_id );
		$meta_key   = sanitize_text_field( $meta_key );
		$meta_value = is_scalar( $meta_value ) ? sanitize_text_field( (string) $meta_value ) : wp_json_encode( $meta_value );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->licenser_license_meta} WHERE license_id = %d AND meta_key = %s",
				$license_id,
				$meta_key
			)
		);

		if ( $exists > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->update(
				$wpdb->licenser_license_meta,
				[
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Indexed custom table update.
					'meta_value' => $meta_value,
				],
				[
					'license_id' => $license_id,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed custom table WHERE clause.
					'meta_key'   => $meta_key,
				],
				[ '%s' ],
				[ '%d', '%s' ]
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->insert(
			$wpdb->licenser_license_meta,
			[
				'license_id' => $license_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Custom table insert.
				'meta_key'   => $meta_key,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Custom table insert.
				'meta_value' => $meta_value,
			],
			[ '%d', '%s', '%s' ]
		);
	}

	/**
	 * Delete a single meta entry by license ID and key.
	 *
	 * @param int    $license_id License ID.
	 * @param string $meta_key   Meta key.
	 * @return int|false
	 */
	public function delete( $license_id, $meta_key ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			$wpdb->licenser_license_meta,
			[
				'license_id' => absint( $license_id ),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed custom table DELETE.
				'meta_key'   => sanitize_text_field( $meta_key ),
			],
			[ '%d', '%s' ]
		);
	}

	/**
	 * Delete a single meta row by its primary key ID.
	 *
	 * @param int $id Meta row ID.
	 * @return int|false
	 */
	public function delete_by_id( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			$wpdb->licenser_license_meta,
			[ 'id' => absint( $id ) ],
			[ '%d' ]
		);
	}

	/**
	 * Delete all metadata for a license.
	 *
	 * @param int $license_id License ID.
	 * @return int|false
	 */
	public function delete_all( $license_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->delete(
			$wpdb->licenser_license_meta,
			[ 'license_id' => absint( $license_id ) ],
			[ '%d' ]
		);
	}
}
