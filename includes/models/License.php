<?php
namespace Licenser\Models;


class License {

    use \Licenser\Traits\SingletonTraitSelf;

    // Default Fields
    public $default_fields = [
        'status' => '',
        'license_key' => '',
        'package_id' => '',
        'order_id' => '',
        'end_date' => '',
        'is_lifetime' => '',
        'domain_limit' => '',
        'source' => '',
        'source_id' => '',
    ];

    /**
     * Get Product License
     *
     * @var int
     */
    public function get( $id ) {
        global $lwpdb;

        $license = $lwpdb->wpdb->get_row(
            $lwpdb->wpdb->prepare(
                "SELECT * FROM {$lwpdb->licenses} WHERE id = %d",
                $id
            )
        );

        // Return if no license found
        if( empty( $license ) ){
            return false;
        }

        return $license;
    }

    /**
     * Create License
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {

        $data = wp_parse_args( $data, [
            'status' => 1,
            'license_key' => '',
            'package_id' => '',
            'order_id' => '',
            'end_date' => '',
            'is_lifetime' => 0,
            'domain_limit' => '',
            'dated' => date('Y-m-d H:i:s'),
        ] );

        global $lwpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->licenses,
                [
                    'status' => intval( $data['status'] ),
                    'license_key' => sanitize_text_field( $data['license_key'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'order_id' => intval( $data['order_id'] ),
                    'end_date' => date( 'Y-m-d H:i:s', strtotime( $data['end_date'] ) ),
                    'is_lifetime' => intval( $data['is_lifetime'] ),
                    'domain_limit' => intval( $data['domain_limit'] )
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $lwpdb->wpdb->insert(
                $lwpdb->licenses,
                [
                    'status' => intval( $data['status'] ),
                    'license_key' => sanitize_text_field( $data['license_key'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'order_id' => intval( $data['order_id'] ),
                    'end_date' => date( 'Y-m-d H:i:s', strtotime( $data['end_date'] ) ),
                    'is_lifetime' => intval( $data['is_lifetime'] ),
                    'domain_limit' => intval( $data['domain_limit'] ),
                    'dated' => sanitize_text_field( $data['dated'] ),
                ] 
            );

            $insert_id = $lwpdb->wpdb->insert_id;
        }


        return $insert_id;
    }

    /**
     * Generate License Key
     * 
     * @return array
     */
    public function generate_key() {
        $method = licenser_get_option('license_generate_method');
        $key = '';
    
        if ($method == 'wp_generate') {
            $limit = licenser_get_option('license_code_character_limit');
            $key = wp_generate_password($limit, false, false);
        } else {
            $key = md5(microtime() . rand());
        }
    
        $prefix = licenser_get_option('license_code_prefix');
    
        return $prefix . $key;
    }
    

    /**
     * Delete Product License Package
     * 
     * @param int $id
     * @return int
     */
    public function delete( $id ) {
        global $lwpdb;

        $lwpdb->wpdb->delete(
            $lwpdb->licenses,
            [
                'id' => $id
            ]
        );

        return true;
    }
}