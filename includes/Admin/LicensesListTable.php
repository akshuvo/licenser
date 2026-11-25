<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Licenses WP_List_Table implementation.
 */
class LicensesListTable extends \WP_List_Table {

	/**
	 * Initialize the list table.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'license',
				'plural'   => 'licenses',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Define table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'cb'              => '<input type="checkbox" />',
			'license_key'     => __( 'License Key', 'licenser' ),
			'license_details' => __( 'License Info', 'licenser' ),
			'domain_limit'    => __( 'Domain Limit', 'licenser' ),
			'end_date'        => __( 'Expires', 'licenser' ),
			'source'          => __( 'Source', 'licenser' ),
			'source_id'       => __( 'Source ID', 'licenser' ),
			'dated'           => __( 'Date', 'licenser' ),
		];
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'id'    => [ 'id', true ],
			'dated' => [ 'dated', true ],
		];
	}

	/**
	 * Render default column cell output.
	 *
	 * @param object $item        Row object.
	 * @param string $column_name Column slug.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( (string) $item->$column_name ) : '';
	}

	/**
	 * Render the license key column with row actions.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	public function column_license_key( $item ) {
		$edit_url = admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . absint( $item->id ) );

		$actions           = [];
		$actions['id']     = sprintf( '<span class="id">ID: %d</span>', absint( $item->id ) );
		$actions['edit']   = sprintf(
			'<a href="%1$s" title="%2$d">%3$s</a>',
			esc_url( $edit_url ),
			absint( $item->id ),
			esc_html__( 'Edit', 'licenser' )
		);
		$actions['delete'] = sprintf(
			'<a href="#" class="licenser-delete-license" data-id="%1$d">%2$s</a>',
			absint( $item->id ),
			esc_html__( 'Delete', 'licenser' )
		);

		return sprintf(
			'<input class="w-100" type="text" value="%1$s" readonly /> %2$s',
			esc_attr( (string) $item->license_key ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render row selection checkbox.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input name="license_id[]" type="checkbox" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * Render formatted creation date.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_dated( $item ) {
		$timestamp = ! empty( $item->dated ) ? strtotime( (string) $item->dated ) : false;
		return $timestamp ? esc_html( licenser_date( 'j F Y', $timestamp ) ) : '';
	}

	/**
	 * Render license expiration date or Lifetime label.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_end_date( $item ) {
		if ( 1 === (int) $item->is_lifetime ) {
			return esc_html__( 'Lifetime', 'licenser' );
		}

		$timestamp = ! empty( $item->end_date ) ? strtotime( (string) $item->end_date ) : false;
		return $timestamp ? esc_html( licenser_date( 'j F Y', $timestamp ) ) : esc_html__( 'N/A', 'licenser' );
	}

	/**
	 * Render domain limit column (0 displays Unlimited).
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_domain_limit( $item ) {
		$limit = isset( $item->domain_limit ) ? absint( $item->domain_limit ) : 0;
		return 0 === $limit ? esc_html__( 'Unlimited', 'licenser' ) : esc_html( (string) $limit );
	}

	/**
	 * Render license product/package summary.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	public function column_license_details( $item ) {
		return sprintf(
			'<ul class="package_details"><li>%1$s: %2$d</li><li>%3$s: %4$s</li></ul>',
			esc_html__( 'Product ID', 'licenser' ),
			absint( $item->product_id ),
			esc_html__( 'Package ID', 'licenser' ),
			esc_html( (string) $item->package_id )
		);
	}

	/**
	 * Prepare items, sorting, and pagination arguments.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$args = [
			'number' => $per_page,
			'offset' => $offset,
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WP_List_Table sorting params, allowlisted below.
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['orderby'] = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order'] = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		}

		$this->items = $this->get_license_list( $args );
		$total_items = License::instance()->count();

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * Query licenses for the list table with strict ORDER BY allowlisting.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_license_list( $args = [] ) {
		global $wpdb;

		$defaults = [
			'number'  => 20,
			'offset'  => 0,
			'orderby' => 'id',
			'order'   => 'DESC',
		];

		$args          = wp_parse_args( $args, $defaults );
		$license_table = licenser_table( 'licenses' );

		$allowed_orderby = [ 'id', 'dated', 'end_date', 'status', 'product_id' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$license_table} ORDER BY {$orderby} {$order} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $args['offset'] ),
				absint( $args['number'] )
			)
		);

		return is_array( $items ) ? $items : [];
	}
}
