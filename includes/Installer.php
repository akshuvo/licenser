<?php

namespace Licenser;

/**
 * Installer class
 */
class Installer {

    var $db_version = 25; // initial db version, don't use floats
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
		// if ( version_compare( get_option( 'licenser_db_version', "1" ), $this->db_version, '<' ) ) {
			
			global $wpdb;

	        $charset_collate = $wpdb->get_charset_collate();
            $table_prefix = $wpdb->prefix . 'licenser_';
			
			// Drop Below Table
	        $wpdb->query("DROP TABLE $table_prefix}products");

	        // Products Table
	        $schema[] = "CREATE TABLE `{$table_prefix}products` (
	          `id` int(11) NOT NULL AUTO_INCREMENT,
	          `name` varchar(255) NOT NULL,
	          `slug` varchar(255) NOT NULL,
			  `uuid` varchar(255) NOT NULL UNIQUE,
	          `product_type` varchar(30) DEFAULT NULL,
	          `version` varchar(30) DEFAULT NULL,
	          `tested` varchar(30) DEFAULT NULL,
	          `requires` varchar(30) DEFAULT NULL,
	          `requires_php` varchar(30) DEFAULT NULL,
	          `banners` varchar(250) DEFAULT NULL,
	          `description` text DEFAULT NULL,
	          `author_name` varchar(220) DEFAULT NULL,
			  `homepage_url` varchar(220) DEFAULT NULL,
			  `demo_url` varchar(220) DEFAULT NULL,
	          `created_by` int(20) unsigned NOT NULL,
              `status` varchar(30) DEFAULT NULL,
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          PRIMARY KEY (`id`)
	        ) $charset_collate";

			// Drop Below Table
	        $wpdb->query("DROP TABLE $table_prefix}product_releases");

            // Product Releases Table
            $schema[] = "CREATE TABLE `{$table_prefix}product_releases` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `product_id` int(11) NOT NULL,
              `version` varchar(30) DEFAULT NULL,
              `changelog` text DEFAULT NULL,
              `file_name` varchar(30) DEFAULT NULL,
              `download_link` varchar(255) DEFAULT NULL,
              `release_date` datetime NOT NULL DEFAULT NOW(),
              PRIMARY KEY (`id`)
            ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE $table_prefix}license_packages");

	        // License Packages Table
	        $schema[] = "CREATE TABLE `{$table_prefix}license_packages` (
	          `id` int(128) NOT NULL AUTO_INCREMENT,
	          `product_id` int(128) NOT NULL,
	          `label` varchar(100) NOT NULL,
	          `package_id` varchar(100) NOT NULL,
	          `update_period` int(128),
	          `domain_limit` int(128),
	          PRIMARY KEY (`id`),
	          UNIQUE (`package_id`)
	        ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE {$table_prefix}licenses");

	        // Generated Licenses
	        $schema[] = "CREATE TABLE `{$table_prefix}licenses` (
	          `id` int NOT NULL AUTO_INCREMENT,
	          `status` tinyint(1) NOT NULL DEFAULT 1,
	          `license_key` varchar(255) NOT NULL DEFAULT '',
	          `package_id` varchar(100) NOT NULL,
	          `order_id` int,
	          `end_date` datetime DEFAULT NOW(),
	          `is_lifetime` tinyint(1) NOT NULL DEFAULT 0,
	          `domain_limit` int(100),
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          PRIMARY KEY (`id`)
	        ) $charset_collate";

	        // Drop Below Table
	        // $wpdb->query("DROP TABLE {$table_prefix}license_domains");

	        // Activated Domains
	        $schema[] = "CREATE TABLE `{$table_prefix}license_domains` (
	          `id` int NOT NULL AUTO_INCREMENT,
	          `license_id` int NOT NULL,
	          `domain` varchar(255) NOT NULL,
	          `status` tinyint(1) NOT NULL DEFAULT 1,
	          `dated` datetime NOT NULL DEFAULT NOW(),
	          PRIMARY KEY (`id`)
	        ) $charset_collate";

	        // Call function
	        if ( ! function_exists( 'dbDelta' ) ) {
	            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	        }

	        // Run Database
	        dbDelta( $schema );
			
			// Update databse version
			update_option( 'licenser_db_version', $this->db_version );
		// }
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