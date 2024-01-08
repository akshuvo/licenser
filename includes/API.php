<?php
namespace Licenser;

/**
 * Api class
 */
class Api {
    // Singleton trait
    use Traits\SingletonTrait;

    /**
     * Initialize the class
     */
    public function init() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }
    
    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        // API class mapping
        $controllers = [
            'products' => 'Products',
            'licenses'  => 'Licenses',
            'settings'  => 'Settings',
        ];

        // // Load controller routes based on url
        // $get_current_controller = explode( '/', $_SERVER['REQUEST_URI'] )[4];

        // if( isset( $controllers[ $get_current_controller ] ) ) {
        //     $controller = $controllers[ $get_current_controller ];
        //     $controller = __NAMESPACE__ . '\\Api\\' . $controller;
        //     $controller = new $controller();
        //     $controller->register_routes();
        // }


        // Load all controller routes
        foreach ( $controllers as $key => $controller ) {
           
            $controller = __NAMESPACE__ . '\\Api\\' . $controller;

            $controller = new $controller();
            $controller->register_routes();
        }
    }
}