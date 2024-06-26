<?php

namespace Licenser;

/**
 * Installer class
 */
class Installer {

    var $db_version = 27; // initial db version, don't use floats
    var $db_version_key = "licenser_db_version";

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->run_migration();
    }


	function run_migration(){
		if ( version_compare( get_option( 'licenser_db_version', "1" ), $this->db_version, '<' ) ) {
			
			global $wpdb;

	        $charset_collate = $wpdb->get_charset_collate();
            $table_prefix = $wpdb->prefix . 'licenser_';
			
			// Drop Below Table
	        // $wpdb->query("DROP TABLE $table_prefix}products");

	        // Products Table
	        $schema[] = "CREATE TABLE `{$table_prefix}products` (
	          `id` int(11) NOT NULL AUTO_INCREMENT,
	          `name` varchar(255) NOT NULL,
	          `slug` varchar(255) NOT NULL,
			  `uuid` varchar(255) NOT NULL UNIQUE,
	          `product_type` varchar(30) DEFAULT NULL,
	          `tested` varchar(30) DEFAULT NULL,
	          `requires` varchar(30) DEFAULT NULL,
	          `requires_php` varchar(30) DEFAULT NULL,
	          `banners` varchar(250) DEFAULT NULL,
	          `description` text DEFAULT NULL,
	          `author_name` varchar(220) DEFAULT NULL,
			  `homepage_url` varchar(220) DEFAULT NULL,
			  `demo_url` varchar(220) DEFAULT NULL,
			  `icon_url` varchar(220) DEFAULT NULL,
	          `created_by` int(20) unsigned NOT NULL,
              `status` varchar(30) DEFAULT NULL,
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          	PRIMARY KEY (`id`),
				INDEX `idx_uuid` (`uuid`)
	        ) $charset_collate";

			// Drop Below Table
	        // $wpdb->query("DROP TABLE $table_prefix}product_releases");

            // Product Releases Table
            $schema[] = "CREATE TABLE `{$table_prefix}product_releases` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `product_id` int(11) NOT NULL,
              `version` varchar(30) DEFAULT NULL,
              `changelog` text DEFAULT NULL,
              `file_name` varchar(255) DEFAULT NULL,
              `download_link` varchar(255) DEFAULT NULL,
              `release_date` datetime NOT NULL DEFAULT NOW(),
              PRIMARY KEY (`id`),
			  INDEX `idx_product_id` (`product_id`)
            ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE $table_prefix}license_packages");

	        // License Packages Table
	        $schema[] = "CREATE TABLE `{$table_prefix}license_packages` (
	          `id` int(128) NOT NULL AUTO_INCREMENT,
	          `product_id` int(128) NOT NULL,
	          `label` varchar(255) NOT NULL,
	          `package_id` varchar(255) NOT NULL UNIQUE,
	          `update_period` int(128),
	          `domain_limit` int(128),
	          	PRIMARY KEY (`id`),
			    INDEX `idx_product_id` (`product_id`)
	        ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE {$table_prefix}licenses");

	        // Generated Licenses
	        $schema[] = "CREATE TABLE `{$table_prefix}licenses` (
	          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	          `status` tinyint(1) NOT NULL DEFAULT 1,
	          `license_key` varchar(255) NOT NULL DEFAULT '',
	          `product_id` INT(128) NOT NULL,
	          `package_id` INT(128) NULL,
			  `source` varchar(100) NULL,
	          `source_id` bigint(20) unsigned NOT NULL DEFAULT 0,
	          `end_date` datetime DEFAULT NULL,
	          `is_lifetime` tinyint(1) NOT NULL DEFAULT 0,
	          `domain_limit` INT(128) NULL,
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          PRIMARY KEY (`id`),
			  INDEX `idx_license_key` (`license_key`),
			  INDEX `idx_product_id` (`product_id`),
			  INDEX `idx_package_id` (`package_id`),
			  INDEX `idx_source_id` (`source_id`)
	        ) $charset_collate";

			// License Meta Table
			$schema[] = "CREATE TABLE `{$table_prefix}license_meta` (
	          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	          `license_id` bigint(20) unsigned NOT NULL,
	          `meta_key` varchar(255) NOT NULL,
	          `meta_value` longtext NOT NULL,
	          PRIMARY KEY (`id`),
			  INDEX `idx_license_id` (`license_id`),
			  INDEX `idx_meta_key` (`meta_key`)
	        ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE {$table_prefix}license_domains");

	        // Activated Domains
	        $schema[] = "CREATE TABLE `{$table_prefix}license_domains` (
	          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	          `license_id` bigint(20) unsigned NOT NULL,
	          `domain` varchar(255) NOT NULL,
	          `status` tinyint(1) NOT NULL DEFAULT 1,
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          PRIMARY KEY (`id`),
			  INDEX `idx_license_id` (`license_id`)
	        ) $charset_collate";

	        // Call function
	        if ( ! function_exists( 'dbDelta' ) ) {
	            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	        }

	        // Run Database
	        dbDelta( $schema );
			
			// Update databse version
			update_option( 'licenser_db_version', $this->db_version );
		}
	}

    /**
     * Add time and version on DB
     */
    public function add_version() {
        $installed = get_option( 'licenser_installed' );

        if ( ! $installed ) {
            update_option( 'licenser_installed', time() );
        }

        update_option( 'licenser_version', LICENSER_VERSION );
    }
}