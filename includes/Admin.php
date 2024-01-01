<?php
namespace Licenser;

/**
 * Admin class
 */
class Admin {
    /**
     * Initialize the class
     */
    function __construct() {
        // Menu Init
        $menu = new Admin\Menu();

		// Added plugin action link
		add_filter( 'plugin_action_links_' . plugin_basename( LICENSER_FILE ), [ $this, 'action_links' ] );

        // Enqueue admin scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

	/**
	 * Adds plugin action links.
	 */
	function action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=licenser' ) . '">' . esc_html__( 'License Manager', 'lmfwppt' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {

        // Get current screen
        $screen = get_current_screen();

        // Check if current screen is plugin settings page
        if ( $screen->id != 'toplevel_page_licenser' ) {
            // return;
        }

        $ver = current_time( 'timestamp' );
	    
	    wp_enqueue_media();

	    // Load the datepicker script (pre-registered in WordPress).
	    wp_enqueue_script( 'jquery-ui-datepicker' );

	    // jQuery UI CSS on a CDN.
	    wp_register_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
	    wp_enqueue_style( 'jquery-ui' );  

        wp_enqueue_style( 'lmfwppt-admin-style', LICENSER_ASSETS . '/css/admin.css', array(), LICENSER_VERSION );
        wp_enqueue_script( 'lmfwppt-admin-script', LICENSER_ASSETS . '/js/admin.js', array( 'jquery' ), LICENSER_VERSION, true );
    }
}