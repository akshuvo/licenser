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

        global $wpdb;
        $columns = !empty( $args['columns'] ) ? implode( ',', $args['columns'] ) : '*';

        // Where
        $where = ' 1=1 ';

        // Get By
        if( $args['get_by'] == 'product_id' ){
            $where .= $wpdb->prepare( " AND product_id = %d", $id );
        } else {
            $where .= $wpdb->prepare( " AND id = %d", $id );
        }

        // Version
        if( !empty( $args['version'] ) ){
            $where .= $wpdb->prepare( " AND version = %s", $args['version'] );
        }

        // Order
        $where .= " ORDER BY id DESC, release_date DESC";

        return $wpdb->get_row( "SELECT {$columns} FROM {$wpdb->licenser_product_releases} WHERE {$where} LIMIT 1" );
    }

    /**
     * Get Stable Release
     */
    public function get_stable( $product_id ) {
        global $wpdb;

        $release = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->licenser_product_releases} WHERE product_id = %d ORDER BY id DESC, release_date DESC LIMIT 1",
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

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        $limit = '';
        if( !empty( $args['number'] ) ){
            $limit = $wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        $query = "SELECT * FROM {$wpdb->licenser_product_releases} WHERE {$where} {$limit}";

        $items = $wpdb->get_results( $query );

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

        global $wpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $wpdb->uplicenser_date(
                $wpdb->licenser_product_releases,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'version' => sanitize_text_field( $data['version'] ),
                    'changelog' => wp_kses_post( $data['changelog'] ),
                    'file_name' => sanitize_text_field( $data['file_name'] ),
                    'download_link' => esc_url_raw( $data['download_link'] ),
                    'release_date' => licenser_date( 'Y-m-d H:i:s', strtotime( $data['release_date'] ) ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $wpdb->insert(
                $wpdb->licenser_product_releases,
                [
                    'product_id' => intval( $data['product_id'] ),
                    'version' => sanitize_text_field( $data['version'] ),
                    'changelog' => wp_kses_post( $data['changelog'] ),
                    'file_name' => !empty( $data['file_name'] ) ? sanitize_text_field( $data['file_name'] ) : basename( $data['download_link'] ),
                    'download_link' => esc_url_raw( $data['download_link'] ),
                    'release_date' => licenser_date( 'Y-m-d H:i:s', strtotime( $data['release_date'] ) ),
                ] 
            );

            $insert_id = $wpdb->insert_id;
        }


        return $insert_id;
    }
}