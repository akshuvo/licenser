<?php
/**
 * Global helper functions for Licenser.
 *
 * @package Licenser
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the full prefixed name of a Licenser database table.
 *
 * @param string $table Unprefixed table suffix.
 * @return string
 */
function licenser_table( $table ) {
	global $wpdb;
	return $wpdb->prefix . 'licenser_' . $table;
}

/**
 * Format a date string using site timezone when no timestamp is supplied.
 *
 * @param string   $format    PHP date format string.
 * @param int|null $timestamp Optional Unix timestamp.
 * @return string
 */
function licenser_date( $format, $timestamp = null ) {
	if ( is_null( $timestamp ) ) {
		return wp_date( $format );
	}

	return gmdate( $format, (int) $timestamp );
}

/**
 * Get a single option from Licenser settings.
 *
 * @param string|null $name Setting key name.
 * @return mixed
 */
function licenser_get_option( $name = null ) {
	if ( ! $name ) {
		return '';
	}

	$settings = get_option( 'licenser_settings', [] );
	return is_array( $settings ) && isset( $settings[ $name ] ) ? $settings[ $name ] : '';
}

/**
 * Get the base site URL for Licenser public API routes.
 *
 * @return string
 */
function licenser_api_url() {
	return apply_filters( 'licenser_api_url', home_url( '/' ) );
}

/**
 * Build the public download URL for a product release.
 *
 * @param string $product_uuid Product UUID.
 * @param string $license_key  Optional license key.
 * @return string
 */
function licenser_product_download_url( $product_uuid, $license_key = '' ) {
	return add_query_arg(
		[
			'license_key' => $license_key,
		],
		trailingslashit( licenser_api_url() ) . 'wp-json/licenser/v1/public/products/download/' . rawurlencode( (string) $product_uuid )
	);
}

/**
 * Parse a file path or URL and determine whether it is local or remote.
 *
 * @param string $file_path File URL or filesystem path.
 * @return array{remote_file: bool, file_path: string}
 */
function licenser_parse_file_path( $file_path ) {
	$wp_uploads     = wp_upload_dir();
	$wp_uploads_dir = $wp_uploads['basedir'];
	$wp_uploads_url = $wp_uploads['baseurl'];

	/*
	 * Replace uploads dir, site URL, etc. with absolute counterparts if possible.
	 * Note the str_replace on site_url is intentional so HTTP files still resolve when HTTPS is forced.
	 */
	$replacements = [
		$wp_uploads_url                  => $wp_uploads_dir,
		network_site_url( '/', 'https' ) => ABSPATH,
		str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
		site_url( '/', 'https' )         => ABSPATH,
		str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
	];

	$count            = 0;
	$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path, $count );
	$parsed_file_path = wp_parse_url( $file_path );
	$remote_file      = ( 0 === $count );

	// Paths that begin with '//' are always remote URLs.
	if ( '//' === substr( $file_path, 0, 2 ) ) {
		return [
			'remote_file' => true,
			'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
		];
	}

	// See if path needs ABSPATH prepended to work.
	if ( file_exists( ABSPATH . $file_path ) ) {
		$remote_file = false;
		$file_path   = ABSPATH . $file_path;
	} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
		$remote_file = false;
		$resolved    = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );
		$file_path   = $resolved ? $resolved : $file_path;
	} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], [ 'http', 'https', 'ftp' ], true ) ) && isset( $parsed_file_path['path'] ) ) {
		$remote_file = false;
		$file_path   = $parsed_file_path['path'];
	}

	return [
		'remote_file' => $remote_file,
		'file_path'   => $file_path,
	];
}

/**
 * Save media frame open/close state via AJAX so release uploads go to `/license-manager`.
 *
 * @return void
 */
