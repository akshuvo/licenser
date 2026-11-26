<?php

namespace Licenser;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;

/**
 * Admin handler class.
 */
class Admin {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		new Admin\Menu();

		add_filter( 'plugin_action_links_' . plugin_basename( LICENSER_FILE ), [ $this, 'action_links' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_post_licenser_export_licenses', [ $this, 'export_licenses_csv' ] );
	}

	/**
	 * Add plugin action links on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$plugin_links = [
			'<a href="' . esc_url( admin_url( 'admin.php?page=licenser' ) ) . '">' . esc_html__( 'Dashboard', 'licenser' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=licenser-settings' ) ) . '">' . esc_html__( 'Settings', 'licenser' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Export filtered licenses to a formula-injection-safe CSV download.
	 *
	 * @return void
	 */
	public function export_licenses_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export licenses.', 'licenser' ), 403 );
		}

		check_admin_referer( 'licenser_export_licenses' );

		$product_id     = isset( $_GET['product_id'] ) ? absint( wp_unslash( $_GET['product_id'] ) ) : 0;
		$license_status = isset( $_GET['license_status'] ) ? sanitize_key( wp_unslash( $_GET['license_status'] ) ) : '';
		$search         = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		$license_model = License::instance();
		$licenses      = $license_model->get_all(
			[
				'number'         => -1,
				'product_id'     => $product_id > 0 ? $product_id : '',
				'license_status' => $license_status,
				'search'         => $search,
				'orderby'        => 'id',
				'order'          => 'DESC',
			]
		);
		$licenses      = is_array( $licenses ) ? $licenses : [];

		// Batch-hydrate products, packages, and active domain counts.
		$products_map = [];
		$all_products = Product::instance()->get_all(
			[
				'number'  => -1,
				'status'  => 'all',
				'columns' => 'id, name',
			]
		);
		foreach ( (array) $all_products as $prod ) {
			if ( isset( $prod->id ) ) {
				$products_map[ absint( $prod->id ) ] = (string) $prod->name;
			}
		}

		$packages_map = [];
		$all_packages = LicensePackage::instance()->get_all( [ 'number' => -1 ] );
		foreach ( (array) $all_packages as $pkg ) {
			if ( isset( $pkg->id ) ) {
				$packages_map[ absint( $pkg->id ) ] = (string) $pkg->label;
			}
		}

		$license_ids   = wp_list_pluck( $licenses, 'id' );
		$domain_counts = $license_model->get_active_domain_counts( $license_ids );

