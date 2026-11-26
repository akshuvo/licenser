<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\Addons;
use Licenser\Controllers\RestController;
use Licenser\Models\Settings as SettingsModel;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Admin Settings REST API controller.
 */
class Settings extends RestController {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->rest_base = 'settings';
	}

	/**
	 * Register settings routes.
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
					'args'                => [],
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		// Clear product transient caches.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/clear-cache',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'clear_cache' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [],
				],
			]
		);

		// Toggle a built-in or registered integration on or off.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/integrations',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_integration' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'integration' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						],
						'enabled'     => [
							'required' => true,
							'type'     => 'boolean',
						],
					],
				],
			]
		);
	}

	/**
	 * Get saved Licenser settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return rest_ensure_response( SettingsModel::instance()->get_all() );
	}

	/**
	 * Update saved Licenser settings.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		$params = $request->get_params();
		$saved  = SettingsModel::instance()->update( $params );

		return rest_ensure_response( $saved );
	}

	/**
	 * Clear all Licenser transient cache entries.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response
	 */
	public function clear_cache( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$cleared = licenser_delete_cache( '', true );

		return rest_ensure_response(
			[
				'success' => true,
				'cleared' => is_numeric( $cleared ) ? (int) $cleared : 0,
			]
		);
	}

	/**
	 * Enable or disable a store integration (e.g., WooCommerce, EDD).
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_integration( $request ) {
		$slug        = sanitize_key( (string) $request->get_param( 'integration' ) );
		$enabled     = (bool) $request->get_param( 'enabled' );
		$addons      = Addons::instance();
		$integration = $addons->get_integration( $slug );

		if ( ! $integration || empty( $integration['available'] ) ) {
			return new WP_Error(
				'licenser_invalid_integration',
				__( 'The requested integration is not available.', 'licenser' ),
				[ 'status' => 400 ]
			);
		}

		SettingsModel::instance()->set_integration_enabled( $slug, $enabled );

		$dependency_active = $addons->is_dependency_active( $slug );
		if ( ! $enabled ) {
			$status = 'disabled';
		} elseif ( $dependency_active ) {
			$status = 'active';
		} else {
			$status = 'dependency_missing';
		}

		return rest_ensure_response(
			[
				'success'           => true,
				'integration'       => $slug,
				'name'              => isset( $integration['name'] ) ? (string) $integration['name'] : $slug,
				'enabled'           => $enabled,
				'dependency_active' => $dependency_active,
				'dependency_name'   => isset( $integration['dependency_name'] ) ? (string) $integration['dependency_name'] : '',
				'status'            => $status,
			]
		);
	}
}
