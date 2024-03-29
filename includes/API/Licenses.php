<?php
namespace Licenser\API;
use WP_Error;
use Licenser\Controllers\RestController;
use Licenser\Models\License;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Addressbook Class
 */
class Licenses extends RestController {

    /**
     * Initialize the class
     */
    function __construct() {
        $this->rest_base = 'licenses';
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

        // Generate Key
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/generate-key',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => function( $request ) {
                        return rest_ensure_response( License::instance()->generate_key() );
                    },
                    'permission_callback' => [ $this, 'create_item_permissions_check' ],
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ],
            ]
        );
  
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<action>[\S]+)',
            [
                'args'   => [
                    'action' => [
                        'description' => __( 'Action name will through correct data.' ),
                        'type'        => 'string',
                    ],
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_actions' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                    // 'args'                => $this->get_endpoint_args_for_item_schema( true ),
                ],
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => [ $this, 'get_item_permissions_check' ],
                    // 'args'                => [ 'context' => $this->get_context_param( [ 'default' => 'view' ] ), ],
                    // 'args'                => $this->get_collection_params(),
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

        $licenseobj = new LMFWPPT_LicenseHandler();

        $licenses = $licenseobj->get_all_licenses();

        $response = rest_ensure_response( $licenses );

        return $response;
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

        // Is Lifetime Checkbox Checked
        if( $params['is_lifetime'] == 'on' ){
            $params['is_lifetime'] = 1;
        }
        
        $license = License::instance()->create( $params );
        

        return rest_ensure_response( $params );
    }


    /**
     * Get the license data according to the action
     *
     * @param array $request Request data.
     *
     * @return Object|\WP_Error
     */
    protected function get_license_response( $request ) {

        // License Object Class
        $licenseobj = new LMFWPPT_LicenseHandler();
    
        if( 'info' === $request['action'] ){
            $response = $licenseobj->get_wp_license_details( $request );
        }

        if ( ! $response ) {
            return new WP_Error(
                'rest_license_invalid_id',
                __( 'Invalid Action.' ),
                [ 'status' => 404 ]
            );
        }

        return $response;
    }

    /**
     * Checks if a given request has access to get a specific item.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|bool
     */
    public function get_item_permissions_check( $request ) {

        return true;
    }

    /**
     * Retrieves one item from the collection.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_item( $request ) {
        
        $action = $request->get_param( 'action' );

        $request = [
            'product_slug' => $request->get_param( 'product_slug' ),
            'action'   => $action,
            'license_key'   => $request->get_param( 'license_key' ),
            'domain'   => $request->get_param( 'domain' ),
        ];
        
        $response = $this->get_license_response( $request );
    
        // $response = $this->prepare_item_for_response( $contact, $request );
        $response = rest_ensure_response( $response );

        return $response;
    }

    // Create Actions
    public function create_actions( $request ) {
        $action = $request->get_param( 'action' );

        $request = [
            'product_slug' => $request->get_param( 'product_slug' ),
            'action'   => $action,
            'license_key'   => $request->get_param( 'license_key' ),
            'domain'   => $request->get_param( 'domain' ),
        ];

        // License Object Class
        $licenseobj = new LMFWPPT_LicenseHandler();

        // If action is activate_license then activate the license
        if ( 'validate' === $action ) {
            $response = $licenseobj->download_product( $request );
        }

        if ( ! $response ) {
            return new WP_Error(
                'rest_license_invalid_id',
                __( 'Invalid Action.' ),
                [ 'status' => 404 ]
            );
        }

        return rest_ensure_response( $response );
    }



}