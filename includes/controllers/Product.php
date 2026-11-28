<?php

namespace Licenser\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\License as License_Controller;
use Licenser\Models\License as License_Model;
use Licenser\Models\License_Meta;
use Licenser\Models\ProductRelease;
use WP_Error;

/**
 * Product download controller.
 */
class Product {

	use \Licenser\Traits\SingletonTraitSelf;

	/**
	 * Validate license ownership and stream or redirect a product release package.
	 *
	 * @param array $args Download parameters (`product_id`, `license_key`, `version`, `ref_domain`).
	 * @return WP_Error|void Exits on successful download; returns WP_Error on failure.
	 */
	public function download( $args ) {
		$args = wp_parse_args(
			$args,
			[
				'product_id'  => 0,
				'license_key' => '',
				'version'     => '',
				'ref_domain'  => '',
			]
		);

		$product_id  = absint( $args['product_id'] );
		$license_key = sanitize_text_field( (string) $args['license_key'] );

		if ( empty( $license_key ) ) {
			return new WP_Error(
				'missing_license_key',
				__( 'License key is required.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		// Validate license key, status, expiry, and product ownership.
		$license = License_Controller::instance()->check(
			$license_key,
			[
				'product_id'             => $product_id,
				'url'                    => $args['ref_domain'],
				'enforce_domain_limit'   => false,
				'reject_inactive_domain' => true,
			]
		);

		if ( is_wp_error( $license ) ) {
			/**
			 * Fires when a product release download fails license validation.
			 *
			 * @param WP_Error $license Validation error.
			 * @param array    $args    Download parameters.
			 */
			do_action( 'licenser_product_download_failed', $license, $args );

			return new WP_Error(
				$license->get_error_code(),
				$license->get_error_message(),
				[ 'status' => 403 ]
			);
		}

		// Get requested or latest release.
		$release = ProductRelease::instance()->get(
			$product_id,
			[
				'columns' => [ 'download_link', 'version', 'file_name' ],
				'version' => $args['version'],
				'get_by'  => 'product_id',
			]
		);

		if ( empty( $release ) || empty( $release->download_link ) ) {
			return new WP_Error(
				'release_not_found',
				__( 'No release found for this product.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		// Record installed version for the calling activated domain.
		$domain_id = ! empty( $args['ref_domain'] ) ? License_Model::instance()->domain_exists( $args['ref_domain'], $license->id ) : '';

		if ( ! empty( $domain_id ) && ! empty( $release->version ) ) {
			License_Meta::instance()->update( $license->id, 'installed_version_' . absint( $domain_id ), $release->version );
		}

		/**
		 * Fires right before a release file is delivered.
		 *
		 * @param object $release Product release record.
		 * @param object $license Validated license record.
		 * @param array  $args    Download parameters.
		 */
		do_action( 'licenser_before_product_download', $release, $license, $args );

		$download_link    = (string) $release->download_link;
		$parsed_file_path = licenser_parse_file_path( $download_link );

		// Redirect for remote release files hosted on external URLs.
		if ( ! empty( $parsed_file_path['remote_file'] ) ) {
			if ( ! wp_http_validate_url( $download_link ) ) {
				return new WP_Error(
					'invalid_download_url',
					__( 'Invalid remote download URL.', 'licenser' ),
					[ 'status' => 500 ]
				);
			}

			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Release ZIPs may be hosted on external CDNs/storage.
			wp_redirect( esc_url_raw( $download_link ) );
			exit;
		}

		$file_path = isset( $parsed_file_path['file_path'] ) ? (string) $parsed_file_path['file_path'] : '';
		$real_file = '' !== $file_path ? realpath( $file_path ) : false;
		$uploads   = wp_upload_dir();

		/**
		 * Filter the list of local directories allowed for release file downloads.
		 *
		 * Defaults to the WordPress uploads base directory to prevent streaming
		 * core or configuration files outside uploads.
		 *
		 * @param string[] $dirs Allowed local base directories.
		 */
		$allowed_dirs = apply_filters( 'licenser_allowed_download_dirs', [ $uploads['basedir'] ] );
		$is_allowed   = false;

		if ( $real_file && is_array( $allowed_dirs ) ) {
			foreach ( $allowed_dirs as $dir ) {
				$real_dir = realpath( (string) $dir );
				if ( $real_dir && 0 === strpos( $real_file, $real_dir . DIRECTORY_SEPARATOR ) ) {
					$is_allowed = true;
					break;
				}
			}
		}

		$base_name       = $real_file ? basename( $real_file ) : '';
		$is_blocked_file = ( '' === $base_name || 0 === strpos( $base_name, '.' ) || (bool) preg_match( '/\.(php\d*|phtml|phar|htaccess|htpasswd|ini|env|sql|log)$/i', $base_name ) );

		// Confirm the resolved local file exists, is readable, is not a sensitive/executable file, and stays inside an allowed directory.
		if ( ! $real_file || ! $is_allowed || $is_blocked_file || ! is_file( $real_file ) || ! is_readable( $real_file ) ) {
			return new WP_Error(
				'file_not_found',
				__( 'Release file could not be found on server.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		$download_file_name = sanitize_file_name( ! empty( $release->file_name ) ? $release->file_name : basename( $real_file ) );

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $download_file_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . filesize( $real_file ) );

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming binary ZIP directly to client.
		readfile( $real_file );
		exit;
	}
}
