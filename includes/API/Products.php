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

        // Delete Product Package - By Package ID
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
                            'description' => __( 'Package ID.' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/packages',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_package' ],
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => [
                        'product_id' => [
                            'description' => __( 'Product ID.' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                        'package_id' => [
                            'description' => __( 'Package ID.' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                        'label' => [
                            'description' => __( 'Package Label.' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                        'update_period' => [
                            'description' => __( 'Update Period.' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                        'domain_limit' => [
                            'description' => __( 'Domain Limit.' ),
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
                            'description' => __( 'Product ID.' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_item_schema' ],
            ]
        );

        // Product Single Route
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
                            'description' => __( 'Product ID.' ),
                            'type'        => 'integer',
                            'required'    => true,
                        ],
                    ],
                ],
            ]
        );

        // Single Product Route
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/products/(?P<uuid>[\S]+)',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_item_by_uuid' ],
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



    /**
     * Delete a single item
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {

        $id = $request->get_param( 'id' );

        $product = Product::instance()->delete( $id );

        return rest_ensure_response( $product );
    }

    /**
     * Delete a single package
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function delete_package( $request ) {

        $id = $request->get_param( 'id' );

        // Get Package
        $package = LicensePackage::instance()->get( $id );

        if ( ! $package ) {
            return new WP_Error(
                'not_found',
                __( 'Invalid Package ID.' ),
                [ 'status' => 404 ]
            );
        }

        // Delete Package
        $deleted = LicensePackage::instance()->delete( $id );

        return rest_ensure_response( [
            'deleted' => $deleted,
            'package' => $package,
        ] );
    }

    /**
     * Create a single package
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function create_package( $request ) {

        $params = $request->get_params();
        
        $package = LicensePackage::instance()->create( $params );

        return rest_ensure_response( $params );
    }

    /**
     * Get a collection of packages
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_packages( $request ) {
        $args = $request->get_params();
        $packages = LicensePackage::instance()->get_all( $args );

        if ( ! $packages ) {
            $packages = [];
        }

        return rest_ensure_response( $packages );
    }

    /**
     * Get a single item by uuid
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item_by_uuid( $request ) {

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