<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\License as License_Controller;
use Licenser\Controllers\Product as Product_Controller;
use Licenser\Controllers\RestController;
use Licenser\Models\Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Public REST API routes consumed by LicenserWP/client SDK.
 */
class Public_Apis extends RestController {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->rest_base = 'public';
	}

	/**
	 * Register public REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Get product metadata & latest release info by UUID.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/products/(?P<uuid>[\S]+)',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_product_by_uuid' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'uuid' => [
							'description' => __( 'Product UUID.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
					],
				],
			]
		);

		// Check, activate, or deactivate a license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/license/(?P<uuid>[\S]+)/(?P<action>[\S]+)',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_license_action' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'uuid'        => [
							'description' => __( 'Product UUID.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
						'action'      => [
							'description' => __( 'Action.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
							'enum'        => [
								'check',
								'activate',
								'deactivate',
							],
						],
						'license_key' => [
							'description' => __( 'License Key.', 'licenser' ),
							'type'        => 'string',
							'required'    => false,
						],
					],
				],
			]
		);

		// Public release download route (license-authenticated).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/products/download/(?P<uuid>[\S]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'download_product' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'uuid'        => [
							'description' => __( 'Product UUID.', 'licenser' ),
							'type'        => 'string',
							'required'    => true,
						],
						'license_key' => [
							'description' => __( 'License Key.', 'licenser' ),
							'type'        => 'string',
							'required'    => false,
						],
					],
				],
			]
		);
	}

	/**
	 * Handle public license `check`, `activate`, and `deactivate` actions.
	 *
	 * @param WP_REST_Request $request Full request object.
	 * @return WP_REST_Response
	 */
	public function handle_license_action( $request ) {
		$uuid        = sanitize_text_field( (string) $request->get_param( 'uuid' ) );
		$action      = sanitize_key( (string) $request->get_param( 'action' ) );
		$license_key = sanitize_text_field( (string) $request->get_param( 'license_key' ) );

		$product = Product::instance()->get(
			$uuid,
			[
				'inc_stable_release' => false,
				'inc_releases'       => false,
				'inc_packages'       => false,
				'get_by'             => 'uuid',
				'columns'            => [ 'status', 'id' ],
			]
		);

		if ( ! $product ) {
			return rest_ensure_response(
				[
					'success' => false,
					'error'   => __( 'Invalid Product ID.', 'licenser' ),
				]
			);
		}

		if ( 'active' !== $product->status ) {
			return rest_ensure_response(
				[
					'success' => false,
					'error'   => __( 'Product is not active.', 'licenser' ),
				]
			);
		}

		$license_controller = License_Controller::instance();
		$response           = [
			'success' => false,
			'error'   => __( 'Invalid license action.', 'licenser' ),
		];

		if ( 'check' === $action ) {
			$response = $license_controller->check_public(
				[
					'license_key' => $license_key,
					'url'         => (string) $request->get_param( 'url' ),
					'product_id'  => (int) $product->id,
				]
			);
		} elseif ( 'activate' === $action ) {
			$response = $license_controller->activate(
				[
					'license_key' => $license_key,
					'url'         => (string) $request->get_param( 'url' ),
					'is_local'    => (bool) $request->get_param( 'is_local' ),
					'client'      => sanitize_text_field( (string) $request->get_param( 'client' ) ),
					'product_id'  => (int) $product->id,
				]
			);
		} elseif ( 'deactivate' === $action ) {
			$response = $license_controller->deactivate(
				[
					'license_key' => $license_key,
					'url'         => (string) $request->get_param( 'url' ),
					'product_id'  => (int) $product->id,
				]
			);
		}

		if ( is_wp_error( $response ) ) {
			$response = [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get update and package details for a single product by UUID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_product_by_uuid( $request ) {
		$uuid = sanitize_text_field( (string) $request->get_param( 'uuid' ) );

		$product = Product::instance()->get(
			$uuid,
			[
				'inc_stable_release' => true,
				'inc_releases'       => false,
				'inc_packages'       => false,
				'get_by'             => 'uuid',
			]
		);

		if ( ! $product ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid Product UUID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		if ( 'theme' === $product->product_type ) {
			$response = $this->prepare_response_for_theme( $product, $request );
		} else {
			$response = $this->prepare_response_for_plugin( $product, $request );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Prepare WordPress updater payload for a plugin product.
	 *
	 * @param object          $product Product object.
	 * @param WP_REST_Request $request Full request object.
	 * @return array
	 */
	public function prepare_response_for_plugin( $product, $request ) {
		$license_key  = sanitize_text_field( (string) $request->get_param( 'license_key' ) );
		$download_url = licenser_product_download_url( $product->uuid, $license_key );

		if ( ! empty( $license_key ) ) {
			$download_url = add_query_arg( 'refurl', esc_url_raw( (string) $request->get_param( 'url' ) ), $download_url );
		}

		$stable_release = ! empty( $product->stable_release ) ? $product->stable_release : null;
		$changelog      = '';

		if ( $stable_release && ! empty( $stable_release->version ) ) {
			$release_ts = ! empty( $stable_release->release_date ) ? strtotime( (string) $stable_release->release_date ) : false;
			$date_label = $release_ts ? licenser_date( 'M d, Y', $release_ts ) : '';
			$changelog  = '<h4>' . esc_html( $stable_release->version ) . ( $date_label ? ' - ' . esc_html( $date_label ) : '' ) . '</h4>' . wp_kses_post( (string) $stable_release->changelog );
		}

		$banners = is_array( $product->banners ) ? $product->banners : [];

		return [
			'id'            => $product->uuid,
			'name'          => $product->name,
			'slug'          => $product->slug,
			'plugin'        => $product->slug,
			'url'           => $product->homepage_url,
			'icons'         => [
				'1x' => $product->icon_url,
				'2x' => $product->icon_url,
			],
			'banners'       => [
				'low'  => isset( $banners['low'] ) ? $banners['low'] : '',
				'high' => isset( $banners['high'] ) ? $banners['high'] : '',
			],
			'tested'        => $product->tested,
			'requires_php'  => $product->requires_php,
			'requires'      => $product->requires,
			'sections'      => [
				'description' => $product->description,
				'changelog'   => $changelog,
			],
			'new_version'   => ( $stable_release && isset( $stable_release->version ) ) ? $stable_release->version : '',
			'last_updated'  => ( $stable_release && isset( $stable_release->release_date ) ) ? $stable_release->release_date : '',
			'package'       => $download_url,
			'download_link' => $download_url,
			'author'        => $product->author_name,
		];
	}

	/**
	 * Prepare WordPress updater payload for a theme product.
	 *
	 * @param object          $product Product object.
	 * @param WP_REST_Request $request Full request object.
	 * @return array
	 */
	public function prepare_response_for_theme( $product, $request ) {
		$license_key  = sanitize_text_field( (string) $request->get_param( 'license_key' ) );
		$download_url = licenser_product_download_url( $product->uuid, $license_key );

		if ( ! empty( $license_key ) ) {
			$download_url = add_query_arg( 'refurl', esc_url_raw( (string) $request->get_param( 'url' ) ), $download_url );
		}

		$stable_release = ! empty( $product->stable_release ) ? $product->stable_release : null;
		$banners        = is_array( $product->banners ) ? $product->banners : [];

		return [
			'id'            => $product->uuid,
			'name'          => $product->name,
			'slug'          => $product->slug,
			'theme'         => $product->slug,
			'url'           => $product->homepage_url,
			'icons'         => [
				'1x' => $product->icon_url,
				'2x' => $product->icon_url,
			],
			'banners'       => [
				'low'  => isset( $banners['low'] ) ? $banners['low'] : '',
				'high' => isset( $banners['high'] ) ? $banners['high'] : '',
			],
			'tested'        => $product->tested,
			'requires_php'  => $product->requires_php,
			'requires'      => $product->requires,
			'sections'      => [
				'description' => $product->description,
				'changelog'   => ( $stable_release && isset( $stable_release->changelog ) ) ? $stable_release->changelog : '',
			],
			'new_version'   => ( $stable_release && isset( $stable_release->version ) ) ? $stable_release->version : '',
			'last_updated'  => ( $stable_release && isset( $stable_release->release_date ) ) ? $stable_release->release_date : '',
			'package'       => $download_url,
			'download_link' => $download_url,
		];
	}

	/**
	 * Download a release archive for a product by UUID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function download_product( $request ) {
		$uuid        = sanitize_text_field( (string) $request->get_param( 'uuid' ) );
		$license_key = sanitize_text_field( (string) $request->get_param( 'license_key' ) );
		$refurl      = esc_url_raw( (string) $request->get_param( 'refurl' ) );

		if ( empty( $license_key ) ) {
			return new WP_Error(
				'missing_license_key',
				__( 'License key is required.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		$product = Product::instance()->get(
			$uuid,
			[
				'inc_stable_release' => true,
				'inc_releases'       => false,
				'inc_packages'       => false,
				'get_by'             => 'uuid',
			]
		);

		if ( ! $product ) {
			return new WP_Error(
				'not_found',
				__( 'Invalid Product ID.', 'licenser' ),
				[ 'status' => 404 ]
			);
		}

		return Product_Controller::instance()->download(
			[
				'product_id'  => (int) $product->id,
				'license_key' => $license_key,
				'ref_domain'  => $refurl,
			]
		);
	}
}
