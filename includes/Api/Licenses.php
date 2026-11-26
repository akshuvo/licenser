<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\RestController;
use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\License_Meta;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Admin Licenses REST API controller.
 */
class Licenses extends RestController {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->rest_base = 'licenses';
	}

	/**
	 * Register routes for licenses, domains, and metadata.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Generate key.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/generate-key',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_key' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [],
				],
			]
		);

		// Bulk generate license keys.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'bulk_create' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'product_id' => [
							'description' => __( 'Product ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'quantity'   => [
							'description' => __( 'Number of keys to generate (1-100).', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Single license route.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'License ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'License ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Add a domain to an existing license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/domains',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_domain' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id'     => [
							'description' => __( 'License ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'domain' => [
							'description' => __( 'Domain URL or hostname.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
					],
				],
			]
		);

		// Update status or delete a single activated domain.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/domains/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_domain' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id'     => [
							'description' => __( 'Domain ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'status' => [
							'description' => __( 'Domain status (0 or 1).', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_domain' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Domain ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Add or update custom metadata for a license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/meta',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_meta' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'id'         => [
							'description' => __( 'License ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- REST parameter name for custom license metadata table.
						'meta_key'   => [
							'description' => __( 'Meta Key.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- REST parameter name for custom license metadata table.
						'meta_value' => [
							'description' => __( 'Meta Value.', 'licenser' ),
							'type'        => 'string',
							'required'    => false,
						],
					],
				],
			]
		);

		// Delete a single license meta row.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/meta/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_meta' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Meta ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Get a collection of licenses.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$per_page = absint( $request->get_param( 'per_page' ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ) );

		$args = [
			'number'      => $per_page > 0 ? $per_page : 20,
			'offset'      => ( $page - 1 ) * ( $per_page > 0 ? $per_page : 20 ),
			'product_id'  => absint( $request->get_param( 'product_id' ) ),
			'source'      => sanitize_text_field( (string) $request->get_param( 'source' ) ),
			'inc_domains' => (bool) $request->get_param( 'inc_domains' ),
		];

		$licenses = License::instance()->get_all( $args );

		return rest_ensure_response( is_array( $licenses ) ? $licenses : [] );
	}

	/**
	 * Get a single license by ID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$license = License::instance()->get( $id );

		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid License ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		return rest_ensure_response( $license );
	}

	/**
	 * Generate a new license key string.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function generate_key( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return rest_ensure_response( License::instance()->generate_key() );
	}

	/**
	 * Create or update a license.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		$params                = $request->get_params();
		$params['is_lifetime'] = ! empty( $params['is_lifetime'] ) ? 1 : 0;

		$license_id = License::instance()->create( $params );

		return rest_ensure_response( License::instance()->get( $license_id ) );
	}

	/**
	 * Delete a single license.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$deleted = License::instance()->delete( $id );

		return rest_ensure_response( $deleted );
	}

	/**
	 * Delete a single activated domain.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function delete_domain( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$deleted = License::instance()->delete_domain( $id );

		return rest_ensure_response( $deleted );
	}

	/**
	 * Delete a single license meta row.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function delete_meta( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$deleted = License_Meta::instance()->delete_by_id( $id );

		return rest_ensure_response( (bool) $deleted );
	}

	/**
	 * Bulk generate 1 to 100 license keys with shared product/package attributes.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function bulk_create( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		$quantity   = absint( $request->get_param( 'quantity' ) );

		if ( $product_id <= 0 ) {
			return new WP_Error(
				'invalid_product',
				__( 'Please select a valid product.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		if ( $quantity < 1 || $quantity > 100 ) {
			return new WP_Error(
				'invalid_quantity',
				__( 'Quantity must be between 1 and 100.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		$package_id   = absint( $request->get_param( 'package_id' ) );
		$status       = ( null !== $request->get_param( 'status' ) ) ? intval( $request->get_param( 'status' ) ) : 1;
		$is_lifetime  = ! empty( $request->get_param( 'is_lifetime' ) ) ? 1 : 0;
		$end_date     = sanitize_text_field( (string) $request->get_param( 'end_date' ) );
		$domain_limit = $request->get_param( 'domain_limit' );
		$source       = sanitize_text_field( (string) $request->get_param( 'source' ) );
		$source       = '' !== $source ? $source : 'bulk';

		// Derive defaults from package when fields are omitted.
		if ( $package_id > 0 ) {
			$package = LicensePackage::instance()->get( $package_id );
			if ( $package ) {
				if ( '' === (string) $domain_limit || null === $domain_limit ) {
					$domain_limit = absint( $package->domain_limit );
				}
				if ( 0 === $is_lifetime && '' === $end_date ) {
					$update_period = absint( $package->update_period );
					if ( $update_period > 0 ) {
						$end_date = licenser_date( 'Y-m-d H:i:s', time() + ( $update_period * DAY_IN_SECONDS ) );
					} else {
						$is_lifetime = 1;
					}
				}
			}
		}

		$license_model = License::instance();
		$created       = [];

		for ( $i = 0; $i < $quantity; $i++ ) {
			$license_id = $license_model->create(
				[
					'status'       => $status,
					'product_id'   => $product_id,
					'package_id'   => $package_id > 0 ? $package_id : '',
					'source'       => $source,
					'source_id'    => 0,
					'end_date'     => $is_lifetime ? null : $end_date,
					'is_lifetime'  => $is_lifetime,
					'domain_limit' => absint( $domain_limit ),
					'license_key'  => $license_model->generate_key(),
				]
			);

			if ( $license_id > 0 ) {
				$row = $license_model->get( $license_id );
				if ( $row ) {
					$created[] = $row;
				}
			}
		}

		return rest_ensure_response(
			[
				'count'    => count( $created ),
				'licenses' => $created,
			]
		);
	}

	/**
	 * Manually add an activated domain to a license.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_domain( $request ) {
		$license_id = absint( $request->get_param( 'id' ) );
		$raw_domain = trim( (string) $request->get_param( 'domain' ) );
		$status     = ( null !== $request->get_param( 'status' ) ) ? ( ( 1 === (int) $request->get_param( 'status' ) ) ? 1 : 0 ) : 1;

		$license = License::instance()->get( $license_id );
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid License ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		// Prepend scheme before normalizing so bare hostnames (`example.com`) parse properly.
		$candidate    = preg_match( '#^https?://#i', $raw_domain ) ? $raw_domain : 'https://' . ltrim( $raw_domain, '/' );
		$clean_domain = licenser_get_clean_url( $candidate );

		if ( '' === $clean_domain || ( false === strpos( $clean_domain, '.' ) && 'localhost' !== $clean_domain ) ) {
			return new WP_Error(
				'invalid_domain',
				__( 'Please enter a valid domain name or URL.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		$existing = License::instance()->get_domain( $candidate, $license_id );
		if ( $existing ) {
			return new WP_Error(
				'domain_exists',
				__( 'This domain is already listed on this license.', 'licenser' ),
				[ 'status' => 409 ]
			);
		}

		$domain_id  = License::instance()->add_domain(
			[
				'license_id' => $license_id,
				'domain'     => $candidate,
				'status'     => $status,
			]
		);
		$domain_row = License::instance()->get_domain_by_id( $domain_id );

		return rest_ensure_response(
			[
				'id'         => $domain_id,
				'license_id' => $license_id,
				'domain'     => $domain_row ? $domain_row->domain : $clean_domain,
				'href'       => licenser_format_domain_url( $domain_row ? $domain_row->domain : $clean_domain ),
				'status'     => $domain_row ? (int) $domain_row->status : $status,
				'dated'      => $domain_row ? licenser_date( 'M d, Y', strtotime( (string) $domain_row->dated ) ) : licenser_date( 'M d, Y' ),
			]
		);
	}

	/**
	 * Toggle a domain's active/inactive status.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_domain( $request ) {
		$id     = absint( $request->get_param( 'id' ) );
		$status = ( 1 === (int) $request->get_param( 'status' ) ) ? 1 : 0;

		$domain_row = License::instance()->get_domain_by_id( $id );
		if ( ! $domain_row ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid Domain ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		License::instance()->update_domain_status( $id, $status );

		return rest_ensure_response(
			[
				'id'     => $id,
				'status' => $status,
			]
		);
	}

	/**
	 * Add or update a custom metadata entry on a license.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function add_meta( $request ) {
		$license_id = absint( $request->get_param( 'id' ) );
		$meta_key   = sanitize_text_field( (string) $request->get_param( 'meta_key' ) );
		$meta_value = sanitize_text_field( (string) $request->get_param( 'meta_value' ) );

		$license = License::instance()->get( $license_id );
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid License ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		if ( '' === $meta_key ) {
			return new WP_Error(
				'missing_meta_key',
				__( 'Meta key is required.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		License_Meta::instance()->update( $license_id, $meta_key, $meta_value );
		$row = License_Meta::instance()->get_row( $license_id, $meta_key );

		return rest_ensure_response( $row );
	}
}
