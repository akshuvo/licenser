<?php
namespace Licenser\Controllers;
use Licenser\Controllers\License as License_Controller;
use Licenser\Models\Product as Product_Model;
use Licenser\Models\ProductRelease;
// use Licenser\Models\License;


class Product {
    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Download Product
     */
    public function download( $args ) {
        $args = wp_parse_args( $args, [
            'product_id' => '',
            'license_key' => '',
            'version' => '',
        ] );
        
        // Check license key
        if( !empty( $args['license_key'] ) ){
            $license = License_Controller::instance()->check( $args['license_key'] );
            if( is_wp_error( $license ) ){
                wp_die( $license->get_error_message() );
            }
        }

        // 'columns' => [],
        //     'get_by' => '',
        //     'version' => '',

        // Get product download url
        $product = ProductRelease::instance()->get( $args['product_id'], [
            'columns' => [ 'download_link' ],
            'version' => $args['version'],
            'get_by' => 'product_id',
        ] );

        // Return if no product found
        if( empty( $product ) ){
            wp_die( __( 'No product found.', 'licenser' ) );
        }

        // Download link
        $download_link = $product->download_link;

        // Parse file path from download link
        $parsed_file_path = licenser_parse_file_path( $download_link );

        /**
         * Fallback on force download method for remote files.
         */
        if ( $parsed_file_path['remote_file'] ) {
            header("Location: $download_link");
            exit;
        }

        // Download file name
        //$download_file_name = sanitize_text_field( $args['product_slug'].'.zip' );
        $download_file_name = basename($download_link);

        // File path
        $file_path = isset( $parsed_file_path['file_path'] ) ? sanitize_text_field( $parsed_file_path['file_path'] ) : '';
       
        // Download from own server
        if( !empty( $download_link ) && !empty( $file_path ) ) {
            //readfile($download_link);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"".$download_file_name."\"");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($file_path));
            ob_end_flush();
            @readfile($file_path);
            exit;
        }

        wp_die( __( 'No file found.', 'licenser' ) );
    }
}