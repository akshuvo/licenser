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

        return [
            'success' => true,
            'status' => 'deactivate',
            'remaining' => 1,
            'activation_limit' => 3,
            'expiry_days' => false,
            'title' => 'Test License',
            'source_id' => 'wc-123',
            'recurring' => 0,
        ];

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

    /**
     * Activate License
     */
    public function activate( $args ) {
        // Check license
        $license = $this->check( $args['license_key'] );

        // Check error
        if( is_wp_error( $license ) ){
            return $license;
        }

        // $license['status']           = 'activate';
        // $license['remaining']        = $response['remaining'];
        // $license['activation_limit'] = $response['activation_limit'];
        // $license['expiry_days']      = $response['expiry_days'];
        // $license['title']            = $response['title'];
        // $license['source_id']        = $response['source_identifier'];
        // $license['recurring']        = $response['recurring'];

        return [
            'success' => true,
            'status' => 'activate',
            'remaining' => 2,
            'activation_limit' => 3,
            'expiry_days' => false,
            'title' => 'Test License',
            'source_id' => 'wc-123',
            'recurring' => 0,
        ];
        
    }

    /**
     * Deactivate License
     */
    public function deactivate( $args ) {
        // Check license
        $license = $this->check( $args['license_key'] );

        // Check error
        if( is_wp_error( $license ) ){
            return $license;
        }

        // $license['status']           = 'deactivate';
        // $license['remaining']        = $response['remaining'];
        // $license['activation_limit'] = $response['activation_limit'];
        // $license['expiry_days']      = $response['expiry_days'];
        // $license['title']            = $response['title'];
        // $license['source_id']        = $response['source_identifier'];
        // $license['recurring']        = $response['recurring'];

        return [
            'success' => true,
            'status' => 'deactivate',
            'remaining' => 2,
            'activation_limit' => 3,
            'expiry_days' => false,
            'title' => 'Test License',
            'source_id' => 'wc-123',
            'recurring' => 0,
        ];
    }
}