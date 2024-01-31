<?php
namespace Licenser\API;
use Licenser\Controllers\RestController;
use Licenser\Models\Product;
use Licenser\Models\LicensePackage;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * REST Class
 */
class Public_Apis extends RestController {

    /**
     * Initialize the class
     */
    function __construct() {
        $this->rest_base = 'public';
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @return void
     */
    public function register_routes() {

        // Product Single Route for Public
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/products/(?P<uuid>[\S]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_product_by_uuid' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                    'args'                => [
                        'uuid' => [
                            'description' => __( 'Product UUID.' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                    ],
                ],
            ]
        );
        
    }

   

    /**
     * Get a single item by uuid
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_product_by_uuid( $request ) {

        error_log( print_r( $request->get_params(), true ) );

        $uuid = $request->get_param( 'uuid' );

        $product = Product::instance()->get_by_uuid( $uuid );

        if ( ! $product ) {
            return new WP_Error(
                'not_found',
                __( 'Invalid Product UUID.' ),
                [ 'status' => 404 ]
            );
        }

        return rest_ensure_response( $product );
    }
}