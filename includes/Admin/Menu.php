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
        add_action( 'admin_bar_menu', [ $this, 'admin_bar_menus' ], 1000 );
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
            __( 'License manager for WordPress Themes and Plugins', 'lmfwppt' ),
            __( 'License manager', 'lmfwppt' ),
            $capability,
            $parent_slug,
            [ $this, 'dashboard_page' ],
            'dashicons-tickets-alt'
        );

        $this->admin_menus = $hook;

        add_submenu_page( $parent_slug, __( 'Plugins - License manager for WordPress Themes and Plugins', 'lmfwppt' ), __( 'Dashboard', 'lmfwppt' ), $capability, $parent_slug, [ $this, 'dashboard_page' ] );

        add_submenu_page( $parent_slug, __( 'Plugins - License manager for WordPress Themes and Plugins', 'lmfwppt' ), __( 'Plugins', 'lmfwppt' ), $capability, $parent_slug.'-plugins', [ $this, 'plugins_page' ] );

        add_submenu_page( $parent_slug, __( 'Themes - License manager for WordPress Themes and Plugins', 'lmfwppt' ), __( 'Themes', 'lmfwppt' ), $capability, $parent_slug.'-themes', [ $this, 'themes_page' ] );

        add_submenu_page( $parent_slug, __( 'License manager for WordPress Themes and Plugins', 'lmfwppt' ), __( 'Licenses', 'lmfwppt' ), $capability, $parent_slug.'-licenses', [ $this, 'licenses_page' ] );

        add_submenu_page( $parent_slug, __( 'Settings', 'lmfwppt' ), __( 'Settings', 'lmfwppt' ), $capability, 'licenser-settings', [ $this, 'settings_page' ] );


        add_submenu_page( $parent_slug, __( 'SDK Generator', 'lmfwppt' ), __( 'SDK Generator', 'lmfwppt' ), $capability, 'licenser-sdk-generator', [ $this, 'sdk_generator_page' ] );

        add_action( 'admin_head-' . $hook, [ $this, 'enqueue_assets' ] );
    }




    // Admin Bar Menu
    function admin_bar_menus( \WP_Admin_Bar $wp_admin_bar ) {
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ): null;

        if ( $page == "licenser" || $page == "licenser-plugins" || $page == "licenser-themes" || $page == "licenser-licenses" || $page == "licenser-settings" || $page == "licenser-sdk-generator" && $this->admin_menus == "toplevel_page_licenser"  ) {
        

            if ( !is_admin_bar_showing() )
                return;

            $parent_slug = 'licenser';

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug,
                'parent' => 'top-secondary',
                'group'  => null,
                'title' => __( 'License Manager', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser'),
            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-dashboard',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'Dashboard', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser'),
            ) );
            
            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-plugins',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'Plugins', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-plugins'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-plugins-add',
                'parent' => $parent_slug.'-plugins',
                'group'  => null,
                'title' => __( 'Add New', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-plugins&action=new'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-themes',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'Themes', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-themes'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );

            $wp_admin_bar->add_menu( array(
                'id' => $parent_slug.'-themes-add',
                'parent' => $parent_slug.'-themes',
                'group' => null,
                'title' => __( 'Add New', 'lmfwppt' ),
                'href' => admin_url('admin.php?page=licenser-themes&action=new'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ),
                ]

            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-license',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'Licenses', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-licenses'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-license-add',
                'parent' => $parent_slug.'-license',
                'group'  => null,
                'title' => __( 'Add New', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-licenses&action=new'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );

            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-setting',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'Setting', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-settings'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );
            $wp_admin_bar->add_menu( array(
                'id'    => $parent_slug.'-sdkgenerator',
                'parent' => $parent_slug,
                'group'  => null,
                'title' => __( 'SDK-Generator', 'lmfwppt' ),
                'href'  => admin_url('admin.php?page=licenser-sdk-generator'),
                'meta' => [
                    'title' => __( 'Menu Title', 'lmfwppt' ), //This title will show on hover
                ]
            ) );
        }
            
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
     * Enqueue scripts and styles
     *
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_media();
    }

}