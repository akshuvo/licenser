<?php
namespace Licenser\Controllers;
use Licenser\Controllers\License as License_Controller;
use Licenser\Models\Product as Product_Model;
use Licenser\Models\License as License_Model;
use Licenser\Models\License_Meta;
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
            'ref_domain' => '',
        ] );
        
        // Check license key
        if( !empty( $args['license_key'] ) ){
            $license = License_Controller::instance()->check( $args['license_key'] );
            if( is_wp_error( $license ) ){
                wp_die( esc_html( $license->get_error_message() ) );
            }
        }

        // Get product download url
        $product = ProductRelease::instance()->get( $args['product_id'], [
            'columns' => [ 'download_link', 'version' ],
            'version' => $args['version'],
            'get_by' => 'product_id',
        ] );

        // Return if no product found
        if( empty( $product ) ){
            wp_die( esc_html__( 'No product found.', 'licenser' ) );
        }

        // TODO: Check if the license can download the latest version
        // > if not, get the latest version that the license can download

        // Domain ID
        $domain_id = isset( $license->id ) && $license->id ? License_Model::instance()->domain_exists( $args['ref_domain'], $license->id ) : '';

        // Add version to meta
        if( !empty( $domain_id ) ){
            $add_meta = License_Meta::instance()->uplicenser_date( $license->id, 'installed_version_' . $domain_id, $product->version );
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

        wp_die( esc_html__( 'No file found.', 'licenser' ) );
    }
}