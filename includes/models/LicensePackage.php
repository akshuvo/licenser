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
        global $lwpdb;

        $package = $lwpdb->wpdb->get_row(
            $lwpdb->wpdb->prepare(
                "SELECT * FROM {$lwpdb->license_packages} WHERE id = %d",
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
        global $lwpdb;

        $defaults = [
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'count_total' => true,
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = '';

        if( !empty( $args['product_id'] ) ){
            $where .= " AND product_id = '{$args['product_id']}'";
        }

        if( !empty( $args['package_id'] ) ){
            $where .= " AND package_id = '{$args['package_id']}'";
        }

        if( !empty( $args['label'] ) ){
            $where .= " AND label = '{$args['label']}'";
        }

        if( !empty( $args['update_period'] ) ){
            $where .= " AND update_period = '{$args['update_period']}'";
        }

        if( !empty( $args['domain_limit'] ) ){
            $where .= " AND domain_limit = '{$args['domain_limit']}'";
        }

        $order_by = $args['orderby'] . ' ' . $args['order'];

        $limit = '';
        if( !empty( $args['number'] ) ){
            $limit = "LIMIT {$args['number']}";
        }

        if( !empty( $args['offset'] ) ){
            $limit .= " OFFSET {$args['offset']}";
        }

        $packages = $lwpdb->wpdb->get_results(
            "SELECT * FROM {$lwpdb->license_packages} WHERE 1=1 {$where} ORDER BY {$order_by} {$limit}"
        );

        return $packages;

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

        global $lwpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->license_packages,
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
            $lwpdb->wpdb->insert(
                $lwpdb->license_packages,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'package_id' => sanitize_text_field( $data['package_id'] ),
                    'label' => sanitize_text_field( $data['label'] ),
                    'update_period' => intval( $data['update_period'] ),
                    'domain_limit' => intval( $data['domain_limit'] ),
                ] 
            );

            $insert_id = $lwpdb->wpdb->insert_id;
        }


        return $insert_id;
    }
}