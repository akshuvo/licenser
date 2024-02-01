<?php
namespace Licenser\Controllers;
use Licenser\Models\Product as Product_Model;
use Licenser\Models\License as License_Model;


class License {
    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Check License is valid
     */
    public function check( $license_key, $args = [] ) {

        $args = wp_parse_args( $args, [
            'url' => '',
        ] );

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

        // Domain Exists
        $license->exists_domain_id = !empty( $args['url'] ) ? License_Model::instance()->domain_exists( $args['url'], $license->id ) : false;

        // Domain limit check
        if( $license->domain_limit > 0 ){
            // Get domain count
            $domain_count = License_Model::instance()->get_domains( [
                'license_id' => $license->id,
                'count_total' => true,
            ] );

            if( !$license->exists_domain_id && $domain_count >= $license->domain_limit ){
                return new \WP_Error( 'domain_limit_exceeded', __( 'Domain limit exceeded.', 'licenser' ) );
            }
        }

        return $license;
    }

    /**
     * Activate License
     */
    public function activate( $args ) {
        // license key
        $license_key = $args['license_key'];
        $url = $args['url'];

        // Check license
        $license = $this->check( $args['license_key'], $args );

        // Check error
        if( is_wp_error( $license ) ){
            return [
                'success' => false,
                'error' => $license->get_error_message(),
            ];
        }

        // License ID
        $license_id = $license->id;

       

        // TODO: If domain exists and, dont add again and return success. no need to update status

        // Add domain
        $add_domain = License_Model::instance()->add_domain( [
            'id' => $license->exists_domain_id,
            'license_id' => $license_id,
            'domain' => $url,
            'status' => '1'
        ] );

        return $this->refresh( $license_id, 'id' );
    }
    
    /**
     * Refresh License
     */
    public function refresh( $license_key, $get_by = 'key' ) {
        // Check license
        $license = License_Model::instance()->get( $license_key, [ 'get_by' => $get_by ] );

        // Check if license key is valid
        if( !$license ){
            return [
                'success' => false,
                'error' => __( 'Invalid license key.', 'licenser' ),
            ];
        }

        // Get domain count
        $domain_count = License_Model::instance()->get_domains( [
            'license_id' => $license->id,
            'count_total' => true,
        ] );

        // Set domain count
        $license->domain_count = $domain_count;

        return apply_filters( 'licenser_refresh_license_public_response', [
            'success' => true,
            'status' => $license->status,
            'remaining' => $license->domain_limit - $domain_count,
            'activation_limit' => $license->domain_limit,
            'expiry_days' => $license->is_lifetime ? false : number_format( ( strtotime( $license->end_date ) - time() ) / DAY_IN_SECONDS ),
            'title' => '',
        ], $license );
    }

    /**
     * Deactivate License
     */
    public function deactivate( $args ) {
       

        // $license['status']           = 'deactivate';
        // $license['remaining']        = $response['remaining'];
        // $license['activation_limit'] = $response['activation_limit'];
        // $license['expiry_days']      = $response['expiry_days'];
        // $license['title']            = $response['title'];
        // $license['source_id']        = $response['source_identifier'];
        // $license['recurring']        = $response['recurring'];

        return [
            'success' => true,
            
        ];
    }
}