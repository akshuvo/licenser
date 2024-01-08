<?php
namespace Licenser\Models;


class Settings {

    use \Licenser\Traits\SingletonTraitSelf;

    /**
     * Create Product Release
     * 
     * @param array $data
     * @return int
     */
    public function create( $data ) {

        $data = wp_parse_args( $data, [
            'license_code_prefix' => '',
            'license_generate_method' => '',
            'license_code_character_limit' => ''
        ] );

        global $lwpdb;

        // Update
        if( isset( $data['id'] ) && !empty( $data['id'] ) ){
            $lwpdb->wpdb->update(
                $lwpdb->lmfwppt_settings,
                [
                    'license_code_prefix' => sanitize_text_field( $data['license_code_prefix'] ),
                    'license_generate_method' => sanitize_text_field( $data['license_generate_method'] ),
                    'license_code_character_limit' => sanitize_text_field( $data['license_code_character_limit'] ),
                ],
                [
                    'id' => $data['id']
                ]
            );

            $insert_id = $data['id'];
        } else {
            $lwpdb->wpdb->insert(
                $lwpdb->lmfwppt_settings,
                [
                    'license_code_prefix' => sanitize_text_field( $data['license_code_prefix'] ),
                    'license_generate_method' => sanitize_text_field( $data['license_generate_method'] ),
                    'license_code_character_limit' => sanitize_text_field( $data['license_code_character_limit'] ),
                ] 
            );

            $insert_id = $lwpdb->wpdb->insert_id;
        }


        return $insert_id;
    }
}