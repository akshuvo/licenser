<?php

namespace Licenser\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Licenser\Models\Product;
use Licenser\Models\ProductRelease;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Products WP_List_Table implementation.
 */
class ProductsListTable extends \WP_List_Table {

	/**
	 * Cached dependency counts indexed by product ID.
	 *
	 * @var array<int, array{releases: int, packages: int, licenses: int}>
	 */
	protected $dependency_counts = [];

	/**
	 * Cached stable releases indexed by product ID.
	 *
	 * @var array<int, object|null>
	 */
	protected $stable_releases = [];

	/**
	 * Active product type (`plugin` or `theme`).
	 *
	 * @var string
	 */
	protected $product_type = 'plugin';

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
			'cb'       => '<input type="checkbox" />',
			'name'     => __( 'Product', 'licenser' ),
			'version'  => __( 'Latest Release', 'licenser' ),
			'packages' => __( 'Packages', 'licenser' ),
			'licenses' => __( 'Licenses', 'licenser' ),
			'uuid'     => __( 'Product UUID', 'licenser' ),
			'dated'    => __( 'Date', 'licenser' ),
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
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return [
			'bulk_archive'  => __( 'Archive (Draft)', 'licenser' ),
			'bulk_activate' => __( 'Restore Active', 'licenser' ),
			'bulk_delete'   => __( 'Delete', 'licenser' ),
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
	 * Render the product column with icon thumbnail, slug, status badge, and row actions.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	public function column_name( $item ) {
		$id       = absint( $item->id );
		$page     = ( 'theme' === $item->product_type ) ? 'licenser-themes' : 'licenser-plugins';
		$edit_url = admin_url( 'admin.php?page=' . $page . '&action=edit&id=' . $id );
		$sdk_url  = admin_url( 'admin.php?page=licenser-sdk-generator&product_uuid=' . rawurlencode( (string) $item->uuid ) );
		$lic_url  = admin_url( 'admin.php?page=licenser-licenses&product_id=' . $id );
		$deps     = isset( $this->dependency_counts[ $id ] ) ? $this->dependency_counts[ $id ] : [
			'releases' => 0,
			'packages' => 0,
			'licenses' => 0,
		];

		$status      = ! empty( $item->status ) ? (string) $item->status : 'active';
		$is_active   = ( 'active' === $status );
		$toggle_to   = $is_active ? 'draft' : 'active';
		$toggle_text = $is_active ? __( 'Archive', 'licenser' ) : __( 'Restore', 'licenser' );

		$actions             = [];
		$actions['edit']     = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $edit_url ),
			esc_html__( 'Edit', 'licenser' )
		);
		$actions['licenses'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $lic_url ),
			esc_html__( 'Licenses', 'licenser' )
		);
		$actions['sdk']      = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $sdk_url ),
			esc_html__( 'SDK', 'licenser' )
		);
		$actions['status']   = sprintf(
			'<a href="#" class="licenser-toggle-product-status" data-id="%1$d" data-status="%2$s">%3$s</a>',
			$id,
			esc_attr( $toggle_to ),
			esc_html( $toggle_text )
		);
		$actions['delete']   = sprintf(
			'<a href="#" class="licenser-delete-product licenser-row-delete" data-id="%1$d" data-name="%2$s" data-releases="%3$d" data-packages="%4$d" data-licenses="%5$d">%6$s</a>',
			$id,
			esc_attr( (string) $item->name ),
			absint( $deps['releases'] ),
			absint( $deps['packages'] ),
			absint( $deps['licenses'] ),
			esc_html__( 'Delete', 'licenser' )
		);

		$thumb_html = '';
		if ( ! empty( $item->icon_url ) ) {
			$thumb_html = sprintf(
				'<img class="licenser-thumb" src="%1$s" alt="%2$s" loading="lazy" />',
				esc_url( (string) $item->icon_url ),
				esc_attr( (string) $item->name )
			);
		} else {
			$initial    = strtoupper( substr( trim( (string) $item->name ), 0, 1 ) );
			$thumb_html = sprintf(
				'<span class="licenser-thumb licenser-thumb--placeholder" aria-hidden="true">%s</span>',
				esc_html( '' !== $initial ? $initial : 'P' )
			);
		}

		$status_badge = '';
		if ( ! $is_active ) {
			$status_badge = sprintf(
				'<span class="licenser-badge licenser-badge--inactive">%s</span>',
				esc_html( ucfirst( $status ) )
			);
		}

		return sprintf(
			'<div class="licenser-product-cell">%1$s<div class="licenser-product-cell__info"><a class="licenser-product-cell__title" href="%2$s">%3$s %4$s</a><span class="licenser-product-cell__slug">%5$s</span>%6$s</div></div>',
			$thumb_html,
			esc_url( $edit_url ),
			esc_html( (string) $item->name ),
			$status_badge,
			esc_html( (string) $item->slug ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render latest stable release version and date.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_version( $item ) {
		$id      = absint( $item->id );
		$release = isset( $this->stable_releases[ $id ] ) ? $this->stable_releases[ $id ] : null;

		if ( ! $release || empty( $release->version ) ) {
			return '<span class="licenser-badge licenser-badge--inactive">' . esc_html__( 'No Release', 'licenser' ) . '</span>';
		}

		$ts        = ! empty( $release->release_date ) ? strtotime( (string) $release->release_date ) : false;
		$date_html = $ts ? '<span class="licenser-cell-secondary licenser-tabular">' . esc_html( licenser_date( 'M j, Y', $ts ) ) . '</span>' : '';

		return sprintf(
			'<div class="licenser-cell-stack"><span><span class="licenser-badge licenser-badge--active">v%1$s</span></span>%2$s</div>',
			esc_html( (string) $release->version ),
			$date_html
		);
	}

	/**
	 * Render linked packages count column.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_packages( $item ) {
		$id    = absint( $item->id );
		$count = isset( $this->dependency_counts[ $id ] ) ? $this->dependency_counts[ $id ]['packages'] : 0;

		return sprintf(
			'<span class="licenser-badge licenser-badge--inactive licenser-tabular">%s</span>',
			esc_html(
				sprintf(
					/* translators: %d: Number of packages. */
					_n( '%d package', '%d packages', $count, 'licenser' ),
					$count
				)
			)
		);
	}

	/**
	 * Render linked licenses count column linking directly to filtered licenses.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_licenses( $item ) {
		$id    = absint( $item->id );
		$count = isset( $this->dependency_counts[ $id ] ) ? $this->dependency_counts[ $id ]['licenses'] : 0;
		$url   = admin_url( 'admin.php?page=licenser-licenses&product_id=' . $id );

		return sprintf(
			'<a href="%1$s" class="licenser-source-link licenser-tabular">%2$s</a>',
			esc_url( $url ),
			esc_html(
				sprintf(
					/* translators: %d: Number of licenses. */
					_n( '%d license', '%d licenses', $count, 'licenser' ),
					$count
				)
			)
		);
	}

	/**
	 * Render truncated Product UUID with one-click Copy button.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_uuid( $item ) {
		$uuid = (string) $item->uuid;
		if ( '' === $uuid ) {
			return '&mdash;';
		}

		$short = strlen( $uuid ) > 12 ? substr( $uuid, 0, 8 ) . '...' : $uuid;

		return sprintf(
			'<div class="licenser-key-inline"><code class="licenser-code-pill" title="%1$s">%2$s</code><button type="button" class="licenser-icon-btn licenser-copy-btn" data-licenser-copy="%1$s" title="%3$s" aria-label="%3$s"><span class="dashicons dashicons-admin-page"></span></button></div>',
			esc_attr( $uuid ),
			esc_html( $short ),
			esc_attr__( 'Copy Product UUID', 'licenser' )
		);
	}

	/**
	 * Render row selection checkbox.
	 *
	 * @param object $item Row object.
	 * @return string
	 */
	protected function column_cb( $item ) {
		$id    = absint( $item->id );
		$label = ! empty( $item->name ) ? (string) $item->name : sprintf( '#%d', $id );

		return sprintf(
			'<input id="cb-select-%1$d" name="product_id[]" type="checkbox" value="%1$d" aria-label="%2$s" />',
			$id,
			/* translators: %s: Product name. */
			esc_attr( sprintf( __( 'Select %s', 'licenser' ), $label ) )
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
		return $timestamp ? '<span class="licenser-tabular licenser-muted">' . esc_html( licenser_date( 'M j, Y', $timestamp ) ) . '</span>' : '&mdash;';
	}

	/**
	 * Prepare items, sorting, filtering, and pagination arguments.
	 *
	 * @param string|null $product_type Product type filter (`plugin` or `theme`).
	 * @return void
	 */
	public function prepare_items( $product_type = null ) {
		$this->product_type = $product_type ? sanitize_key( $product_type ) : 'plugin';

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WP_List_Table filters.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_REQUEST['product_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['product_status'] ) ) : 'all';
		if ( ! in_array( $status, [ 'all', 'active', 'draft', 'trash' ], true ) ) {
			$status = 'all';
		}

		$args = [
			'number'       => $per_page,
			'offset'       => $offset,
			'product_type' => $this->product_type,
			'status'       => $status,
			'search'       => $search,
			'orderby'      => 'id',
			'order'        => 'DESC',
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WP_List_Table sorting params, allowlisted in Product::get_all().
		if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['orderby'] = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order'] = sanitize_key( wp_unslash( $_REQUEST['order'] ) );
		}

		$product_model = Product::instance();
		$items         = $product_model->get_all( $args );
		$this->items   = is_array( $items ) ? $items : [];

		if ( ! empty( $this->items ) ) {
			$ids                     = wp_list_pluck( $this->items, 'id' );
			$this->dependency_counts = $product_model->get_dependency_counts( $ids );

			$release_model = ProductRelease::instance();
			foreach ( $ids as $pid ) {
				$this->stable_releases[ absint( $pid ) ] = $release_model->get_stable( absint( $pid ) );
			}
		}

		$total_items = $product_model->count(
			[
				'product_type' => $this->product_type,
				'status'       => $status,
				'search'       => $search,
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
