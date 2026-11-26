<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\Product;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Products WP_List_Table implementation.
 */
class ProductsListTable extends \WP_List_Table {

	/**
	 * Initialize the list table.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'product',
				'plural'   => 'products',
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
			'cb'    => '<input type="checkbox" />',
			'name'  => __( 'Product Name', 'licenser' ),
			'slug'  => __( 'Product Slug', 'licenser' ),
			'dated' => __( 'Date', 'licenser' ),
		];
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'name'  => [ 'name', true ],
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
	 * Render the product name column with edit/delete row actions.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	public function column_name( $item ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only current admin screen slug.
		$page     = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'licenser-plugins';
		$edit_url = admin_url( 'admin.php?page=' . $page . '&action=edit&id=' . absint( $item->id ) );

		$actions           = [];
		$actions['edit']   = sprintf(
			'<a href="%1$s" title="%2$d">%3$s</a>',
			esc_url( $edit_url ),
			absint( $item->id ),
			esc_html__( 'Edit', 'licenser' )
		);
		$actions['delete'] = sprintf(
			'<a href="#" class="licenser-delete-product" data-id="%1$d">%2$s</a>',
			absint( $item->id ),
			esc_html__( 'Delete', 'licenser' )
		);

		return sprintf(
			'<a href="%1$s"><strong>%2$s</strong></a> %3$s',
			esc_url( $edit_url ),
			esc_html( (string) $item->name ),
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
			'<input name="product_id[]" type="checkbox" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * Render the formatted creation date column.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_dated( $item ) {
		$timestamp = ! empty( $item->dated ) ? strtotime( (string) $item->dated ) : false;
		return $timestamp ? esc_html( licenser_date( 'j F Y', $timestamp ) ) : '';
	}

	/**
	 * Prepare items, sorting, and pagination arguments.
	 *
	 * @param string|null $product_type Product type filter (`plugin` or `theme`).
	 * @return void
	 */
	public function prepare_items( $product_type = null ) {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$args = [
			'number'       => $per_page,
			'offset'       => $offset,
			'product_type' => $product_type ? sanitize_key( $product_type ) : 'plugin',
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WP_List_Table sorting params, allowlisted below.
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['orderby'] = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order'] = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		}

		$this->items = $this->get_product_list( $args );

		$total_items = Product::instance()->count(
			[
				'product_type' => $args['product_type'],
			]
		);

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);
	}

	/**
	 * Query products for the list table with strict ORDER BY allowlisting.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_product_list( $args = [] ) {
		global $wpdb;

		$defaults = [
			'number'       => 20,
			'offset'       => 0,
			'orderby'      => 'id',
			'order'        => 'DESC',
			'product_type' => 'plugin',
		];

		$args            = wp_parse_args( $args, $defaults );
		$allowed_orderby = [ 'id', 'name', 'slug', 'dated' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$order_by_sql    = sanitize_sql_orderby( "{$orderby} {$order}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->licenser_products} WHERE product_type = %s ORDER BY {$order_by_sql} LIMIT %d, %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$args['product_type'],
				absint( $args['offset'] ),
				absint( $args['number'] )
			)
		);

		return is_array( $items ) ? $items : [];
	}
}
