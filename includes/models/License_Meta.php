<?php
namespace Licenser\Models;


class License_Meta {

    use \Licenser\Traits\SingletonTraitSelf;

    // Default Fields
    public $default_fields = [
        'license_id' => '',
        'meta_key' => '',
        'meta_value' => '',
    ];

    /**
     * Get Meta Value
     *
     * @param int $license_id
     * @param string $meta_key
     * @return array
     */
    public function get_value( $license_id, $meta_key = '' ) {
        global $wpdb;

        $where = $wpdb->prepare( " WHERE license_id = %d", $license_id );

        $where .= $wpdb->prepare( " AND meta_key = %s", $meta_key );
       
        return $wpdb->get_var( "SELECT meta_value FROM {$lwpdb->license_meta} {$where}" );
    }

    /**
     * Update Meta (Add if not exists)
     *
     * @param int $license_id
     * @param array $data
     * @return int
     */
    public function update( $license_id, $meta_key, $meta_value ) {
        global $wpdb;

        // Check if exists
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lwpdb->license_meta} WHERE license_id = %d AND meta_key = %s", $license_id, $meta_key ) );

        if( $exists ){
            return $wpdb->update( $lwpdb->license_meta, [ 'meta_value' => $meta_value ], [ 'license_id' => $license_id, 'meta_key' => $meta_key ] );
        } else {
            return $wpdb->insert( $lwpdb->license_meta, [ 'license_id' => $license_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value ] );
        }
    }

    /**
     * Delete Meta
     *
     * @param int $license_id
     * @param string $meta_key
     * @return int
     */
    public function delete( $license_id, $meta_key ) {
        global $wpdb;

        return $wpdb->delete( $lwpdb->license_meta, [ 'license_id' => $license_id, 'meta_key' => $meta_key ] );
    }

    /**
     * Delete All Meta
     *
     * @param int $license_id
     * @return int
     */
    public function delete_all( $license_id ) {
        global $wpdb;

        return $wpdb->delete( $lwpdb->license_meta, [ 'license_id' => $license_id ] );
    }

}