		$filename = 'licenser-licenses-' . licenser_date( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming CSV directly to php://output.
		$output = fopen( 'php://output', 'w' );
		if ( ! $output ) {
			exit;
		}

		// UTF-8 BOM for clean spreadsheet encoding.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Streaming UTF-8 BOM to php://output.
		fwrite( $output, "\xEF\xBB\xBF" );

		$escape_cell = static function ( $value ) {
			$str = (string) $value;
			if ( '' !== $str && in_array( $str[0], [ '=', '+', '-', '@', "\t", "\r" ], true ) ) {
				return "'" . $str;
			}
			return $str;
		};

		fputcsv(
			$output,
			[
				'ID',
				'License Key',
				'Product',
				'Package',
				'Status',
				'Active Domains',
				'Domain Limit',
				'Lifetime',
				'Expires',
				'Source',
				'Source ID',
				'Created Date',
			]
		);

		foreach ( $licenses as $license ) {
			$lid          = absint( $license->id );
			$pid          = absint( $license->product_id );
			$pkg_id       = absint( $license->package_id );
			$computed     = $license_model->get_computed_status( $license );
			$active_count = isset( $domain_counts[ $lid ] ) ? $domain_counts[ $lid ] : 0;
			$limit        = absint( $license->domain_limit );

			fputcsv(
				$output,
				array_map(
					$escape_cell,
					[
						$lid,
						(string) $license->license_key,
						isset( $products_map[ $pid ] ) ? $products_map[ $pid ] : '',
						isset( $packages_map[ $pkg_id ] ) ? $packages_map[ $pkg_id ] : '',
						$computed,
						$active_count,
						0 === $limit ? 'Unlimited' : $limit,
						1 === (int) $license->is_lifetime ? 'Yes' : 'No',
						1 === (int) $license->is_lifetime ? 'Lifetime' : (string) $license->end_date,
						(string) $license->source,
						absint( $license->source_id ),
						(string) $license->dated,
					]
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing php://output stream.
		fclose( $output );
		exit;
	}

	/**
	 * Enqueue admin scripts and styles strictly on Licenser screens.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! $screen || false === strpos( (string) $screen->id, 'licenser' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_style( 'licenser-admin-style', LICENSER_ASSETS . '/css/admin.css', [], LICENSER_VERSION );
		wp_enqueue_script( 'licenser-admin-script', LICENSER_ASSETS . '/js/admin.js', [ 'jquery' ], LICENSER_VERSION, true );

		wp_localize_script(
			'licenser-admin-script',
			'Licenser',
			[
				'rest_url'          => esc_url_raw( rest_url( 'licenser/v1/' ) ),
				'public_api_base'   => esc_url_raw( untrailingslashit( rest_url( 'licenser/v1/public' ) ) ),
				'root'              => esc_url_raw( rest_url() ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'admin_url'         => admin_url( 'admin.php' ),
				'media_state_nonce' => wp_create_nonce( 'licenser_media_frame_state' ),
				'sdk_api_url'       => esc_url_raw( untrailingslashit( licenser_api_url() ) ),
				'i18n'              => [
					'confirm_delete_title'        => __( 'Confirm Deletion', 'licenser' ),
					'confirm_delete_product'      => __( 'Are you sure you want to delete this product?', 'licenser' ),
					'confirm_delete_product_warn' => __( 'This product still has linked records. Archiving sets its status to Draft while keeping customer licenses intact.', 'licenser' ),
					'archive_instead'             => __( 'Archive Instead', 'licenser' ),
					'delete_anyway'               => __( 'Delete Anyway', 'licenser' ),
					'delete_label'                => __( 'Delete', 'licenser' ),
					'cancel_label'                => __( 'Cancel', 'licenser' ),
					'cascade_checkbox'            => __( 'Also delete linked releases and license packages', 'licenser' ),
					'confirm_delete_package'      => __( 'Remove this license package? Existing licenses referencing this package will retain their own limits and expiry.', 'licenser' ),
					'confirm_delete_release'      => __( 'Delete this release entry? This action cannot be undone.', 'licenser' ),
					'confirm_delete_license'      => __( 'Delete this license key along with all of its activated domains and metadata?', 'licenser' ),
					'confirm_delete_domain'       => __( 'Remove this activated domain from the license?', 'licenser' ),
					'confirm_deactivate_domain'   => __( 'Deactivating this domain will block license checks and automatic updates for this site until it is reactivated.', 'licenser' ),
					'confirm_activate_domain'     => __( 'Reactivate licensing and automatic update access for this domain?', 'licenser' ),
					'confirm_archive_product'     => __( 'Archive this product (set to Draft)? Existing licenses will remain in the database, but public update checks will pause until restored.', 'licenser' ),
					'confirm_restore_product'     => __( 'Restore this product to Active status so it serves updates and license checks again?', 'licenser' ),
					'confirm_action_title'        => __( 'Confirm Action', 'licenser' ),
					'archive_label'               => __( 'Archive', 'licenser' ),
					'restore_label'               => __( 'Restore', 'licenser' ),
					'confirm_delete_meta'         => __( 'Delete this metadata entry?', 'licenser' ),
					'product_deleted'             => __( 'Product deleted successfully.', 'licenser' ),
					'product_archived'            => __( 'Product archived (set to Draft).', 'licenser' ),
					'product_restored'            => __( 'Product restored to Active.', 'licenser' ),
					'product_updated'             => __( 'Product updated successfully.', 'licenser' ),
					'product_added'               => __( 'Product created successfully. Redirecting...', 'licenser' ),
					'release_saved'               => __( 'Release saved successfully.', 'licenser' ),
					'release_deleted'             => __( 'Release deleted successfully.', 'licenser' ),
					'package_deleted'             => __( 'License package removed.', 'licenser' ),
					'license_deleted'             => __( 'License deleted successfully.', 'licenser' ),
					'license_updated'             => __( 'License updated successfully.', 'licenser' ),
					'license_added'               => __( 'License issued successfully. Redirecting...', 'licenser' ),
					'bulk_created'                => __( 'Licenses generated successfully.', 'licenser' ),
					'domain_added'                => __( 'Domain added successfully.', 'licenser' ),
					'domain_updated'              => __( 'Domain status updated.', 'licenser' ),
					'domain_deleted'              => __( 'Domain removed successfully.', 'licenser' ),
					'meta_added'                  => __( 'Metadata saved successfully.', 'licenser' ),
					'meta_deleted'                => __( 'Metadata entry deleted.', 'licenser' ),
					'settings_saved'              => __( 'Settings saved successfully.', 'licenser' ),
					'cache_cleared'               => __( 'Product cache cleared successfully.', 'licenser' ),
					'integration_enabled'         => __( 'Integration enabled.', 'licenser' ),
					'integration_disabled'        => __( 'Integration disabled. Related integration files will no longer be loaded.', 'licenser' ),
					'confirm_disable_integration' => __( 'Disabling this integration stops loading its PHP files, admin hooks, and storefront features until re-enabled. Existing licenses remain untouched.', 'licenser' ),
					'disable_label'               => __( 'Disable', 'licenser' ),
					'enabled_label'               => __( 'Enabled', 'licenser' ),
					'disabled_label'              => __( 'Disabled', 'licenser' ),
					'requires_prefix'             => __( 'Requires', 'licenser' ),
					'copied'                      => __( 'Copied!', 'licenser' ),
					'copied_toast'                => __( 'Copied to clipboard.', 'licenser' ),
					'copy_failed'                 => __( 'Could not copy automatically. Please select and copy manually.', 'licenser' ),
					'generic_error'               => __( 'Something went wrong. Please try again.', 'licenser' ),
					'select_package'              => __( 'Select Package', 'licenser' ),
					'no_package_found'            => __( 'No Package Found', 'licenser' ),
					'lifetime'                    => __( 'Lifetime', 'licenser' ),
					'unlimited'                   => __( 'Unlimited', 'licenser' ),
					'days_suffix'                 => __( 'Days', 'licenser' ),
					'sites_suffix'                => __( 'Sites', 'licenser' ),
					'site_singular'               => __( 'Site', 'licenser' ),
					'active'                      => __( 'Active', 'licenser' ),
					'inactive'                    => __( 'Inactive', 'licenser' ),
					'expired'                     => __( 'Expired', 'licenser' ),
					'expiring_soon'               => __( 'Expiring Soon', 'licenser' ),
					'activate'                    => __( 'Activate', 'licenser' ),
					'deactivate'                  => __( 'Deactivate', 'licenser' ),
					'stable'                      => __( 'Stable', 'licenser' ),
					'previous'                    => __( 'Previous', 'licenser' ),
					'sdk_step_plugin'             => __( 'Step 2: Add this snippet to your main plugin file.', 'licenser' ),
					'sdk_step_theme'              => __( "Step 2: Add this snippet to your theme's functions.php file.", 'licenser' ),
					'select_media_title'          => __( 'Select File', 'licenser' ),
					'select_media_button'         => __( 'Use File', 'licenser' ),
					'running_request'             => __( 'Running request...', 'licenser' ),
				],
			]
		);

		// Only load CodeMirror on the SDK Generator screen.
		if ( false !== strpos( (string) $screen->id, 'licenser-sdk-generator' ) ) {
			$cm_settings['codeEditor'] = wp_enqueue_code_editor(
				[
					'type'       => 'php',
					'codemirror' => [
						'readOnly' => true,
					],
				]
			);
			wp_localize_script( 'licenser-admin-script', 'licenser_cm', $cm_settings );
			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );
		}
	}
}
