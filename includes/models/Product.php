<?php
namespace Licenser\Models;


class Product {

    use \Licenser\Traits\SingletonTraitSelf;

    public function __construct() {
     

        error_log( 'Product Model' );
    }

    /**
     * Get Product
     *
     * @var int
     */
    public function get( $id ) {
        global $lwpdb;

        $board = $lwpdb->wpdb->get_row(
            $lwpdb->wpdb->prepare(
                "SELECT * FROM {$lwpdb->products} WHERE id = %d",
                $id
            )
        );

        return $board;
    }

    /**
     * Create Product
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {
//         id	int(11) Auto Increment	
// name	varchar(255)	
// slug	varchar(255)	
// uuid	varchar(255)	
// product_type	varchar(30) NULL	
// tested	varchar(30) NULL	
// requires	varchar(30) NULL	
// requires_php	varchar(30) NULL	
// banners	varchar(250) NULL	
// description	text NULL	
// author_name	varchar(220) NULL	
// homepage_url	varchar(220) NULL	
// demo_url	varchar(220) NULL	
// created_by	int(20) unsigned	
// status	varchar(30) NULL	
// dated	datetime [CURRENT_TIMESTAMP]	


        $data = wp_parse_args( $data, [
            'name' => '',
            'slug' => '',
            'uuid' => '',
            'product_type' => '',
            'tested' => '',
            'requires' => '',
            'requires_php' => '',
            'banners' => '',
            'description' => '',
            'author_name' => '',
            'homepage_url' => '',
            'demo_url' => '',
            'created_by' => '',
            'status' => '',
        ] );

        

        global $lwpdb;

        $lwpdb->wpdb->insert(
            $lwpdb->products,
            [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'product_type' => $data['product_type'],
                'status' => $data['status'],
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ]
        );

        return $lwpdb->wpdb->insert_id;
    }
}