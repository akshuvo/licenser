<?php
namespace Licenser\API;
use Licenser\Controllers\RestController;
use Licenser\Models\Product;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST Class
 */
class Products extends RestController {

    /**
     * Initialize the class
     */
    function __construct() {
        $this->rest_base = 'products';
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
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
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

        $params = $request->get_params();
        
        $product = Product::instance()->create( $params );

        return rest_ensure_response( $params );
    }


}