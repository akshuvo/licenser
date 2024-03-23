<?php
namespace Licenser;

class Ajax {
    
    /**
     * Initialize the class
     */
    function __construct() {

        // // Settings
        // $settingsObj = new Settings();
        // // Ajax Add data options table 
        // add_action( 'wp_ajax_setting_add_form', [ $settingsObj, 'setting_add' ] );

        // // Licenses
        // $licensesObj = new Licenses();
                
        // add_action( 'wp_ajax_license_add_form', [ $licensesObj, 'license_add' ] );
        // add_action( 'wp_ajax_get_packages_option', [ $licensesObj, 'product_package' ] ); 
        // add_action( 'wp_ajax_license_key', [ $licensesObj, 'ajax_generate_license_key' ] );
        // // Add Domain field ajax
        // add_action( 'wp_ajax_lmfwppt_domain_active_field_action', [ $licensesObj, 'domain_ajax_add_action' ] );


        // // Products
        // $productsObj = new Products();

        // add_action( 'lmfwppt_license_field_after_wrap', [ $productsObj, 'license_content' ], 10, 2 );
        
        // // Add license field ajax
        // add_action( 'wp_ajax_lmfwppt_single_license_field', [ $productsObj, 'license_package_ajax_add_action' ] );

        // // Add Section field ajax
        // add_action( 'wp_ajax_lmfwppt_single_section_field', [ $productsObj, 'product_sections_ajax_add_action' ] );

        // // Product add action
        // add_action( 'wp_ajax_product_add_form', [ $productsObj, 'product_add' ] );

        // // SDK Generator
        // $sdkGeneratorObj = new SdkGenerator();
        // // Ajax Add data options table 
		// add_action( 'wp_ajax_sdk_generator_add_form', [ $sdkGeneratorObj, 'sdkgenerator_add' ] );

		add_action( 'wp_ajax_licenser_migrate_from_old_database', function(){
            $table = $_POST['table'];
            $migration = new MigrateOldDb();

            $migrate = $migration->migrate_data( $table );

            // error_log( print_r( $migrate, true ) );

            echo '<pre>';
            print_r( $migrate );
            echo '</pre>';
            die();
        });


    }

}