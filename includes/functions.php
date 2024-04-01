<?php
/**
 * Licenser Table
 */
function licenser_table( $table ) {
    global $wpdb;
    return $wpdb->prefix . 'licenser_' . $table;
}

/**
 * gmdate
 *
 * @return Array
 */
function licenser_date( $format, $timestamp = null ) {
    if ( is_null( $timestamp ) ) {
        $timestamp = current_time( 'timestamp' );
    }

    return gmdate( $format, $timestamp );
}

/**
 * Get Settings
 */
function licenser_get_option( $name = null ){
    
    if ( !$name ) {
        return;
    }

    $settings = get_option( 'lmfwppt_settings' );
    return isset( $settings[$name] ) ? $settings[$name] : "";
}

// API URL
function licenser_api_url(){
    // return 'https://c0c7-103-106-239-17.ngrok-free.app/';
    return apply_filters( 'licenser_api_url', home_url('/') );
}

// Download URL
function licenser_product_download_url( $product_uuid, $license_key = '' ) {
    return add_query_arg( [
        'license_key' => $license_key,
    ], licenser_api_url() . 'wp-json/licenser/v1/public/products/download/' . $product_uuid );
}


/**
 * Parse file path and see if its remote or local.
 *
 * @param  string $file_path File path.
 * @return array
 */
function licenser_parse_file_path( $file_path ) {
    $wp_uploads     = wp_upload_dir();
    $wp_uploads_dir = $wp_uploads['basedir'];
    $wp_uploads_url = $wp_uploads['baseurl'];

    /**
     * Replace uploads dir, site url etc with absolute counterparts if we can.
     * Note the str_replace on site_url is on purpose, so if https is forced
     * via filters we can still do the string replacement on a HTTP file.
     */
    $replacements = array(
        $wp_uploads_url                                                   => $wp_uploads_dir,
        network_site_url( '/', 'https' )                                  => ABSPATH,
        str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
        site_url( '/', 'https' )                                          => ABSPATH,
        str_replace( 'https:', 'http:', site_url( '/', 'http' ) )         => ABSPATH,
    );

    $count            = 0;
    $file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );
    $parsed_file_path = wp_parse_url( $file_path );
    $remote_file      = null === $count || 0 === $count; // Remote file only if there were no replacements.

    // Paths that begin with '//' are always remote URLs.
    if ( '//' === substr( $file_path, 0, 2 ) ) {
        return array(
            'remote_file' => true,
            'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
        );
    }

    // See if path needs an abspath prepended to work.
    if ( file_exists( ABSPATH . $file_path ) ) {
        $remote_file = false;
        $file_path   = ABSPATH . $file_path;

    } elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
        $remote_file = false;
        $file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

        // Check if we have an absolute path.
    } elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) ) {
        $remote_file = false;
        $file_path   = $parsed_file_path['path'];
    }

    return array(
        'remote_file' => $remote_file,
        'file_path'   => $file_path,
    );
}

// Media Frame State Saving Ajax
add_action('wp_ajax_licenser_media_frame_state', 'licenser_media_frame_state_action');
function licenser_media_frame_state_action(){
    $state = isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '';
    update_option( 'licenser_media_frame_state', $state );
    wp_send_json_success($state);
}

// Change Upload Directory for License Manager
add_filter( 'wp_handle_upload_prefilter', 'licenser_license_manager_pre_upload' );
function licenser_license_manager_pre_upload( $file ) {
    add_filter('upload_dir', 'licenser_license_manager_uploads_dir');
    return $file;
}

function licenser_license_manager_uploads_dir( $param ){

    if ( get_option('licenser_media_frame_state') == 'open' ) {
        $mydir = '/license-manager';

        $param['path'] = $param['basedir'] . $mydir;
        $param['url'] = $param['baseurl'] . $mydir;
    }
    
    return $param;
}

// Get Host/Path from URL
function licenser_get_clean_url( $url ) {
    $parsed_url = wp_parse_url( $url );
    $host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
    $path       = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';

    $clean_url = $host . $path;

    // Remove www.
    $clean_url = preg_replace( '/^www\./', '', $clean_url );

    // Remove trailing slash.
    $clean_url = untrailingslashit( $clean_url );

    return $clean_url;
}