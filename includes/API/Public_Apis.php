<?php
namespace Licenser\API;
use Licenser\Controllers\RestController;
use Licenser\Controllers\Product as Product_Controller;
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
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_product_by_uuid' ],
                    'permission_callback' => '__return_true',
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

        // Download Product Route for Public
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/products/download/(?P<uuid>[\S]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'download_product' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'uuid' => [
                            'description' => __( 'Product UUID.' ),
                            'type'        => 'string',
                            'required'    => true,
                        ],
                        'license_key' => [
                            'description' => __( 'License Key.' ),
                            'type'        => 'string',
                            'required'    => false,
                        ],
                    ],
                ],
            ]
        );
        
    }

   

    /**
     * Get a single product by uuid
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_product_by_uuid( $request ) {

        error_log( print_r( $request->get_params(), true ) );

        $uuid = $request->get_param( 'uuid' );

        $product = Product::instance()->get( $uuid, [
            'inc_stable_release' => true,
            'inc_releases' => false,
            'inc_packages' => false,
            'get_by' => 'uuid',
        ]);

        if ( ! $product ) {
            return new WP_Error(
                'not_found',
                __( 'Invalid Product UUID.' ),
                [ 'status' => 404 ]
            );
        }

        // Check product type
        if( $product->product_type == 'plugin' ){
            $response = $this->prepare_response_for_plugin( $product, $request );
        } elseif( $product->product_type == 'theme' ){
            $response = $this->prepare_response_for_theme( $product, $request );
        }
  
        error_log( print_r( $response, true ) );
        return rest_ensure_response( $response );
    }

    /**
     * Prepare response for plugin
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return array
     */
    public function prepare_response_for_plugin( $product, $request ) {

        $download_url = licenser_product_download_url( $product->uuid, $request->get_param( 'license_key' ) );

        $response = [
            'id' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'plugin' => $product->slug,
            'url' => $product->homepage_url,
            'icons' => [
                '1x' => $product->icon_url,
                '2x' => $product->icon_url,
            ],
            'banners' => [
                'low' => $product->banners['low'],
                'high' => $product->banners['high'],
            ],
            'tested' => $product->tested,
            'requires_php' => $product->requires_php,
            'requires' => $product->requires,
            'sections' => [
                'description' => $product->description,
                'changelog' => '<h3>1.2.3</h3> <p><em>Release Date - Jan 8, 2024</em></p> <p>Test</p>',
            ],
            'new_version' => $product->stable_release->version,
            'last_updated' => $product->stable_release->release_date,
            'package' => $download_url,
            'download_link' => $download_url,
        ];

        return $response;
    }

    /**
     * Prepare response for theme
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return array
     */
    public function prepare_response_for_theme( $product, $request ) {

        $download_url = licenser_product_download_url( $product->uuid, $request->get_param( 'license_key' ) );

        $response = [
            'id' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'theme' => $product->slug,
            'url' => $product->homepage_url,
            'icons' => [
                '1x' => $product->icon_url,
                '2x' => $product->icon_url,
            ],
            'banners' => [
                'low' => $product->banners['low'],
                'high' => $product->banners['high'],
            ],
            'tested' => $product->tested,
            'requires_php' => $product->requires_php,
            'requires' => $product->requires,
            'sections' => [
                'description' => $product->description,
                'changelog' => $product->stable_release->changelog,
            ],
            'new_version' => $product->stable_release->version,
            'last_updated' => $product->stable_release->release_date,
            'package' => $download_url,
            'download_link' => $download_url,
        ];

        return $response;
    }

    /**
     * Download a single item by uuid
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function download_product( $request ) {

        $uuid = $request->get_param( 'uuid' );
        $license_key = $request->get_param( 'license_key' );

        $product = Product::instance()->get( $uuid, [
            'inc_stable_release' => true,
            'inc_releases' => false,
            'inc_packages' => false,
            'get_by' => 'uuid',
        ]);

        if ( ! $product ) {
            return new WP_Error(
                'not_found',
                __( 'Invalid Product UUID.' ),
                [ 'status' => 404 ]
            );
        }

        // Download
        return Product_Controller::instance()->download( [
            'product_id' => $product->id,
            'license_key' => $license_key,
            // 'version' => $product->stable_release->version,
        ] );
    }

}