<?php

namespace Licenser;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer and database migration handler.
 */
class Installer {

	/**
	 * Target database schema version (integer only; never use floats).
	 *
	 * @var int
	 */
	const DB_VERSION = 29;

	/**
	 * Database schema version.
	 *
	 * @var int
	 */
	public $db_version = self::DB_VERSION;

	/**
	 * Option key storing the installed DB version.
	 *
	 * @var string
	 */
	public $db_version_key = 'licenser_db_version';

	/**
	 * Run the installer.
	 *
	 * @return void
	 */
	public function run() {
		$this->add_version();
		$this->run_migration();
	}

	/**
	 * Run database schema creation and upgrades.
	 *
	 * @return void
	 */
	public function run_migration() {
		if ( version_compare( (string) get_option( $this->db_version_key, '1' ), (string) $this->db_version, '<' ) ) {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();
			$table_prefix    = $wpdb->prefix . 'licenser_';

			$schema = [];

			// Products table.
			$schema[] = "CREATE TABLE {$table_prefix}products (
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				slug varchar(255) NOT NULL,
				uuid varchar(191) NOT NULL,
				product_type varchar(30) DEFAULT NULL,
				tested varchar(30) DEFAULT NULL,
				requires varchar(30) DEFAULT NULL,
				requires_php varchar(30) DEFAULT NULL,
				banners text DEFAULT NULL,
				description text DEFAULT NULL,
				author_name varchar(220) DEFAULT NULL,
				homepage_url varchar(220) DEFAULT NULL,
				demo_url varchar(220) DEFAULT NULL,
				icon_url varchar(220) DEFAULT NULL,
				created_by int(20) unsigned NOT NULL,
				status varchar(30) DEFAULT NULL,
				dated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY uuid (uuid),
				KEY idx_uuid (uuid)
			) {$charset_collate};";

			// Product releases table.
			$schema[] = "CREATE TABLE {$table_prefix}product_releases (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				product_id int(11) NOT NULL,
				version varchar(30) DEFAULT NULL,
				changelog text DEFAULT NULL,
				file_name varchar(255) DEFAULT NULL,
				download_link varchar(255) DEFAULT NULL,
				release_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_product_id (product_id)
			) {$charset_collate};";

			// License packages table.
			$schema[] = "CREATE TABLE {$table_prefix}license_packages (
				id int(11) NOT NULL AUTO_INCREMENT,
				product_id int(11) NOT NULL,
				label varchar(255) NOT NULL,
				package_id varchar(191) NOT NULL,
				update_period int(11) DEFAULT NULL,
				domain_limit int(11) DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY package_id (package_id),
				KEY idx_product_id (product_id)
			) {$charset_collate};";

			// Generated licenses table.
			$schema[] = "CREATE TABLE {$table_prefix}licenses (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				status tinyint(1) NOT NULL DEFAULT 1,
				license_key varchar(191) NOT NULL DEFAULT '',
				product_id int(11) NOT NULL,
				package_id int(11) DEFAULT NULL,
				source varchar(100) DEFAULT NULL,
				source_id bigint(20) unsigned NOT NULL DEFAULT 0,
				end_date datetime DEFAULT NULL,
				is_lifetime tinyint(1) NOT NULL DEFAULT 0,
				domain_limit int(11) DEFAULT NULL,
				dated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_license_key (license_key),
				KEY idx_product_id (product_id),
				KEY idx_package_id (package_id),
				KEY idx_source_id (source_id)
			) {$charset_collate};";

			// License meta table.
			$schema[] = "CREATE TABLE {$table_prefix}license_meta (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				license_id bigint(20) unsigned NOT NULL,
				meta_key varchar(191) NOT NULL,
				meta_value longtext NOT NULL,
				PRIMARY KEY  (id),
				KEY idx_license_id (license_id),
				KEY idx_meta_key (meta_key)
			) {$charset_collate};";

			// Activated domains table.
			$schema[] = "CREATE TABLE {$table_prefix}license_domains (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				license_id bigint(20) unsigned NOT NULL,
				domain varchar(255) NOT NULL,
				status tinyint(1) NOT NULL DEFAULT 1,
				dated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				KEY idx_license_id (license_id)
			) {$charset_collate};";

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $schema );

			$this->migrate_legacy_options();

			update_option( $this->db_version_key, $this->db_version );
		}
	}

	/**
	 * Migrate legacy option and transient keys to `licenser_*` prefixes.
	 *
	 * @return void
	 */
	protected function migrate_legacy_options() {
		global $wpdb;

		$legacy_settings = get_option( 'lmfwppt_settings', null );
		if ( null !== $legacy_settings && false === get_option( Models\Settings::OPTION_NAME, false ) ) {
			update_option( Models\Settings::OPTION_NAME, $legacy_settings );
		}

		if ( null !== $legacy_settings ) {
			delete_option( 'lmfwppt_settings' );
		}

		// Clear any legacy `lwp_cache_*` transients.
		$like_transient = $wpdb->esc_like( '_transient_lwp_cache_' ) . '%';
		$like_timeout   = $wpdb->esc_like( '_transient_timeout_lwp_cache_' ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_transient,
				$like_timeout
			)
		);
	}

	/**
	 * Store installation timestamp and plugin version in wp_options.
	 *
	 * @return void
	 */
	public function add_version() {
		$installed = get_option( 'licenser_installed' );

		if ( ! $installed ) {
			update_option( 'licenser_installed', time() );
		}

		update_option( 'licenser_version', LICENSER_VERSION );
	}
}
