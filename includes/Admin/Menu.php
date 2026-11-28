<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\Product;

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
		$capability  = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'dashboard' );
		$prod_cap    = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'products' );
		$lic_cap     = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'licenses' );
		$set_cap     = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'settings' );
		$sdk_cap     = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'sdk' );
		$addons_cap  = (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'addons' );

		$pro_tag = defined( 'LICENSER_PRO_VERSION' )
			? '<span class="update-plugins licenser-menu-badge"><span class="plugin-count">' . esc_html__( 'PRO', 'licenser' ) . '</span></span>'
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
		$plugins_hook  = add_submenu_page( $parent_slug, __( 'Plugins - Licenser', 'licenser' ), __( 'Plugins', 'licenser' ), $prod_cap, $parent_slug . '-plugins', [ $this, 'plugins_page' ] );
		$themes_hook   = add_submenu_page( $parent_slug, __( 'Themes - Licenser', 'licenser' ), __( 'Themes', 'licenser' ), $prod_cap, $parent_slug . '-themes', [ $this, 'themes_page' ] );
		$licenses_hook = add_submenu_page( $parent_slug, __( 'Licenses - Licenser', 'licenser' ), __( 'Licenses', 'licenser' ), $lic_cap, $parent_slug . '-licenses', [ $this, 'licenses_page' ] );
		add_submenu_page( $parent_slug, __( 'Settings - Licenser', 'licenser' ), __( 'Settings', 'licenser' ), $set_cap, 'licenser-settings', [ $this, 'settings_page' ] );
		add_submenu_page( $parent_slug, __( 'SDK Studio - Licenser', 'licenser' ), __( 'SDK Studio', 'licenser' ), $sdk_cap, 'licenser-sdk-generator', [ $this, 'sdk_generator_page' ] );
		add_submenu_page( $parent_slug, __( 'Addons - Licenser', 'licenser' ), __( 'Addons', 'licenser' ), $addons_cap, 'licenser-addons', [ $this, 'addons_page' ] );

		add_action( 'admin_head-' . $hook, [ $this, 'enqueue_assets' ] );

		if ( $plugins_hook ) {
			add_action( 'load-' . $plugins_hook, [ $this, 'handle_products_bulk_actions' ] );
		}
		if ( $themes_hook ) {
			add_action( 'load-' . $themes_hook, [ $this, 'handle_products_bulk_actions' ] );
		}
		if ( $licenses_hook ) {
			add_action( 'load-' . $licenses_hook, [ $this, 'handle_licenses_bulk_actions' ] );
		}

		/**
		 * Fires after Licenser core admin submenus have been registered.
		 *
		 * @param string $parent_slug Parent menu slug (`licenser`).
		 * @param string $capability  Required capability (`manage_options`).
		 */
		do_action( 'licenser_admin_menu', $parent_slug, $capability );
	}

	/**
	 * Handle nonce-verified bulk actions for Products list screens prior to output.
	 *
	 * @return void
	 */
	public function handle_products_bulk_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action  = isset( $_POST['action'] ) && '-1' !== $_POST['action'] ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		$action2 = isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ? sanitize_key( wp_unslash( $_POST['action2'] ) ) : '';
		$bulk    = '' !== $action ? $action : $action2;

		if ( ! in_array( $bulk, [ 'bulk_delete', 'bulk_archive', 'bulk_activate' ], true ) ) {
			return;
		}

		check_admin_referer( 'bulk-products' );

		$ids   = isset( $_POST['product_id'] ) ? array_values( array_filter( wp_parse_id_list( wp_unslash( $_POST['product_id'] ) ) ) ) : [];
		$count = 0;
		$model = Product::instance();

		foreach ( $ids as $id ) {
			if ( 'bulk_delete' === $bulk ) {
				if ( $model->delete( $id ) ) {
					++$count;
				}
			} elseif ( 'bulk_archive' === $bulk ) {
				if ( $model->update_status( $id, 'draft' ) ) {
					++$count;
				}
			} elseif ( 'bulk_activate' === $bulk ) {
				if ( $model->update_status( $id, 'active' ) ) {
					++$count;
				}
			}
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';

		wp_safe_redirect(
			add_query_arg(
				[
					'page'          => $page,
					'licenser_msg'  => $bulk,
					'licenser_done' => $count,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle nonce-verified bulk actions for Licenses list screen prior to output.
	 *
	 * @return void
	 */
	public function handle_licenses_bulk_actions() {
		if ( ! current_user_can( (string) apply_filters( 'licenser_admin_capability', 'manage_options', 'licenses' ) ) ) {
			return;
		}

		$action  = isset( $_POST['action'] ) && '-1' !== $_POST['action'] ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
		$action2 = isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ? sanitize_key( wp_unslash( $_POST['action2'] ) ) : '';
		$bulk    = '' !== $action ? $action : $action2;

		if ( ! in_array( $bulk, [ 'bulk_activate', 'bulk_deactivate', 'bulk_delete' ], true ) ) {
			return;
		}

		check_admin_referer( 'bulk-licenses' );

		$ids   = isset( $_POST['license_id'] ) ? array_values( array_filter( wp_parse_id_list( wp_unslash( $_POST['license_id'] ) ) ) ) : [];
		$model = License::instance();
		$count = 0;

		if ( 'bulk_activate' === $bulk ) {
			$count = $model->bulk_update_status( $ids, 1 );
		} elseif ( 'bulk_deactivate' === $bulk ) {
			$count = $model->bulk_update_status( $ids, 0 );
		} elseif ( 'bulk_delete' === $bulk ) {
			$count = $model->bulk_delete( $ids );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'          => 'licenser-licenses',
					'licenser_msg'  => $bulk,
					'licenser_done' => $count,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
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
