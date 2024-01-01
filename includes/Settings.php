<?php 
namespace Licenser;

class Settings{
	
	/**
     * Initialize the class
     */

	function __construct(){
		// Ajax Add data options table 
		add_action( 'wp_ajax_setting_add_form', [ $this, 'setting_add' ] );
	}

	// Setting add form action
    function setting_add(){
        if ( isset( $_POST['lmaction'] ) && $_POST['lmaction'] == "setting_add_form" ) {
           $lmfwppt_settings = isset( $_POST['lmfwppt_settings'] ) ? $_POST['lmfwppt_settings'] : array();
           update_option( 'lmfwppt_settings', $lmfwppt_settings );
        }
        die();
    }

}