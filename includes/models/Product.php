<?php
namespace Licenser\Models;


class Product {

    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Get Product
     *
     * @var int
     */
    public function get( $id, $args = [] ) {
        $args = wp_parse_args( $args, [
            'status' => 'active',
            'inc_releases' => true,
            'inc_packages' => true,
        ] );
        global $lwpdb;

        $product = $lwpdb->wpdb->get_row(
            $lwpdb->wpdb->prepare(
                "SELECT * FROM {$lwpdb->products} WHERE id = %d",
                $id
            )
        );

        // Return if no product found
        if( empty( $product ) ){
            return false;
        }

        // Banners
        if( !empty( $product->banners ) ){
            $product->banners = json_decode( $product->banners );
        }

        // Packages
        if( $args['inc_packages'] ){
            $product->packages = LicensePackage::instance()->get_all([
                'product_id' => $product->id,
            ]);
        }

        return $product;
    }

    /**
     * Get Products
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
            'status' => 'active',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = '';

        if( !empty( $args['status'] ) ){
            $where .= " AND status = '{$args['status']}'";
        }

        $query = "SELECT * FROM {$lwpdb->products} WHERE 1=1 {$where}";

        if( !empty( $args['orderby'] ) && !empty( $args['order'] ) ){
            $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        }

        if( !empty( $args['number'] ) && !empty( $args['offset'] ) ){
            $query .= " LIMIT {$args['offset']}, {$args['number']}";
        }

        error_log( $query );

        $products = $lwpdb->wpdb->get_results( $query );

        return $products;
    }

    /**
     * Create Product
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {
        $data = wp_parse_args( $data, [
            'name' => '',
            'slug' => '',
            'product_type' => '',
            'tested' => '',
            'requires' => '',
            'requires_php' => '',
            'banners' => [],
            'description' => '',
            'author_name' => '',
            'homepage_url' => '',
            'demo_url' => '',
            'created_by' => '',
            'status' => 'active',

            'version' => '',
            'changelog' => '',
            'file_name' => '',
            'download_link' => '',
            'release_date' => '',

            'license_packages' => [],
        ] );

        // Banner
        if( !empty( $data['banners'] ) && is_array( $data['banners'] ) ){
            $data['banners'] = json_encode( $data['banners'] );
        }

        error_log( print_r( $data, true ) );
        

        global $lwpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->products,
                [
                    'name' => sanitize_text_field( $data['name'] ),
                    'slug' => sanitize_text_field( $data['slug'] ),
                    'product_type' => sanitize_text_field( $data['product_type'] ),
                    'tested' => sanitize_text_field( $data['tested'] ),
                    'requires' => sanitize_text_field( $data['requires'] ),
                    'requires_php' => sanitize_text_field( $data['requires_php'] ),
                    'banners' => sanitize_text_field( $data['banners'] ),
                    'description' => sanitize_text_field( $data['description'] ),
                    'author_name' => sanitize_text_field( $data['author_name'] ),
                    'homepage_url' => sanitize_text_field( $data['homepage_url'] ),
                    'demo_url' => sanitize_text_field( $data['demo_url'] ),
                    'created_by' => sanitize_text_field( $data['created_by'] ),
                    'status' => sanitize_text_field( $data['status'] ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $lwpdb->wpdb->insert(
                $lwpdb->products,
                [
                    'name' => sanitize_text_field( $data['name'] ),
                    'slug' => sanitize_text_field( $data['slug'] ),
                    'uuid' => wp_generate_uuid4(),
                    'product_type' => sanitize_text_field( $data['product_type'] ),
                    'tested' => sanitize_text_field( $data['tested'] ),
                    'requires' => sanitize_text_field( $data['requires'] ),
                    'requires_php' => sanitize_text_field( $data['requires_php'] ),
                    'banners' => sanitize_text_field( $data['banners'] ),
                    'description' => sanitize_text_field( $data['description'] ),
                    'author_name' => sanitize_text_field( $data['author_name'] ),
                    'homepage_url' => esc_url_raw( $data['homepage_url'] ),
                    'demo_url' => esc_url_raw( $data['demo_url'] ),
                    'created_by' => get_current_user_id(),
                    'status' => sanitize_text_field( $data['status'] ),
                ] 
            );

            $insert_id = $lwpdb->wpdb->insert_id;
        }

        // Product Release
        if( !empty( $data['version'] ) && !empty( $data['download_link'] ) && !empty( $data['release_date'] ) ){
            $product_release = ProductRelease::instance()->create([
                'product_id' => $insert_id,
                'version' => $data['version'],
                'changelog' => $data['changelog'],
                'file_name' => $data['file_name'],
                'download_link' => $data['download_link'],
                'release_date' => $data['release_date'],
            ]);
        }

        // License Package
        if( !empty( $data['license_packages'] ) && is_array( $data['license_packages'] ) ){
            foreach( $data['license_packages'] as $package ){
                error_log( print_r( $package, true ) );
                $license_package = LicensePackage::instance()->create([
                    'id' => isset( $package['id'] ) ? $package['id'] : '',
                    'product_id' => $insert_id,
                    'package_id' => isset( $package['package_id'] ) ? $package['package_id'] : wp_generate_uuid4(),
                    'label' => $package['label'],
                    'update_period' => $package['update_period'],
                    'domain_limit' => $package['domain_limit'],
                ]);
            }
        }

        return $insert_id;
    }
}