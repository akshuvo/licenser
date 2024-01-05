<?php
namespace Licenser\Models;


class LicensePackage {

    use \Licenser\Traits\SingletonTraitSelf;

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