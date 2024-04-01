<?php
namespace Licenser\Models;


class Product {

    use \Licenser\Traits\SingletonTraitSelf;

    // Product Default Fields
    public $default_fields = [
        'name' => '',
        'uuid' => '',
        'slug' => '',
        'product_type' => '',
        'tested' => '',
        'requires' => '',
        'requires_php' => '',
        'banners' => [
            'low' => '',
            'high' => '',
        ],
        'description' => '',
        'author_name' => '',
        'homepage_url' => '',
        'demo_url' => '',
        'icon_url' => '',
        'created_by' => '',
        'status' => 'active',
    ];

    /**
     * Get Product Types
     */
    public function get_types() {
        return apply_filters( 'licenser_product_types', [
            'plugin' => __( 'Plugin', 'licenser' ),
            'theme' => __( 'Theme', 'licenser' ),
        ] );
    }

    /**
     * Get Product
     *
     * @var int
     */
    public function get( $id, $args = [] ) {
        $args = wp_parse_args( $args, [
            'inc_stable_release' => true,
            'inc_releases' => false,
            'inc_packages' => true,
            'columns' => [],
            'get_by' => '',
        ] );
        global $wpdb;
        $columns = !empty( $args['columns'] ) && is_array( $args['columns'] ) ? implode( ',', $args['columns'] ) : '*';

        // Where
        $where = ' 1=1 ';

        // Get By
        if( $args['get_by'] == 'uuid' ){
            $where .= $wpdb->prepare( " AND uuid = %s", $id );
        } else {
            $where .= $wpdb->prepare( " AND id = %d", $id );
        }

        $product = $wpdb->get_row( "SELECT {$columns} FROM {$wpdb->licenser_products} WHERE {$where} LIMIT 1" );

        // Return if no product found
        if( empty( $product ) ){
            return false;
        }

        // Banners
        if( !empty( $product->banners ) ){
            $product->banners = json_decode( $product->banners, true );
        }

        // Stable Release
        if( $args['inc_stable_release'] ){
            $product->stable_release = ProductRelease::instance()->get_stable( $product->id );
        }

        // Releases
        if( $args['inc_releases'] ){
            $product->releases = ProductRelease::instance()->get_all([
                'product_id' => $product->id,
            ]);
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
        global $wpdb;

        $defaults = [
            'number' => 20,
            'offset' => 0,
            'orderby' => 'id',
            'order' => 'DESC',
            'count_total' => true,
            'name' => '',
            'slug' => '',
            'uuid' => '',
            'product_type' => '',
            'status' => 'active',
            'inc_packages' => false,
            'columns' => '*',
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = ' 1=1 ';

        if( !empty( $args['name'] ) ){
            $where .= $wpdb->prepare( " AND name = %s", $args['name'] );
        }

        if( !empty( $args['slug'] ) ){
            $where .= $wpdb->prepare( " AND slug = %s", $args['slug'] );
        }

        if( !empty( $args['uuid'] ) ){
            $where .= $wpdb->prepare( " AND uuid = %s", $args['uuid'] );
        }

        if( !empty( $args['product_type'] ) ){
            $where .= $wpdb->prepare( " AND product_type = %s", $args['product_type'] );
        }

        if( !empty( $args['status'] ) ){
            $where .= $wpdb->prepare( " AND status = %s", $args['status'] );
        }

        // Order
        $where .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Limit
        if( $args['number'] != -1 ){
            $where .= $wpdb->prepare( " LIMIT %d, %d", $args['offset'], $args['number'] );
        }

        // Columns
        $columns = sanitize_text_field( $args['columns'] );

        $items = $wpdb->get_results( "SELECT {$columns} FROM {$wpdb->licenser_products} WHERE {$where}" );

        // Return if no product found
        if( empty( $items ) ){
            return false;
        }

        // Include Packages
        if( $args['inc_packages'] ){
            foreach( $items as $item ){
                $item->packages = isset( $item->id ) ? LicensePackage::instance()->get_all([
                    'product_id' => $item->id,
                ]) : [];
            }
        }

        return $items;
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
            'icon_url' => '',
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
            $data['banners'] = wp_json_encode( $data['banners'] );
        }        

        global $wpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $wpdb->uplicenser_date(
                $wpdb->licenser_products,
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
                    'icon_url' => sanitize_text_field( $data['icon_url'] ),
                    'created_by' => sanitize_text_field( $data['created_by'] ),
                    'status' => sanitize_text_field( $data['status'] ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $wpdb->insert(
                $wpdb->licenser_products,
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
                    'icon_url' => esc_url_raw( $data['icon_url'] ),
                    'created_by' => get_current_user_id(),
                    'status' => sanitize_text_field( $data['status'] ),
                ] 
            );

            $insert_id = $wpdb->insert_id;
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

    /**
     * Delete Product
     * 
     * @param int $id
     * @return bool
     */
    public function delete( $id ) {
        global $wpdb;

        $wpdb->delete(
            $wpdb->licenser_products,
            [
                'id' => $id
            ]
        );

        return true;
    }

    /**
     * Update Status
     * 
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status( $id, $status ) {
        if( !in_array( $status, [ 'active', 'trash', 'draft' ] ) ){
            return false;
        }

        global $wpdb;

        $wpdb->uplicenser_date(
            $wpdb->licenser_products,
            [
                'status' => $status
            ],
            [
                'id' => $id
            ]
        );

        return true;
    }
}