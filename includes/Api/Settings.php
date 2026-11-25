<?php

namespace Licenser\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Controllers\RestController;
use Licenser\Models\Settings as SettingsModel;
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
}
