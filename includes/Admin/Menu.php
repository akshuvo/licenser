<?php
namespace Licenser\Admin;

/**
 * The Menu handler class
 */
class Menu {

    private $admin_menus;

    /**
     * Initialize the class
     */
    function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register admin menu
     *
     * @return void
     */
    public function admin_menu() {
        $parent_slug = 'licenser';
        $capability = 'manage_options';

        $hook = add_menu_page(
            __( 'Licenser', 'licenser' ),
            __( 'Licenser', 'licenser' ),
            $capability,
            $parent_slug,
            [ $this, 'dashboard_page' ],
            'dashicons-tickets-alt'
        );

        $this->admin_menus = $hook;

        add_submenu_page( $parent_slug, __( 'Plugins - Licenser', 'licenser' ), __( 'Dashboard', 'licenser' ), $capability, $parent_slug, [ $this, 'dashboard_page' ] );

        add_submenu_page( $parent_slug, __( 'Plugins - Licenser', 'licenser' ), __( 'Plugins', 'licenser' ), $capability, $parent_slug.'-plugins', [ $this, 'plugins_page' ] );

        add_submenu_page( $parent_slug, __( 'Themes - Licenser', 'licenser' ), __( 'Themes', 'licenser' ), $capability, $parent_slug.'-themes', [ $this, 'themes_page' ] );

        add_submenu_page( $parent_slug, __( 'Licenser', 'licenser' ), __( 'Licenses', 'licenser' ), $capability, $parent_slug.'-licenses', [ $this, 'licenses_page' ] );

        add_submenu_page( $parent_slug, __( 'Settings', 'licenser' ), __( 'Settings', 'licenser' ), $capability, 'licenser-settings', [ $this, 'settings_page' ] );


        add_submenu_page( $parent_slug, __( 'SDK Generator', 'licenser' ), __( 'SDK Generator', 'licenser' ), $capability, 'licenser-sdk-generator', [ $this, 'sdk_generator_page' ] );
        add_submenu_page( $parent_slug, __( 'Addons', 'licenser' ), __( 'Addons', 'licenser' ), $capability, 'licenser-addons', [ $this, 'addons_page' ] );

        add_action( 'admin_head-' . $hook, [ $this, 'enqueue_assets' ] );
    }

    /**
     * Handles Dashboard pages
     *
     * @return void
     */
    public function dashboard_page() {
        $template = __DIR__ . '/templates/dashboard/dashboard.php';
        
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles Plugin pages
     *
     * @return void
     */
    public function plugins_page() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        switch ( $action ) {
            case 'edit':
            case 'new':
                $template = __DIR__ . '/templates/products/new.php';
                break;

            default:
                $template = __DIR__ . '/templates/products/list.php';
                break;
        }

        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles Theme pages
     *
     * @return void
     */
    public function themes_page() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        switch ( $action ) {
            case 'edit':
            case 'new':
                $template = __DIR__ . '/templates/products/new.php';
                break;

            default:
                $template = __DIR__ . '/templates/products/list.php';
                break;
        }

        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles Theme pages
     *
     * @return void
     */
    public function licenses_page() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $id     = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

        switch ( $action ) {
            case 'edit':
            case 'new':
                $template = __DIR__ . '/templates/licenses/new.php';
                break;

            default:
                $template = __DIR__ . '/templates/licenses/list.php';
                break;
        }

        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles the settings page
     *
     * @return void
     */
    public function settings_page() {
        $template = __DIR__ . '/templates/settings/settings.php';
        
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles the settings page
     *
     * @return void
     */
    public function sdk_generator_page() {
        $template = __DIR__ . '/templates/tools/tools.php';
        
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Handles the addons page
     *
     * @return void
     */
    public function addons_page() {
        $template = __DIR__ . '/templates/addons/addons.php';
        
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_media();
    }

}