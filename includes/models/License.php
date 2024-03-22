<?php
namespace Licenser\Models;


class License {

    use \Licenser\Traits\SingletonTraitSelf;

    // Default Fields
    public $default_fields = [
        'status' => 1,
        'license_key' => '',
        'product_id' => '',
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
            'inc_domains' => false,
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
            if( is_array( $args['source_id'] ) ) {
                $source_ids = esc_sql( $args['source_id'] );
                $source_ids = implode( ',', $source_ids );
                $where .= " AND source_id IN ({$source_ids})";
            } else {
                $where .= $lwpdb->wpdb->prepare( " AND source_id = %d", $args['source_id'] );
            }
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

        // Include Domains
        if( $args['inc_domains'] ){
            foreach( $licenses as $key => $license ){
                $licenses[$key]->domains = isset( $license->id ) ? $this->get_domains([
                    'license_id' => $license->id,
                ]) : [];
            }
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
            'product_id' => '',
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
                    'product_id' => sanitize_text_field( $data['product_id'] ),
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
                    'product_id' => sanitize_text_field( $data['product_id'] ),
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
    
        if ( $method == 'wp_generate' ) {
            $limit = licenser_get_option('license_code_character_limit');
            $key = wp_generate_password($limit, false, false);
        } elseif ($method == 'uuid') {
            $key = wp_generate_uuid4();
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

    /**
     * Get License Domains
     * 
     * @param int $license_id
     * @return array
     */
    public function get_domains( $args = [] ) {
        $args = wp_parse_args( $args, [
            'license_id' => '',
            'columns' => [],
            'count_total' => false,
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
        ] );
        
        global $lwpdb;

        // Columns
        $columns = !empty( $args['columns'] ) ? implode( ',', $args['columns'] ) : '*';

        $where = ' 1=1 ';

        // License ID
        if( !empty( $args['license_id'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND license_id = %d", $args['license_id'] );
        }

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        $limit = '';
        if( $args['number'] != -1 ){
            $limit = $lwpdb->wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        // Count Total
        if( $args['count_total'] ) {
            return $lwpdb->wpdb->get_var( "SELECT COUNT(*) FROM {$lwpdb->license_domains} WHERE {$where}" );
        } 
        
        return $lwpdb->wpdb->get_results(
            "SELECT {$columns} FROM {$lwpdb->license_domains} WHERE {$where} {$limit}"
        );
    }
    /**
     * Delete License Domains
     * 
     * @param int $license_id
     * @return int
     */
    public function delete_domains( $license_id ) {
        global $lwpdb;

        $lwpdb->wpdb->delete(
            $lwpdb->license_domains,
            [
                'license_id' => $license_id
            ]
        );

        return true;
    }

    /**
     * Delete License Domain
     * 
     * @param int $id
     * @return int
     */
    public function delete_domain( $id ) {
        global $lwpdb;

        $lwpdb->wpdb->delete(
            $lwpdb->license_domains,
            [
                'id' => $id
            ]
        );

        return true;
    }

    /**
     * Domain Exists
     * 
     * @param int $license_id
     * @param string $domain
     * @return bool
     */
    public function domain_exists( $domain, $license_id = '' ) {
        global $lwpdb;

        $domain = lmfwppt_get_clean_url( $domain );

        $where = $lwpdb->wpdb->prepare( "domain = %s", $domain );

        if( !empty( $license_id ) ){
            $where .= $lwpdb->wpdb->prepare( " AND license_id = %d", $license_id );
        }

        return $lwpdb->wpdb->get_var( "SELECT id FROM {$lwpdb->license_domains} WHERE {$where} LIMIT 1" );
    }


    /**
     * Add Domain
     * 
     * @param array $args
     * @return int
     */
    public function add_domain( $args ) {
        $args = wp_parse_args( $args, [
            'id' => '',
            'license_id' => '',
            'domain' => '',
            'status' => ''
        ] );

        global $lwpdb;

        // Update
        if( !empty( $args['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->license_domains,
                [
                    'license_id' => intval( $args['license_id'] ),
                    'domain' => lmfwppt_get_clean_url( $args['domain'] ),
                    'status' => intval( $args['status'] ),
                ],
                [
                    'id' => $args['id']
                ]
            );

            return $args['id'];
        }

        // Insert
        $lwpdb->wpdb->insert(
            $lwpdb->license_domains,
            [
                'license_id' => intval( $args['license_id'] ),
                'domain' => lmfwppt_get_clean_url( $args['domain'] ),
                'status' => intval( $args['status'] ),
            ],
            [
                '%d',
                '%s',
                '%d',
            ]
        );

        return $lwpdb->wpdb->insert_id;
    }


}