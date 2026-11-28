<?php

namespace Licenser\Api;

use Licenser\Controllers\RestController;
use Licenser\Models\License;
use Licenser\Models\License_Meta;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;
use Licenser\Models\ProductRelease;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Bounded CSV migrations with a write-free simulation path. */
class Import extends RestController {
	/**
	 * REST capability context and route.
	 *
	 * @var string
	 */
	protected $rest_base = 'import';

	/** Register the administrator-only endpoint; REST cookie authentication verifies its nonce. */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			]
		);
	}

	/**
	 * Target fields shared with the mapping review UI.
	 *
	 * @param string $entity Entity name.
	 * @return string[]
	 */
	public static function fields( $entity ) {
		$fields = [
			'products' => [ 'name', 'slug', 'product_type', 'status', 'author_name', 'homepage_url', 'demo_url', 'icon_url', 'requires', 'tested', 'requires_php', 'description', 'banner_low', 'banner_high', 'version', 'download_link', 'file_name', 'release_date', 'changelog' ],
			'packages' => [ 'product_id', 'product_slug', 'label', 'package_id', 'update_period', 'domain_limit' ],
			'licenses' => [ 'license_key', 'product_id', 'product_slug', 'package_id', 'package_label', 'status', 'domain_limit', 'end_date', 'is_lifetime', 'is_cancelled', 'customer_email', 'customer_name', 'domains', 'source', 'source_id' ],
		];
		return $fields[ $entity ] ?? [];
	}

	/**
	 * Preset aliases, with canonical columns taking precedence in every preset.
	 *
	 * @param string $entity Entity name.
	 * @param string $preset Preset name.
	 * @param array  $headers Normalized CSV headers.
	 * @return array
	 */
	public static function aliases( $entity, $preset, $headers ) {
		if ( 'products' === $entity ) {
			return [
				'name'         => [ 'title', 'product_name' ],
				'product_type' => [ 'type' ],
			];
		}
		if ( 'packages' === $entity ) {
			return [
				'label'         => [ 'plan_name', 'name', 'title' ],
				'domain_limit'  => [ 'sites', 'activation_limit' ],
				'update_period' => [ 'validity_days', 'period' ],
			];
		}
		if ( 'auto' === $preset ) {
			$preset = array_intersect( [ 'secret_key', 'user_email', 'quota', 'plan_id', 'is_cancelled' ], $headers ) ? 'freemius' : ( in_array( 'code', $headers, true ) || in_array( 'expires_at', $headers, true ) ? 'appsero' : 'edd_sl' );
		}
		$maps = [
			'generic'  => [],
			'edd_sl'   => [
				'license_key'    => [ 'key' ],
				'product_id'     => [ 'download' ],
				'domain_limit'   => [ 'activation_limit', 'limit' ],
				'end_date'       => [ 'expiration' ],
				'customer_email' => [ 'email' ],
				'domains'        => [ 'sites' ],
			],
			'appsero'  => [
				'license_key'    => [ 'code' ],
				'domain_limit'   => [ 'activation_limit' ],
				'end_date'       => [ 'expires_at' ],
				'customer_email' => [ 'email' ],
			],
			'freemius' => [
				'license_key'    => [ 'secret_key' ],
				'package_id'     => [ 'plan_id' ],
				'domain_limit'   => [ 'quota' ],
				'end_date'       => [ 'expiration' ],
				'customer_email' => [ 'user_email' ],
			],
		];
		return $maps[ $preset ] ?? [];
	}

	/**
	 * Normalize CSV header names consistently for presets and explicit mappings.
	 *
	 * @param string $header Header text.
	 * @return string
	 */
	private function header( $header ) {
		return sanitize_key( str_replace( ' ', '_', trim( (string) $header ) ) );
	}

	/**
	 * Parse a bounded UTF-8 CSV before performing any mutations.
	 *
	 * @param mixed $csv CSV text.
	 * @return array
	 * @throws \InvalidArgumentException For invalid CSV.
	 */
	private function parse( $csv ) {
		if ( ! is_string( $csv ) || '' === trim( $csv ) || strlen( $csv ) > 2 * 1024 * 1024 || 1 !== preg_match( '//u', $csv ) || false !== strpos( $csv, "\0" ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Provide UTF-8 CSV up to 2 MB.', 'licenser' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Bounded in-memory CSV stream.
		$stream = fopen( 'php://memory', 'r+' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- In-memory stream, no filesystem write.
		fwrite( $stream, preg_replace( '/^\xEF\xBB\xBF/', '', $csv ) );
		rewind( $stream );
		$raw     = fgetcsv( $stream, 0, ',', '"', '' );
		$headers = is_array( $raw ) ? array_map( [ $this, 'header' ], $raw ) : [];
		$rows    = [];
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Streaming CSV iteration.
		while ( false !== ( $cells = fgetcsv( $stream, 0, ',', '"', '' ) ) ) {
			if ( [ null ] === $cells || [ '' ] === $cells ) {
				continue;
			}
			$rows[] = $cells;
			if ( count( $rows ) > 1000 ) {
				break;
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close in-memory stream.
		fclose( $stream );
		if ( count( $rows ) > 1000 || ! $headers || in_array( '', $headers, true ) || count( array_unique( $headers ) ) !== count( $headers ) ) {
			throw new \InvalidArgumentException( esc_html__( 'CSV requires unique named headers and at most 1,000 data rows.', 'licenser' ) );
		}
		return [ $headers, $rows ];
	}

	/**
	 * Validate explicit overrides, including deliberately ignored optional fields.
	 *
	 * @param mixed  $map Input mapping.
	 * @param string $entity Entity name.
	 * @param array  $headers CSV headers.
	 * @return array
	 * @throws \InvalidArgumentException For invalid mappings.
	 */
	private function column_map( $map, $entity, $headers ) {
		if ( null === $map ) {
			return [];
		}
		if ( is_string( $map ) ) {
			$map = json_decode( $map );
		}
		if ( is_object( $map ) ) {
			$map = get_object_vars( $map );
		}
		if ( ! is_array( $map ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Column map must be an object of target fields and CSV headers.', 'licenser' ) );
		}
		foreach ( $map as $field => $header ) {
			if ( ! in_array( $field, self::fields( $entity ), true ) || ! is_string( $header ) || ( '' !== $header && ! in_array( $this->header( $header ), $headers, true ) ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Column map contains an unknown target field or CSV header.', 'licenser' ) );
			}
			$map[ $field ] = $this->header( $header );
		}
		return $map;
	}

	/**
	 * Apply explicit mappings before preset aliases without leaking ignored columns.
	 *
	 * @param array  $row Raw row.
	 * @param string $entity Entity name.
	 * @param array  $aliases Preset aliases.
	 * @param array  $map Explicit overrides.
	 * @return array
	 */
	private function map_row( $row, $entity, $aliases, $map ) {
		$result = [];
		foreach ( self::fields( $entity ) as $field ) {
			if ( array_key_exists( $field, $map ) ) {
				if ( '' !== $map[ $field ] ) {
					$result[ $field ] = $row[ $map[ $field ] ];
				}
				continue;
			}
			foreach ( array_merge( [ $field ], $aliases[ $field ] ?? [] ) as $alias ) {
				if ( isset( $row[ $alias ] ) && '' !== $row[ $alias ] ) {
					$result[ $field ] = $row[ $alias ];
					break;
				}
			}
		}
		return $result;
	}

	/**
	 * Resolve all supplied references, rejecting conflicts rather than hiding typos with a fallback.
	 *
	 * @param array $row Mapped row.
	 * @param int   $default_product Fallback product ID.
	 * @return int
	 * @throws \InvalidArgumentException For invalid product references.
	 */
	private function resolve_product( $row, $default_product ) {
		$id   = $row['product_id'] ?? '';
		$slug = $row['product_slug'] ?? '';
		if ( '' !== $id && ( ! ctype_digit( $id ) || (float) $id > 2147483647 || (int) $id < 1 ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Product ID must be a positive integer.', 'licenser' ) );
		}
		if ( '' !== $slug ) {
			$products = Product::instance()->get_all(
				[
					'slug'   => sanitize_title( $slug ),
					'status' => 'all',
					'number' => 1,
				]
			);
			$product  = is_array( $products ) && $products ? reset( $products ) : false;
			if ( ! $product || ( '' !== $id && (int) $id !== (int) $product->id ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Product slug was not found or conflicts with product_id.', 'licenser' ) );
			}
			$id = (string) $product->id;
		}
		$id = '' === $id ? $default_product : (int) $id;
		// Bypass transient writes even when a product cache is cold during a dry run.
		if ( ! $id || ! Product::instance()->get(
			$id,
			[
				'cache'              => false,
				'inc_packages'       => false,
				'inc_stable_release' => false,
			]
		) ) {
			throw new \InvalidArgumentException( esc_html__( 'Product was not found. Choose a default product or map product_id/product_slug.', 'licenser' ) );
		}
		return $id;
	}

	/**
	 * Parse a database-safe nonnegative integer, optionally accepting unlimited synonyms.
	 *
	 * @param string $value Input value.
	 * @param array  $zero Synonyms for zero.
	 * @return int
	 * @throws \InvalidArgumentException For invalid integers.
	 */
	private function integer( $value, $zero = [] ) {
		$value = in_array( strtolower( $value ), $zero, true ) ? '0' : $value;
		if ( ! ctype_digit( $value ) || (float) $value > 2147483647 ) {
			throw new \InvalidArgumentException( esc_html__( 'Expected a nonnegative integer within the database limit.', 'licenser' ) );
		}
		return (int) $value;
	}

	/**
	 * Validate an HTTP(S) URL before the model sanitizes it.
	 *
	 * @param string $value URL value.
	 * @return string
	 * @throws \InvalidArgumentException For invalid URLs.
	 */
	private function url( $value ) {
		if ( '' !== $value && ( ! filter_var( $value, FILTER_VALIDATE_URL ) || ! in_array( strtolower( (string) wp_parse_url( $value, PHP_URL_SCHEME ) ), [ 'http', 'https' ], true ) ) ) {
			throw new \InvalidArgumentException( esc_html__( 'URLs must be valid HTTP or HTTPS URLs.', 'licenser' ) );
		}
		return esc_url_raw( $value );
	}

	/**
	 * Normalize product metadata and an optional release before any writes.
	 *
	 * @param array $row Mapped row.
	 * @return array
	 * @throws \InvalidArgumentException For invalid product data.
	 */
	private function normalize_product( $row ) {
		$name   = sanitize_text_field( $row['name'] ?? '' );
		$slug   = sanitize_title( ! empty( $row['slug'] ) ? $row['slug'] : $name );
		$type   = strtolower( ! empty( $row['product_type'] ) ? $row['product_type'] : 'plugin' );
		$status = strtolower( isset( $row['status'] ) && '' !== $row['status'] ? $row['status'] : 'active' );
		if ( '' === $name || '' === $slug || strlen( $name ) > 250 || strlen( $slug ) > 250 ) {
			throw new \InvalidArgumentException( esc_html__( 'Product name and a valid slug are required (up to 250 characters).', 'licenser' ) );
		}
		if ( ! in_array( $type, [ 'plugin', 'theme' ], true ) || ! in_array( $status, [ 'active', 'draft' ], true ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Product type must be plugin/theme and status must be active/draft.', 'licenser' ) );
		}
		$data = [
			'name'         => $name,
			'slug'         => $slug,
			'product_type' => $type,
			'status'       => $status,
		];
		foreach ( [ 'author_name', 'requires', 'tested', 'requires_php' ] as $field ) {
			if ( isset( $row[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( $row[ $field ] );
			}
		}
		foreach ( [ 'homepage_url', 'demo_url', 'icon_url', 'banner_low', 'banner_high', 'download_link' ] as $field ) {
			if ( isset( $row[ $field ] ) ) {
				$row[ $field ] = $this->url( $row[ $field ] );
				if ( in_array( $field, [ 'homepage_url', 'demo_url', 'icon_url' ], true ) ) {
					$data[ $field ] = $row[ $field ];
				}
			}
		}
		foreach ( [ 'low', 'high' ] as $size ) {
			if ( isset( $row[ 'banner_' . $size ] ) ) {
				$data['banners'][ $size ] = $row[ 'banner_' . $size ];
			}
		}
		if ( isset( $row['description'] ) ) {
			$data['description'] = wp_kses_post( $row['description'] );
		}
		$release = array_intersect_key( $row, array_flip( [ 'version', 'download_link', 'file_name', 'release_date', 'changelog' ] ) );
		if ( array_filter( $release ) ) {
			if ( empty( $release['version'] ) || empty( $release['download_link'] ) ) {
				throw new \InvalidArgumentException( esc_html__( 'An initial release requires version and download_link.', 'licenser' ) );
			}
			$release['version']   = sanitize_text_field( $release['version'] );
			$release['file_name'] = sanitize_text_field( ! empty( $release['file_name'] ) ? $release['file_name'] : basename( $release['download_link'] ) );
			$release['changelog'] = wp_kses_post( $release['changelog'] ?? '' );
			if ( empty( $release['release_date'] ) ) {
				unset( $release['release_date'] );
			}
			if ( ! empty( $release['release_date'] ) ) {
				$parsed = date_parse( $release['release_date'] );
				if ( $parsed['error_count'] || $parsed['warning_count'] || ! $parsed['year'] || false === strtotime( $release['release_date'] ) ) {
					throw new \InvalidArgumentException( esc_html__( 'Invalid release date.', 'licenser' ) );
				}
				$release['release_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $release['release_date'] ) );
			}
		}
		return [
			'data'    => $data,
			'release' => array_filter( $release ) ? $release : [],
		];
	}

	/**
	 * Get all packages on a product without casting false to an array.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function packages( $product_id ) {
		$packages = LicensePackage::instance()->get_all(
			[
				'product_id' => $product_id,
				'number'     => -1,
			]
		);
		return is_array( $packages ) ? $packages : [];
	}

	/**
	 * Normalize a license package.
	 *
	 * @param array $row Mapped row.
	 * @param int   $default_product Fallback product ID.
	 * @return array
	 * @throws \InvalidArgumentException For invalid package data.
	 */
	private function normalize_package( $row, $default_product ) {
		$label = sanitize_text_field( $row['label'] ?? '' );
		$uuid  = $row['package_id'] ?? '';
		if ( '' === $label || strlen( $label ) > 250 || strlen( $uuid ) > 191 || sanitize_text_field( $uuid ) !== $uuid ) {
			throw new \InvalidArgumentException( esc_html__( 'A package label (up to 250 characters) and a valid optional identifier (up to 191 characters) are required.', 'licenser' ) );
		}
		return [
			'data' => [
				'product_id'    => $this->resolve_product( $row, $default_product ),
				'label'         => $label,
				'package_id'    => $uuid,
				'update_period' => $this->integer( $row['update_period'] ?? '0', [ '', 'lifetime', 'never' ] ),
				'domain_limit'  => $this->integer( $row['domain_limit'] ?? '0', [ '', '-1', 'unlimited' ] ),
			],
		];
	}
	/**
	 * Normalize one row completely before any license, metadata or domain writes.
	 *
	 * @param array $row CSV row values.
	 * @param int   $default_product Fallback product ID.
	 * @return array
	 * @throws \InvalidArgumentException When row validation fails (caught by import).
	 */
	private function normalize_license( $row, $default_product ) {
		$key = trim( $row['license_key'] ?? '' );
		if ( '' === $key || strlen( $key ) > 191 || sanitize_text_field( $key ) !== $key ) {
			throw new \InvalidArgumentException( esc_html__( 'A valid license key (up to 191 characters) is required.', 'licenser' ) );
		}
		$product_id = $this->resolve_product( $row, $default_product );
		$status     = strtolower( isset( $row['status'] ) && '' !== $row['status'] ? $row['status'] : 'active' );
		$statuses   = [
			'active'    => 1,
			'valid'     => 1,
			'1'         => 1,
			'expired'   => 1,
			'inactive'  => 0,
			'disabled'  => 0,
			'revoked'   => 0,
			'cancelled' => 0,
			'canceled'  => 0,
			'0'         => 0,
		];
		if ( ! isset( $statuses[ $status ] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Unknown license status.', 'licenser' ) );
		}
		$limit = $row['domain_limit'] ?? '0';
		$limit = in_array( strtolower( $limit ), [ '', 'unlimited', '0', '-1' ], true ) ? '0' : $limit;
		if ( ! ctype_digit( $limit ) || (float) $limit > 2147483647 ) {
			throw new \InvalidArgumentException( esc_html__( 'Activation limit must be a nonnegative integer or unlimited.', 'licenser' ) );
		}
		$date     = trim( $row['end_date'] ?? '' );
		$lifetime = in_array( strtolower( $date ), [ 'lifetime', 'never', '0' ], true ) || filter_var( $row['is_lifetime'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$end_date = null;
		if ( ! $lifetime && '' !== $date ) {
			$timestamp = ctype_digit( $date ) ? (int) $date : strtotime( $date );
			$parsed    = date_parse( $date );
			if ( false === $timestamp || $timestamp < 0 || ( ! ctype_digit( $date ) && ( $parsed['error_count'] || $parsed['warning_count'] || ! $parsed['year'] ) ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Invalid expiration date.', 'licenser' ) );
			}
			$end_date = gmdate( 'Y-m-d H:i:s', $timestamp );
		}
		if ( 'expired' === $status && ( $lifetime || ! $end_date || strtotime( $end_date ) >= time() ) ) {
			$lifetime = false;
			$end_date = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		}
		$email = trim( $row['customer_email'] ?? '' );
		if ( '' !== $email && ! is_email( $email ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Invalid customer email.', 'licenser' ) );
		}
		$domains = [];
		foreach ( preg_split( '/[;|]/', $row['domains'] ?? '' ) as $domain ) {
			if ( '' === trim( $domain ) ) {
				continue;
			}
			$domain_url = trim( $domain );
			if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $domain_url ) ) {
				$domain_url = 'https://' . $domain_url;
			}
			$scheme = wp_parse_url( $domain_url, PHP_URL_SCHEME );
			if ( ! in_array( strtolower( (string) $scheme ), [ 'http', 'https' ], true ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Invalid activated domain scheme.', 'licenser' ) );
			}
			$clean = licenser_get_clean_url( $domain_url );
			$host  = wp_parse_url( 'https://' . $clean, PHP_URL_HOST );
			if ( ! $clean || ! $host || ! filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Invalid activated domain.', 'licenser' ) );
			}
			$domains[] = $clean;
		}
		$package_id = $row['package_id'] ?? '';
		if ( '' !== $package_id ) {
			$package = ctype_digit( $package_id ) ? LicensePackage::instance()->get( (int) $package_id ) : false;
			if ( ! $package || (int) $package->product_id !== $product_id ) {
				throw new \InvalidArgumentException( esc_html__( 'Package/plan ID does not belong to the selected product. Use a Licenser integer package ID.', 'licenser' ) );
			}
		} elseif ( ! empty( $row['package_label'] ) ) {
			$matches = array_filter(
				$this->packages( $product_id ),
				static function ( $item ) use ( $row ) {
					return 0 === strcasecmp( $item->label, $row['package_label'] );
				}
			);
			if ( count( $matches ) !== 1 ) {
				throw new \InvalidArgumentException( esc_html__( 'Package label must match exactly one package on the selected product.', 'licenser' ) );
			}
			$package_id = (string) reset( $matches )->id;
		}
		$source_id = $this->integer( $row['source_id'] ?? '0', [ '' ] );
		return [
			'data'    => [
				'license_key'  => $key,
				'product_id'   => (int) $product_id,
				'package_id'   => $package_id,
				'status'       => filter_var( $row['is_cancelled'] ?? false, FILTER_VALIDATE_BOOLEAN ) ? 0 : $statuses[ $status ],
				'domain_limit' => (int) $limit,
				'is_lifetime'  => $lifetime ? 1 : 0,
				'end_date'     => $end_date,
				'source_id'    => $source_id,
				'source'       => sanitize_key( $row['source'] ?? 'imported' ),
			],
			'meta'    => array_intersect_key(
				[
					'customer_email' => strtolower( sanitize_email( $email ) ),
					'customer_name'  => sanitize_text_field( $row['customer_name'] ?? '' ),
				],
				$row
			),
			'domains' => array_values( array_unique( $domains ) ),
		];
	}

	/**
	 * Neutralize report strings recursively without altering stored values.
	 *
	 * @param mixed $value Report value.
	 * @return mixed
	 */
	private function report_text( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'report_text' ], $value );
		}
		return is_string( $value ) && preg_match( '/^[\s]*[=+@\-]/u', $value ) ? "'" . $value : $value;
	}

	/**
	 * Read a persisted entity without populating product transients.
	 *
	 * @param string $entity Entity name.
	 * @param int    $id Record ID.
	 * @return object|false
	 */
	private function read( $entity, $id ) {
		if ( 'products' === $entity ) {
			return Product::instance()->get(
				$id,
				[
					'cache'              => false,
					'inc_packages'       => false,
					'inc_stable_release' => false,
				]
			);
		}
		return 'packages' === $entity ? LicensePackage::instance()->get( $id ) : License::instance()->get( $id );
	}

	/**
	 * Check readback because legacy models return IDs even when an update fails.
	 *
	 * @param object|false $saved Persisted record.
	 * @param array        $data Expected fields.
	 * @return bool
	 */
	private function matches( $saved, $data ) {
		if ( ! $saved ) {
			return false;
		}
		foreach ( $data as $field => $value ) {
			if ( in_array( $field, [ 'id', 'package_id' ], true ) && '' === $value ) {
				continue;
			}
			if ( ! property_exists( $saved, $field ) ) {
				return false;
			}
			if ( is_array( $value ) ) {
				$actual = is_array( $saved->$field ) ? $saved->$field : json_decode( (string) $saved->$field, true );
				if ( ! is_array( $actual ) || array_diff_assoc( $value, $actual ) ) {
					return false;
				}
			} elseif ( (string) $saved->$field !== (string) $value ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Persist one fully validated row, reporting partial failures honestly for safe retry with Update.
	 *
	 * @param string       $entity Entity name.
	 * @param array        $normalized Validated row.
	 * @param object|false $existing Existing record.
	 * @return object
	 * @throws \RuntimeException When any write fails.
	 */
	private function save( $entity, $normalized, $existing ) {
		$data = $normalized['data'];
		if ( $existing ) {
			if ( isset( $data['banners'] ) && isset( $existing->banners ) && is_array( $existing->banners ) ) {
				$data['banners'] = array_merge( $existing->banners, $data['banners'] );
			}
			$data = array_merge( (array) $existing, $data, [ 'id' => $existing->id ] );
		}
		if ( 'products' === $entity ) {
			$id = Product::instance()->create( $data );
		} elseif ( 'packages' === $entity ) {
			$id = LicensePackage::instance()->create( $data );
		} else {
			$id = License::instance()->create( $data );
		}
		$saved = $id ? $this->read( $entity, $id ) : false;
		if ( ! $this->matches( $saved, $normalized['data'] ) ) {
			throw new \RuntimeException( esc_html__( 'Record could not be saved. Review the data and retry using Update.', 'licenser' ) );
		}
		if ( 'products' === $entity && ! empty( $normalized['release'] ) ) {
			$release               = $normalized['release'];
			$prior                 = ProductRelease::instance()->get(
				$id,
				[
					'get_by'  => 'product_id',
					'version' => $release['version'],
				]
			);
			$release['product_id'] = $id;
			if ( $prior ) {
				$release['id'] = $prior->id;
			}
			$release_id = ProductRelease::instance()->create( $release );
			if ( ! $release_id || ! $this->matches( ProductRelease::instance()->get( $release_id ), $release ) ) {
				throw new \RuntimeException( esc_html__( 'Product saved, but its release failed. Retry using Update.', 'licenser' ) );
			}
		}
		foreach ( $normalized['meta'] ?? [] as $key => $value ) {
			if ( false === License_Meta::instance()->update( $id, $key, $value ) ) {
				throw new \RuntimeException( esc_html__( 'License saved, but customer metadata failed. Retry using Update.', 'licenser' ) );
			}
		}
		foreach ( $normalized['domains'] ?? [] as $domain ) {
			if ( ! License::instance()->domain_exists( $domain, $id ) && ! License::instance()->add_domain(
				[
					'license_id' => $id,
					'domain'     => $domain,
					'status'     => 1,
				]
			) ) {
				throw new \RuntimeException( esc_html__( 'License saved, but domain import failed. Retry using Update.', 'licenser' ) );
			}
		}
		return $saved;
	}

	/**
	 * Parse, validate and simulate/import rows, retaining virtual records for intra-CSV duplicates.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return array|WP_Error
	 * @throws \InvalidArgumentException Invalid input is caught and returned as a REST error.
	 */
	public function create_item( $request ) {
		$entity = $request->get_param( 'entity' ) ?? 'licenses';
		$preset = $request->get_param( 'preset' ) ?? 'auto';
		$mode   = $request->get_param( 'duplicate_mode' ) ?? 'skip';
		try {
			if ( ! is_string( $entity ) || ! in_array( $entity, [ 'products', 'packages', 'licenses' ], true ) || ! in_array( $mode, [ 'skip', 'update' ], true ) || ! in_array( $preset, 'licenses' === $entity ? [ 'auto', 'generic', 'edd_sl', 'appsero', 'freemius' ] : [ 'auto', 'generic' ], true ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Choose a valid entity, preset and duplicate mode.', 'licenser' ) );
			}
			$default = $request->get_param( 'default_product_id' ) ?? 0;
			if ( ! is_scalar( $default ) ) {
				throw new \InvalidArgumentException( esc_html__( 'Default product ID must be a nonnegative integer.', 'licenser' ) );
			}
			$default                = $this->integer( (string) $default );
			list( $headers, $rows ) = $this->parse( $request->get_param( 'csv' ) );
			$map                    = $this->column_map( $request->get_param( 'column_map' ), $entity, $headers );
		} catch ( \InvalidArgumentException $error ) {
			return new WP_Error( 'invalid_import', $error->getMessage(), [ 'status' => 400 ] );
		}
		// Only an explicit JSON false authorizes writes; omitted, null and malformed values remain previews.
		$dry_run = false !== $request->get_param( 'dry_run' );
		$report  = [
			'dry_run'    => $dry_run,
			'entity'     => $entity,
			'total_rows' => count( $rows ),
			'created'    => 0,
			'updated'    => 0,
			'skipped'    => 0,
			'errors'     => [],
			'preview'    => [],
		];
		$aliases = self::aliases( $entity, $preset, $headers );
		$seen    = [];
		$plans   = [];
		foreach ( $rows as $index => $cells ) {
			$row_number = $index + 2;
			try {
				if ( count( $cells ) !== count( $headers ) ) {
					throw new \InvalidArgumentException( esc_html__( 'Column count does not match the header.', 'licenser' ) );
				}
				$row = $this->map_row( array_combine( $headers, array_map( 'trim', $cells ) ), $entity, $aliases, $map );
				if ( 'products' === $entity ) {
					$normalized = $this->normalize_product( $row );
					$identifier = $normalized['data']['slug'];
					$products   = Product::instance()->get_all(
						[
							'slug'   => $identifier,
							'status' => 'all',
							'number' => 1,
						]
					);
					$existing   = $seen[ $identifier ] ?? ( is_array( $products ) && $products ? reset( $products ) : false );
					if ( $existing && ! isset( $seen[ $identifier ] ) ) {
						$existing = $this->read( 'products', $existing->id );
					}
				} elseif ( 'packages' === $entity ) {
					$normalized = $this->normalize_package( $row, $default );
					$data       = $normalized['data'];
					$pid        = $data['product_id'];
					if ( ! isset( $plans[ $pid ] ) ) {
						$plans[ $pid ] = $this->packages( $pid );
					}
					$identifier = '' !== $data['package_id'] ? $data['package_id'] : $data['label'];
					$existing   = false;
					foreach ( $plans[ $pid ] as $plan ) {
						if ( '' !== $data['package_id'] ? 0 === strcasecmp( $plan->package_id, $data['package_id'] ) : 0 === strcasecmp( $plan->label, $data['label'] ) ) {
							$existing = $plan;
							break;
						}
					}
					if ( '' !== $data['package_id'] ) {
						$global = LicensePackage::instance()->get_all(
							[
								'package_id' => $data['package_id'],
								'number'     => -1,
							]
						);
						foreach ( is_array( $global ) ? $global : [] as $plan ) {
							if ( (int) $plan->product_id !== $pid ) {
								throw new \InvalidArgumentException( esc_html__( 'Package identifier already belongs to another product.', 'licenser' ) );
							}
						}
						foreach ( $seen as $plan ) {
							if ( 0 === strcasecmp( $plan->package_id, $data['package_id'] ) && (int) $plan->product_id !== $pid ) {
								throw new \InvalidArgumentException( esc_html__( 'Package identifier already belongs to another product.', 'licenser' ) );
							}
						}
					}
				} else {
					$normalized = $this->normalize_license( $row, $default );
					$identifier = $normalized['data']['license_key'];
					$existing   = $seen[ strtolower( $identifier ) ] ?? License::instance()->get( $identifier, [ 'get_by' => 'key' ] );
					if ( $existing && (int) $existing->product_id !== $normalized['data']['product_id'] ) {
						throw new \InvalidArgumentException( esc_html__( 'Duplicate key belongs to a different product.', 'licenser' ) );
					}
				}
				$operation = $existing ? ( 'skip' === $mode ? 'skipped' : 'updated' ) : 'created';
				$saved     = $existing;
				if ( 'packages' === $entity && $existing && '' === $normalized['data']['package_id'] ) {
					$normalized['data']['package_id'] = $existing->package_id;
				}
				if ( 'skipped' !== $operation ) {
					$saved = $dry_run ? (object) array_merge( $existing ? (array) $existing : [], $normalized['data'], [ 'id' => $existing ? $existing->id : -$row_number ] ) : $this->save( $entity, $normalized, $existing );
				}
				if ( 'packages' === $entity ) {
					if ( ! $existing ) {
						$plans[ $pid ][] = $saved;
					} else {
						foreach ( $plans[ $pid ] as $key => $plan ) {
							if ( $plan->id === $existing->id ) {
								$plans[ $pid ][ $key ] = $saved;
							}
						}
					}
					$seen[ $saved->id ] = $saved;
				} else {
					$seen[ strtolower( $identifier ) ] = $saved;
				}
				++$report[ $operation ];
				$report['preview'][] = $this->report_text(
					[
						'row'        => $row_number,
						'operation'  => $operation,
						'identifier' => $identifier,
						'summary'    => 'products' === $entity ? $normalized['data']['name'] : ( 'packages' === $entity ? $normalized['data']['label'] : __( 'License with customer metadata and activated domains', 'licenser' ) ),
						'details'    => $normalized,
					]
				);
			} catch ( \Exception $error ) {
				$report['errors'][] = [
					'row'     => $row_number,
					'message' => $this->report_text( $error->getMessage() ),
				];
			}
		}
		return $report;
	}
}
