<?php
namespace Licenser\Models;


class Settings {

    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Create Settings
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {
        update_option('lmfwppt_settings', $data);
    }

    /**
     * Get Settings
     * 
     * @return array
     */
    public function get( $name = '' ) {
        // Get all
        $settings = $this->get_all();

        // $settings = [
        //     'license_key' => "asdfa",
        //     'license_status' => "asdfa",
        // ];

        return isset( $settings[$name] ) ? $settings[$name] : '';
    }

    /**
     * Get Settings
     * 
     * @return array
     */
    public function get_all() {
        return get_option('lmfwppt_settings');
    }


}