<?php
namespace Licenser\Models;


class ProductRelease {

    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Get Var
     *
     * @var int
     */
    public function get( $id, $args = [] ) {
        $args = wp_parse_args( $args, [
            'columns' => [],
            'get_by' => '',
            'version' => '',
        ] );

        global $lwpdb;
        $columns = !empty( $args['columns'] ) ? implode( ',', $args['columns'] ) : '*';

        // Where
        $where = ' 1=1 ';

        // Get By
        if( $args['get_by'] == 'product_id' ){
            $where .= $lwpdb->wpdb->prepare( " AND product_id = %d", $id );
        } else {
            $where .= $lwpdb->wpdb->prepare( " AND id = %d", $id );
        }

        // Version
        if( !empty( $args['version'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND version = %s", $args['version'] );
        }

        // Order
        $where .= " ORDER BY id DESC, release_date DESC";

        return $lwpdb->wpdb->get_row( "SELECT {$columns} FROM {$lwpdb->product_releases} WHERE {$where} LIMIT 1" );
    }

    /**
     * Get Stable Release
     */
    public function get_stable( $product_id ) {
        global $lwpdb;

        $release = $lwpdb->wpdb->get_row(
            $lwpdb->wpdb->prepare(
                "SELECT * FROM {$lwpdb->product_releases} WHERE product_id = %d ORDER BY id DESC, release_date DESC LIMIT 1",
                $product_id
            )
        );

        return $release;
    }

    /**
     * Get Product Releases
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
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = ' 1=1 ';

        if( !empty( $args['product_id'] ) ){
            $where .= $lwpdb->wpdb->prepare( " AND product_id = %d", $args['product_id'] );
        }

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        $limit = '';
        if( !empty( $args['number'] ) ){
            $limit = $lwpdb->wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        $query = "SELECT * FROM {$lwpdb->product_releases} WHERE {$where} {$limit}";

        $items = $lwpdb->wpdb->get_results( $query );

        return $items;
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
                    'changelog' => wp_kses_post( $data['changelog'] ),
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
                    'changelog' => wp_kses_post( $data['changelog'] ),
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