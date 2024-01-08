<?php
namespace Licenser\API;
use Licenser\Controllers\RestController;
use Licenser\Models\Settings as SettingsModel;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST Class
 */
class Settings extends RestController {

    /**
     * Initialize the class
     */
    function __construct() {
        $this->rest_base = 'settings';
    }

    /**
     * Registers the routes for the objects of the controller.
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
                    'args'                => [
                        'lmfwppt_settings' => [
                            'description' => __( 'Settings data.' ),
                            'required'    => true,
                        ],
                    ]
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        $products = get_posts( [
            'post_type'      => 'product',
            'posts_per_page' => -1,
        ] );

        $data = [];

        foreach ( $products as $product ) {
            $data[] = [
                'id'   => $product->ID,
                'name' => $product->post_title,
            ];
        }

        return rest_ensure_response( $data );
    }


    /**
     * Create a single item
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {

        $params = $request->get_params('lmfwppt_settings');
        
        $existing_settings = get_option('lmfwppt_settings', array());

        update_option('lmfwppt_settings', $params);
        return rest_ensure_response( $params );
    }


}