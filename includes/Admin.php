<?php

namespace Licenser;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	}

	/**
	 * Add plugin action links on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$plugin_links = [
			'<a href="' . esc_url( admin_url( 'admin.php?page=licenser' ) ) . '">' . esc_html__( 'License Manager', 'licenser' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
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
				'root'              => esc_url_raw( rest_url() ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'admin_url'         => admin_url( 'admin.php' ),
				'media_state_nonce' => wp_create_nonce( 'licenser_media_frame_state' ),
				'sdk_api_url'       => esc_url_raw( untrailingslashit( licenser_api_url() ) ),
				'i18n'              => [
					'confirm_delete_product' => __( 'Are you sure you want to delete this product?', 'licenser' ),
					'confirm_delete_license' => __( 'Are you sure you want to delete this license?', 'licenser' ),
					'confirm_delete_domain'  => __( 'Are you sure you want to delete this domain?', 'licenser' ),
					'confirm_delete_meta'    => __( 'Are you sure you want to delete this metadata entry?', 'licenser' ),
					'product_deleted'        => __( 'Product deleted successfully.', 'licenser' ),
					'product_updated'        => __( 'Product updated successfully.', 'licenser' ),
					'product_added'          => __( 'Product added successfully. Redirecting...', 'licenser' ),
					'license_deleted'        => __( 'License deleted successfully.', 'licenser' ),
					'license_updated'        => __( 'License updated successfully.', 'licenser' ),
					'license_added'          => __( 'License added successfully. Redirecting...', 'licenser' ),
					'domain_deleted'         => __( 'Domain deleted successfully.', 'licenser' ),
					'meta_deleted'           => __( 'Meta deleted successfully.', 'licenser' ),
					'settings_saved'         => __( 'Settings saved successfully.', 'licenser' ),
					'generic_error'          => __( 'Something went wrong. Try again.', 'licenser' ),
					'select_package'         => __( 'Select Package', 'licenser' ),
					'no_package_found'       => __( 'No Package Found', 'licenser' ),
					'sdk_step_plugin'        => __( 'Step 2: Add the following code to your plugin file.', 'licenser' ),
					'sdk_step_theme'         => __( "Step 2: Add the following code to your theme's functions.php file.", 'licenser' ),
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
