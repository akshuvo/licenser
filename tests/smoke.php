<?php
/**
 * Disposable WordPress smoke test for the installed Licenser release ZIP.
 *
 * @package Licenser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

if ( ! class_exists( 'Licenser' ) || ! defined( 'LICENSER_VERSION' ) ) {
	WP_CLI::error( 'Missing Licenser bootstrap class or LICENSER_VERSION constant.' );
}

global $wpdb;

$licenser_expected_tables = [
	$wpdb->prefix . 'licenser_products',
	$wpdb->prefix . 'licenser_product_releases',
	$wpdb->prefix . 'licenser_license_packages',
	$wpdb->prefix . 'licenser_licenses',
	$wpdb->prefix . 'licenser_license_meta',
	$wpdb->prefix . 'licenser_license_domains',
];

foreach ( $licenser_expected_tables as $licenser_table_name ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$licenser_found = $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', $licenser_table_name )
	);

	if ( $licenser_found !== $licenser_table_name ) {
		WP_CLI::error( 'Missing database table after activation: ' . $licenser_table_name );
	}
}

$licenser_rest_server = rest_get_server();
$licenser_routes      = array_keys( $licenser_rest_server->get_routes() );

foreach ( [ '/licenser/v1/products', '/licenser/v1/licenses', '/licenser/v1/settings' ] as $licenser_route ) {
	if ( ! in_array( $licenser_route, $licenser_routes, true ) ) {
		WP_CLI::error( 'Missing expected REST API route: ' . $licenser_route );
	}
}

WP_CLI::success( 'Installed Licenser ZIP activated cleanly; all 6 tables and REST routes verified.' );
