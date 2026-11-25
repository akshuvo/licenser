<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\RestController;
use Licenser\Models\License;
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

		// Delete a single activated domain.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/domains/(?P<id>[\d]+)',
			[
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
}
