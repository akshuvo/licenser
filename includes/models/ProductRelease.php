<?php
namespace Licenser\Models;


class ProductRelease {

    use \Licenser\Traits\SingletonTraitSelf;

    public function __construct() {
     

        error_log( ' Release Model' );
    }

    /**
     * Create Product Release
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {

        $data = wp_parse_args( $data, [
            'product_id' => '',
            'version' => '',
            'changelog' => '',
            'file_name' => '',
            'download_link' => '',
            'release_date' => '',
        ] );

        global $lwpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->product_releases,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'version' => sanitize_text_field( $data['version'] ),
                    'changelog' => sanitize_text_field( $data['changelog'] ),
                    'file_name' => sanitize_text_field( $data['file_name'] ),
                    'download_link' => esc_url_raw( $data['download_link'] ),
                    'release_date' => date( 'Y-m-d H:i:s', strtotime( $data['release_date'] ) ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $lwpdb->wpdb->insert(
                $lwpdb->product_releases,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'version' => sanitize_text_field( $data['version'] ),
                    'changelog' => sanitize_text_field( $data['changelog'] ),
                    'file_name' => sanitize_text_field( $data['file_name'] ),
                    'download_link' => esc_url_raw( $data['download_link'] ),
                    'release_date' => date( 'Y-m-d H:i:s', strtotime( $data['release_date'] ) ),
                ] 
            );

            $insert_id = $lwpdb->wpdb->insert_id;
        }


        return $insert_id;
    }
}