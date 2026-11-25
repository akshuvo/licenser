<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Menu handler class.
 */
class Menu {

	/**
	 * Top-level admin page hook suffix.
	 *
	 * @var string
	 */
	private $admin_menus = '';

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/**
	 * Register Licenser admin menu and submenus.
	 *
	 * @return void
	 */
	public function admin_menu() {
		$parent_slug = 'licenser';
		$capability  = 'manage_options';

		$pro_tag = defined( 'LICENSER_PRO_VERSION' )
			? '<span class="menu-counter" style="background: #e27731;">' . esc_html__( 'PRO', 'licenser' ) . '</span>'
			: '';

		/* translators: %s: Optional PRO badge HTML. */
		$menu_title = trim( sprintf( __( 'Licenser %s', 'licenser' ), $pro_tag ) );

		$hook = add_menu_page(
			__( 'Licenser', 'licenser' ),
			$menu_title,
			$capability,
			$parent_slug,
			[ $this, 'dashboard_page' ],
			'dashicons-tickets-alt'
		);

		$this->admin_menus = $hook;

		add_submenu_page( $parent_slug, __( 'Dashboard - Licenser', 'licenser' ), __( 'Dashboard', 'licenser' ), $capability, $parent_slug, [ $this, 'dashboard_page' ] );
		add_submenu_page( $parent_slug, __( 'Plugins - Licenser', 'licenser' ), __( 'Plugins', 'licenser' ), $capability, $parent_slug . '-plugins', [ $this, 'plugins_page' ] );
		add_submenu_page( $parent_slug, __( 'Themes - Licenser', 'licenser' ), __( 'Themes', 'licenser' ), $capability, $parent_slug . '-themes', [ $this, 'themes_page' ] );
		add_submenu_page( $parent_slug, __( 'Licenses - Licenser', 'licenser' ), __( 'Licenses', 'licenser' ), $capability, $parent_slug . '-licenses', [ $this, 'licenses_page' ] );
		add_submenu_page( $parent_slug, __( 'Settings - Licenser', 'licenser' ), __( 'Settings', 'licenser' ), $capability, 'licenser-settings', [ $this, 'settings_page' ] );
		add_submenu_page( $parent_slug, __( 'SDK Generator - Licenser', 'licenser' ), __( 'SDK Generator', 'licenser' ), $capability, 'licenser-sdk-generator', [ $this, 'sdk_generator_page' ] );
		add_submenu_page( $parent_slug, __( 'Addons - Licenser', 'licenser' ), __( 'Addons', 'licenser' ), $capability, 'licenser-addons', [ $this, 'addons_page' ] );

		add_action( 'admin_head-' . $hook, [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Render the Dashboard page.
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
	 * Render the Plugins list/edit page.
	 *
	 * @return void
	 */
	public function plugins_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin view routing.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

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
	 * Render the Themes list/edit page.
	 *
	 * @return void
	 */
	public function themes_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin view routing.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

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
	 * Render the Licenses list/edit page.
	 *
	 * @return void
	 */
	public function licenses_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin view routing.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

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
	 * Render the Settings page.
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
	 * Render the SDK Generator page.
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
	 * Render the Addons page.
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
	 * Enqueue dashboard page media assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_media();
	}
}
