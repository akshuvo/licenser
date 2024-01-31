<?php
namespace Licenser\Controllers;
use Licenser\Models\Product as Product_Model;
use Licenser\Models\License as License_Model;


class License {
    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Check License is valid
     */
    public function check( $license_key ) {
        $license = License_Model::instance()->get( $license_key, [ 'get_by' => 'key' ] );

        // Return if no license found
        if( empty( $license ) ){
            return new \WP_Error( 'invalid_license', __( 'Invalid license key.', 'licenser' ) );
        }

        // Check license status
        if( $license->status != 1 ){
            return new \WP_Error( 'invalid_license', __( 'Invalid license key.', 'licenser' ) );
        }
        
        // Check if license is expired
        if( $license->is_lifetime != 1 ){
            $end_date = strtotime( $license->end_date );
            if( $end_date < time() ){
                return new \WP_Error( 'expired_license', __( 'License is expired.', 'licenser' ) );
            }
        }


        // TODO: Check domain limit and other stuff

        return $license;
    }


}