<?php
namespace Licenser\API;
use WP_Error;
use Licenser\Controllers\RestController;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Addressbook Class
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
  
        // Get all products
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_items' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
                // 'args'                => [ 'context' => $this->get_context_param( [ 'default' => 'view' ] ), ],
                // 'args'                => $this->get_collection_params(),
            ]
        );
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