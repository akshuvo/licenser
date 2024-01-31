<?php
namespace Licenser\Models;


class License {

    use \Licenser\Traits\SingletonTraitSelf;

    // Default Fields
    public $default_fields = [
        'status' => 1,
        'license_key' => '',
        'package_id' => '',
        'source' => '',
        'source_id' => '',
        'end_date' => '',
        'is_lifetime' => 0,
        'domain_limit' => '',
        'dated' => '',
    ];

    /**
     * Get Product License
     *
     * @var int
     */
    public function get( $id, $args = []) {
        $args = wp_parse_args( $args, [
            'columns' => '*',
            'get_by' => '',
        ] );

        global $lwpdb;
        $columns =  $args['columns'];

        // Where
        $where = ' 1=1 ';

        // Get By
        if( $args['get_by'] == 'key' ){
            $where .= $lwpdb->wpdb->prepare( " AND license_key = %s", $id );
        } else {
            $where .= $lwpdb->wpdb->prepare( " AND id = %d", $id );
        }

        $license = $lwpdb->wpdb->get_row( "SELECT {$columns} FROM {$lwpdb->licenses} WHERE {$where} LIMIT 1" );

        // Return if no license found
        if( empty( $license ) ){
            return false;
        }

        return $license;
    }

    /**
     * Get Product Licenses
     *
     * @var int
     */
    public function get_all( $args = [] ) {
        global $lwpdb;

        $defaults = [
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'count_total' => true,
            'product_id' => '',
            'package_id' => '',
            'source' => '',
            'source_id' => '',
            'status' => '',
            'license_key' => '',
            'end_date' => '',
            'is_lifetime' => '',
            'domain_limit' => '',
            'dated' => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = ' 1=1 ';

        if( !empty( $args['product_id'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND product_id = %d", $args['product_id'] );
        }

        if( !empty( $args['package_id'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND package_id = %s", $args['package_id'] );
        }

        if( !empty( $args['source'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND source = %s", $args['source'] );
        }

        if( !empty( $args['source_id'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND source_id = %d", $args['source_id'] );
        }

        if( !empty( $args['status'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND status = %d", $args['status'] );
        }

        if( !empty( $args['license_key'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND license_key = %s", $args['license_key'] );
        }

        if( !empty( $args['end_date'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND end_date = %s", $args['end_date'] );
        }

        if( !empty( $args['is_lifetime'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND is_lifetime = %d", $args['is_lifetime'] );
        }

        if( !empty( $args['domain_limit'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND domain_limit = %d", $args['domain_limit'] );
        }

        if( !empty( $args['dated'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND dated = %s", $args['dated'] );
        }

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        $limit = '';
        if( $args['number'] != -1 ){
            $limit = $lwpdb->wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        $licenses = $lwpdb->wpdb->get_results(
            "SELECT * FROM {$lwpdb->licenses} WHERE {$where} {$limit}"
        );

        // Return if no license found
        if( empty( $licenses ) ){
            return false;
        }

        return $licenses;
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
            'source' => '',
            'source_id' => '',
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
                    'source' => sanitize_text_field( $data['source'] ),
                    'source_id' => intval( $data['source_id'] ),
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

            // If license_key is empty then generate a new one
            if( empty( $data['license_key'] ) ){
                $data['license_key'] = $this->generate_key();
            }

            // Insert
            $lwpdb->wpdb->insert(
                $lwpdb->licenses,
                [
                    'status' => intval( $data['status'] ),
                    'license_key' => sanitize_text_field( $data['license_key'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'source' => sanitize_text_field( $data['source'] ),
                    'source_id' => intval( $data['source_id'] ),
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