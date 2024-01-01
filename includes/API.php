<?php
namespace Licenser;
/**
 * API Class
 */
class Api {

    /**
     * Initialize the class
     */
    function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_api' ] );
    }

    /**
     * Register the API
     *
     * @return void
     */
    public function register_api() {
        // Include the API class file
        require_once dirname( __FILE__ ) . '/API/License.php';
        $licenses = new API\Licenses();
        $licenses->register_routes();
    }
}