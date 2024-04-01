<?php
namespace Licenser\Models;


class LicensePackage {

    use \Licenser\Traits\SingletonTraitSelf;


    /**
     * Get Product License Package
     *
     * @var int
     */
    public function get( $id ) {
        global $wpdb;

        $package = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->licenser_license_packages} WHERE id = %d",
                $id
            )
        );

        // Return if no package found
        if( empty( $package ) ){
            return false;
        }

        return $package;
    }

    /**
     * Get Product License Packages
     *
     * @var int
     */
    public function get_all( $args = [] ) {
        global $wpdb;

        $defaults = [
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'count_total' => true,
            'product_id' => '',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = ' 1=1 ';

        if( !empty( $args['product_id'] ) ){
            $where .= $wpdb->prepare( " AND product_id = %d", $args['product_id'] );
        }

        if( !empty( $args['package_id'] ) ){
            $where .= $wpdb->prepare( " AND package_id = %s", $args['package_id'] );
        }

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Limit
        if( $args['number'] != -1 ){
            $where .= $wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        $packages = $wpdb->get_results( "SELECT * FROM {$wpdb->licenser_license_packages} WHERE {$where}" );

        // Return if no package found
        if( empty( $packages ) ){
            return false;
        }

        return $packages;
    }

    /**
     * Create Product License Package
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {

        $data = wp_parse_args( $data, [
            'product_id' => '',
            'package_id' => '',
            'label' => '',
            'update_period' => '',
            'domain_limit' => '',
        ] );

        global $wpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $wpdb->uplicenser_date(
                $wpdb->licenser_license_packages,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'label' => sanitize_text_field( $data['label'] ),
                    'update_period' => intval( $data['update_period'] ),
                    'domain_limit' => intval( $data['domain_limit'] ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $wpdb->insert(
                $wpdb->licenser_license_packages,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'label' => sanitize_text_field( $data['label'] ),
                    'update_period' => intval( $data['update_period'] ),
                    'domain_limit' => intval( $data['domain_limit'] ),
                ] 
            );

            $insert_id = $wpdb->insert_id;
        }


        return $insert_id;
    }

    /**
     * Delete Product License Package
     * 
     * @param int $id
     * @return int
     */
    public function delete( $id ) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->licenser_license_packages,
            [
                'id' => $id
            ]
        );

        return true;
    }
}