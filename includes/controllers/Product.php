<?php
namespace Licenser\Controllers;
use Licenser\Controllers\License as License_Controller;
use Licenser\Models\Product as Product_Model;
use Licenser\Models\ProductRelease;
// use Licenser\Models\License;


class Product {
    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Download Product
     */
    public function download( $args ) {
        $args = wp_parse_args( $args, [
            'product_id' => '',
            'license_key' => '',
            'version' => '',
        ] );
        
        // Check license key
        if( !empty( $args['license_key'] ) ){
            $license = License_Controller::instance()->check( $args['license_key'] );
            if( is_wp_error( $license ) ){
                wp_die( $license->get_error_message() );
            }
        }

        // 'columns' => [],
        //     'get_by' => '',
        //     'version' => '',

        // Get product download url
        $product = ProductRelease::instance()->get( $args['product_id'], [
            'columns' => [ 'download_link' ],
            'version' => $args['version'],
            'get_by' => 'product_id',
        ] );

        error_log( print_r( $product, true ) );


        return $product;
    }
}