function licenser_media_frame_state_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'licenser' ) ], 403 );
	}

	if ( ! check_ajax_referer( 'licenser_media_frame_state', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'licenser' ) ], 403 );
	}

	$raw_state = isset( $_POST['state'] ) ? sanitize_key( wp_unslash( $_POST['state'] ) ) : 'close';
	$state     = ( 'open' === $raw_state ) ? 'open' : 'close';

	update_option( 'licenser_media_frame_state', $state );
	wp_send_json_success( $state );
}
add_action( 'wp_ajax_licenser_media_frame_state', 'licenser_media_frame_state_action' );

/**
 * Attach custom upload directory filter prior to media upload.
 *
 * @param array $file Uploaded file data.
 * @return array
 */
function licenser_license_manager_pre_upload( $file ) {
	if ( current_user_can( 'manage_options' ) && 'open' === get_option( 'licenser_media_frame_state' ) ) {
		add_filter( 'upload_dir', 'licenser_license_manager_uploads_dir' );
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'licenser_license_manager_pre_upload' );

/**
 * Redirect uploads into `uploads/license-manager` while the Licenser media modal is open.
 *
 * @param array $param Upload directory parameters.
 * @return array
 */
function licenser_license_manager_uploads_dir( $param ) {
	if ( current_user_can( 'manage_options' ) && 'open' === get_option( 'licenser_media_frame_state' ) ) {
		$mydir         = '/license-manager';
		$param['path'] = $param['basedir'] . $mydir;
		$param['url']  = $param['baseurl'] . $mydir;
	}

	return $param;
}

/**
 * Normalize a domain or URL to `host/path` without scheme, `www.`, or trailing slash.
 *
 * @param string $url Raw URL or domain string.
 * @return string
 */
function licenser_get_clean_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$parsed_url = wp_parse_url( $url );
	$host       = isset( $parsed_url['host'] ) ? strtolower( $parsed_url['host'] ) : '';
	$path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

	$clean_url = $host . $path;

	// Remove leading www.
	$clean_url = preg_replace( '/^www\./i', '', $clean_url );

	// Remove trailing slash.
	$clean_url = untrailingslashit( (string) $clean_url );

	return $clean_url;
}

/**
 * Ensure a stored clean domain has a URL scheme for clickable links.
 *
 * @param string $domain Normalized domain or full URL.
 * @return string
 */
function licenser_format_domain_url( $domain ) {
	$domain = trim( (string) $domain );
	if ( '' === $domain ) {
		return '';
	}

	if ( ! preg_match( '#^https?://#i', $domain ) ) {
		$domain = 'https://' . ltrim( $domain, '/' );
	}

	return $domain;
}

/**
 * Return the transient cache prefix used by Licenser.
 *
 * @return string
 */
function licenser_cache_key() {
	return 'licenser_cache_';
}

/**
 * Retrieve a cached transient value.
 *
 * @param string $key Cache key suffix.
 * @return mixed
 */
function licenser_get_cache( $key ) {
	return get_transient( licenser_cache_key() . $key );
}

/**
 * Store a value in transient cache.
 *
 * @param string $key        Cache key suffix.
 * @param mixed  $value      Value to store.
 * @param int    $expiration Expiration in seconds (default 1 hour).
 * @return bool
 */
function licenser_set_cache( $key, $value, $expiration = HOUR_IN_SECONDS ) {
	return set_transient( licenser_cache_key() . $key, $value, $expiration );
}

/**
 * Delete one or all matching Licenser transient cache entries.
 *
 * @param string $key        Cache key or prefix.
 * @param bool   $sql_method Whether to delete all transients starting with `$key`.
 * @return bool|int
 */
function licenser_delete_cache( $key, $sql_method = false ) {
	$full_key = licenser_cache_key() . $key;

	if ( $sql_method ) {
		global $wpdb;

		$like_transient = $wpdb->esc_like( '_transient_' . $full_key ) . '%';
		$like_timeout   = $wpdb->esc_like( '_transient_timeout_' . $full_key ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_transient,
				$like_timeout
			)
		);
	}

	return delete_transient( $full_key );
}
