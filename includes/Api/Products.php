<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\RestController;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Admin Products & Packages REST API controller.
 */
class Products extends RestController {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->rest_base = 'products';
	}

	/**
	 * Register product and package routes.
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

		// Delete product package by ID.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/packages/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_package' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Package ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);

		// Create or list product packages.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/packages',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_package' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'product_id'    => [
							'description' => __( 'Product ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'package_id'    => [
							'description' => __( 'Package ID.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
						'label'         => [
							'description' => __( 'Package Label.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
						'update_period' => [
							'description' => __( 'Update Period.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
						'domain_limit'  => [
							'description' => __( 'Domain Limit.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_packages' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						'product_id' => [
							'description' => __( 'Product ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Single product delete route.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'id' => [
							'description' => __( 'Product ID.', 'licenser' ),
							'type'        => 'integer',
							'required'    => true,
						],
					],
				],
			]
		);
	}

	/**
	 * Get a collection of Licenser products.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$per_page = absint( $request->get_param( 'per_page' ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ) );

		$args = [
			'number'       => $per_page > 0 ? $per_page : 20,
			'offset'       => ( $page - 1 ) * ( $per_page > 0 ? $per_page : 20 ),
			'product_type' => sanitize_key( (string) $request->get_param( 'product_type' ) ),
			'status'       => sanitize_key( (string) $request->get_param( 'status' ) ),
			'inc_packages' => (bool) $request->get_param( 'inc_packages' ),
		];

		$products = Product::instance()->get_all( $args );

		return rest_ensure_response( is_array( $products ) ? $products : [] );
	}

	/**
	 * Create or update a product.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		$params     = $request->get_params();
		$product_id = Product::instance()->create( $params );
		$product    = Product::instance()->get(
			$product_id,
			[
				'cache' => false,
			]
		);

		return rest_ensure_response( $product ? $product : [ 'id' => $product_id ] );
	}

	/**
	 * Delete a single product.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$deleted = Product::instance()->delete( $id );

		return rest_ensure_response( $deleted );
	}

	/**
	 * Delete a single license package.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_package( $request ) {
		$id      = absint( $request->get_param( 'id' ) );
		$package = LicensePackage::instance()->get( $id );

		if ( ! $package ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid Package ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		$deleted = LicensePackage::instance()->delete( $id );

		return rest_ensure_response(
			[
				'deleted' => $deleted,
				'package' => $package,
			]
		);
	}

	/**
	 * Create a single license package.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function create_package( $request ) {
		$params     = $request->get_params();
		$package_id = LicensePackage::instance()->create( $params );

		return rest_ensure_response( LicensePackage::instance()->get( $package_id ) );
	}

	/**
	 * Get packages belonging to a product.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_packages( $request ) {
		$packages = LicensePackage::instance()->get_all(
			[
				'product_id' => absint( $request->get_param( 'product_id' ) ),
				'number'     => -1,
			]
		);

		return rest_ensure_response( is_array( $packages ) ? $packages : [] );
	}
}
