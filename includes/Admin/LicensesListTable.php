<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\License;
use Licenser\Models\LicensePackage;
use Licenser\Models\Product;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Licenses WP_List_Table implementation.
 */
class LicensesListTable extends \WP_List_Table {

	/**
	 * Product lookup map indexed by ID.
	 *
	 * @var array<int, object>
	 */
	protected $products_map = [];

	/**
	 * Package lookup map indexed by ID.
	 *
	 * @var array<int, string>
	 */
	protected $packages_map = [];

	/**
	 * Active domain counts indexed by license ID.
	 *
	 * @var array<int, int>
	 */
	protected $domain_counts = [];

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
			'cb'          => '<input type="checkbox" />',
			'license_key' => __( 'License Key', 'licenser' ),
			'product'     => __( 'Product & Package', 'licenser' ),
			'status'      => __( 'Status', 'licenser' ),
			'domains'     => __( 'Domains', 'licenser' ),
			'end_date'    => __( 'Expires', 'licenser' ),
			'source'      => __( 'Source', 'licenser' ),
			'dated'       => __( 'Issued', 'licenser' ),
		];
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'end_date' => [ 'end_date', true ],
			'dated'    => [ 'dated', true ],
		];
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [
			'bulk_activate'   => __( 'Activate', 'licenser' ),
			'bulk_deactivate' => __( 'Deactivate', 'licenser' ),
			'bulk_delete'     => __( 'Delete', 'licenser' ),
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
	 * Render the license key column with copy control and row actions.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	public function column_license_key( $item ) {
		$id       = absint( $item->id );
		$key      = (string) $item->license_key;
		$edit_url = admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . $id );

		$actions           = [];
		$actions['id']     = sprintf( '<span class="licenser-muted licenser-tabular">#%d</span>', $id );
		$actions['edit']   = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $edit_url ),
			esc_html__( 'Edit', 'licenser' )
		);
		$actions['delete'] = sprintf(
			'<a href="#" class="licenser-delete-license licenser-row-delete" data-id="%1$d">%2$s</a>',
			$id,
			esc_html__( 'Delete', 'licenser' )
		);

		return sprintf(
			'<div class="licenser-key-inline"><code class="licenser-code-pill" title="%2$s">%1$s</code><button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="%2$s" title="%3$s" aria-label="%3$s"><span class="dashicons dashicons-admin-page"></span></button></div>%4$s',
			esc_html( $key ),
			esc_attr( $key ),
			esc_attr__( 'Copy License Key', 'licenser' ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render human-readable Product Name & Package Label.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_product( $item ) {
		$pid       = absint( $item->product_id );
		$pkg_id    = absint( $item->package_id );
		$prod_name = isset( $this->products_map[ $pid ] ) ? (string) $this->products_map[ $pid ]->name : sprintf(
			/* translators: %d: Product ID. */
			__( 'Product #%d', 'licenser' ),
			$pid
		);
		$pkg_label = isset( $this->packages_map[ $pkg_id ] ) ? $this->packages_map[ $pkg_id ] : '';

		$prod_html = esc_html( $prod_name );
		if ( isset( $this->products_map[ $pid ] ) ) {
			$page      = ( 'theme' === $this->products_map[ $pid ]->product_type ) ? 'licenser-themes' : 'licenser-plugins';
			$prod_url  = admin_url( 'admin.php?page=' . $page . '&action=edit&id=' . $pid );
			$prod_html = sprintf( '<a class="licenser-cell-primary" href="%1$s">%2$s</a>', esc_url( $prod_url ), esc_html( $prod_name ) );
		} else {
			$prod_html = '<span class="licenser-cell-primary">' . $prod_html . '</span>';
		}

		$pkg_html = $pkg_label ? '<span class="licenser-cell-secondary">' . esc_html( $pkg_label ) . '</span>' : '<span class="licenser-cell-secondary">' . esc_html__( 'Custom / No Package', 'licenser' ) . '</span>';

		return '<div class="licenser-cell-stack">' . $prod_html . $pkg_html . '</div>';
	}

	/**
	 * Render semantic status badge (`Active`, `Expiring Soon`, `Expired`, `Inactive`).
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_status( $item ) {
		$computed = License::instance()->get_computed_status( $item );
		$map      = [
			'active'        => [ 'licenser-badge--active', __( 'Active', 'licenser' ) ],
			'expiring_soon' => [ 'licenser-badge--warning', __( 'Expiring Soon', 'licenser' ) ],
			'expired'       => [ 'licenser-badge--expired', __( 'Expired', 'licenser' ) ],
			'inactive'      => [ 'licenser-badge--inactive', __( 'Inactive', 'licenser' ) ],
		];

		$class = isset( $map[ $computed ] ) ? $map[ $computed ][0] : 'licenser-badge--inactive';
		$label = isset( $map[ $computed ] ) ? $map[ $computed ][1] : __( 'Inactive', 'licenser' );

		return sprintf(
			'<span class="licenser-badge %1$s"><span class="licenser-badge__dot"></span>%2$s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Render active domain count vs limit with visual meter.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_domains( $item ) {
		$lid   = absint( $item->id );
		$used  = isset( $this->domain_counts[ $lid ] ) ? $this->domain_counts[ $lid ] : 0;
		$limit = isset( $item->domain_limit ) ? absint( $item->domain_limit ) : 0;

		if ( 0 === $limit ) {
			return sprintf(
				'<div class="licenser-meter-wrap"><span class="licenser-meter-label">%1$d / %2$s</span></div>',
				$used,
				esc_html__( 'Unlimited', 'licenser' )
			);
		}

		$pct        = min( 100, (int) round( ( $used / max( 1, $limit ) ) * 100 ) );
		$fill_class = 'licenser-meter__fill';
		if ( $used >= $limit ) {
			$fill_class .= ' licenser-meter__fill--full';
		} elseif ( $pct >= 80 ) {
			$fill_class .= ' licenser-meter__fill--warn';
		}

		return sprintf(
			'<div class="licenser-meter-wrap"><span class="licenser-meter-label">%1$d / %2$d</span><div class="licenser-meter"><div class="%3$s" style="width:%4$d%%"></div></div></div>',
			$used,
			$limit,
			esc_attr( $fill_class ),
			$pct
		);
	}

	/**
	 * Render license expiration date or Lifetime pill.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_end_date( $item ) {
		if ( 1 === (int) $item->is_lifetime ) {
			return '<span class="licenser-badge licenser-badge--lifetime"><span class="licenser-badge__dot"></span>' . esc_html__( 'Lifetime', 'licenser' ) . '</span>';
		}

		$timestamp = ! empty( $item->end_date ) ? strtotime( (string) $item->end_date ) : false;
		if ( ! $timestamp ) {
			return '<span class="licenser-muted">&mdash;</span>';
		}

		return '<span class="licenser-tabular">' . esc_html( licenser_date( 'M j, Y', $timestamp ) ) . '</span>';
	}

	/**
	 * Render source badge with clickable WooCommerce order link when applicable.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_source( $item ) {
		$source    = strtolower( trim( (string) $item->source ) );
		$source_id = absint( $item->source_id );

		if ( in_array( $source, [ 'wc', 'woocommerce' ], true ) && $source_id > 0 ) {
			$order_url = admin_url( 'post.php?post=' . $source_id . '&action=edit' );
			if ( function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $source_id );
				if ( $order && method_exists( $order, 'get_edit_order_url' ) ) {
					$order_url = $order->get_edit_order_url();
				}
			}

			return sprintf(
				'<a href="%1$s" class="licenser-source-link licenser-tabular">WC #%2$d <span class="dashicons dashicons-external"></span></a>',
				esc_url( $order_url ),
				$source_id
			);
		}

		if ( '' === $source ) {
			return '<span class="licenser-badge licenser-badge--inactive">' . esc_html__( 'Manual', 'licenser' ) . '</span>';
		}

		$label = ucfirst( $source );
		if ( $source_id > 0 ) {
			$label .= ' #' . $source_id;
		}

		return '<span class="licenser-badge licenser-badge--inactive">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Render row selection checkbox.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_cb( $item ) {
		$id = absint( $item->id );

		return sprintf(
			'<input id="cb-select-%1$d" name="license_id[]" type="checkbox" value="%1$d" aria-label="%2$s" />',
			$id,
			/* translators: %d: License ID. */
			esc_attr( sprintf( __( 'Select license #%d', 'licenser' ), $id ) )
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
		return $timestamp ? '<span class="licenser-tabular licenser-muted">' . esc_html( licenser_date( 'M j, Y', $timestamp ) ) . '</span>' : '&mdash;';
	}

	/**
	 * Prepare items, sorting, filtering, batch hydration, and pagination.
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WP_List_Table filters.
		$product_id = isset( $_REQUEST['product_id'] ) ? absint( wp_unslash( $_REQUEST['product_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$license_status = isset( $_REQUEST['license_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['license_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$args = [
			'number'         => $per_page,
			'offset'         => $offset,
			'product_id'     => $product_id > 0 ? $product_id : '',
			'license_status' => $license_status,
			'search'         => $search,
			'orderby'        => 'id',
			'order'          => 'DESC',
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sorting allowlisted inside License::get_all().
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['orderby'] = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order'] = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		}

		$license_model = License::instance();
		$items         = $license_model->get_all( $args );
		$this->items   = is_array( $items ) ? $items : [];

		// Batch hydrate lookups to avoid N+1 queries.
		$all_products = Product::instance()->get_all(
			[
				'number'  => -1,
				'status'  => 'all',
				'columns' => 'id, name, product_type',
			]
		);
		foreach ( (array) $all_products as $prod ) {
			if ( isset( $prod->id ) ) {
				$this->products_map[ absint( $prod->id ) ] = $prod;
			}
		}

		$all_packages = LicensePackage::instance()->get_all( [ 'number' => -1 ] );
		foreach ( (array) $all_packages as $pkg ) {
			if ( isset( $pkg->id ) ) {
				$this->packages_map[ absint( $pkg->id ) ] = (string) $pkg->label;
			}
		}

		if ( ! empty( $this->items ) ) {
			$ids                 = wp_list_pluck( $this->items, 'id' );
			$this->domain_counts = $license_model->get_active_domain_counts( $ids );
		}

		$total_items = $license_model->count(
			[
				'product_id'     => $product_id > 0 ? $product_id : '',
				'license_status' => $license_status,
				'search'         => $search,
			]
		);

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);
	}
}
