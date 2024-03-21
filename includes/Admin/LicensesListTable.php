<?php 
namespace Licenser\Admin;

use Licenser\Products;
use Licenser\Licenses;

if( !class_exists('WP_List_Table') ){
	require_once ABSPATH .'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Product List Table Class
 */
class LicensesListTable extends \WP_List_Table{

	function __construct(){
		parent::__construct([
			'singular' => "license",
			'plural' => "license",
			'ajax' => false
		]);
	}

	// number of column
	public function get_columns(){
		return [
			'cb' => "<input type='checkbox'/>",
			'license_key' => __('License Key','lmfwppt'),
			'license_details' => __('License Info','lmfwppt'),
			'order_id' => __('Order ID','lmfwppt'),
			'end_date' => __('Expires','lmfwppt'),
			'dated' => __('Date','lmfwppt'),
		];

	}

	/**
     * Get sortable columns
     *
     * @return array
     */

	// pagination and sortable use this code
    function get_sortable_columns() {
        $sortable_columns = [
            'id' => [ 'id', true ],
            'dated' => [ 'dated', true ],
        ];

        return $sortable_columns;
    }
    // pagination and sortable use this code

	protected function column_default($item, $column_name){
		switch ($column_name) {
			case 'value':
				# code...
				break;
			
			default:
				return isset($item->$column_name) ? $item->$column_name : '';
		}
	}

	// Default column Customize
	public function column_license_key($item){
		$actions = [];

		$actions['id']   = sprintf( '<span class="id">ID: %s </span>', $item->id );

		$actions['edit']   = sprintf( '<a href="%s" title="%s">%s</a>', admin_url( 'admin.php?page=licenser-licenses&action=edit&id=' . $item->id ), $item->id, __( 'Edit', 'lmfwppt' ), __( 'Edit', 'lmfwppt' ) );

        $actions['delete'] = sprintf( '<a href="%s" class="submitdelete" onclick="return confirm(\'Are you sure?\');" title="%s">%s</a>', wp_nonce_url( admin_url( 'admin-post.php?action=lmfwppt-delete-license&id=' . $item->id ), 'lmfwppt-delete-license' ), $item->id, __( 'Delete', 'lmfwppt' ), __( 'Delete', 'lmfwppt' ) );

		return sprintf(
			'<input class="w-100" type="text" value="%1$s" readonly/> %2$s', $item->license_key, $this->row_actions($actions)
		);

	}

	protected function column_cb($item){
		return sprintf(
			"<input name='product_id[]' type='checkbox' value='%d'/>", $item->id
		);
	}

	protected function column_dated($item){
		return date('j F Y',strtotime($item->dated));
	}

	protected function column_end_date($item){
		if( $item->is_lifetime == "1" ){
			return __( 'Lifetime', 'lmfwppt' );
		}
		return date('j F Y',strtotime($item->end_date));
	}

	public function column_license_details( $item ){	



		$package_details = '<a href="admin.php?page=licenser-licenses&action=edit&id=">'.$item->package_id.'</a>';
		 
		$package_details .= '<ul class="package_details">
					<li>Domain Limit: '.$item->domain_limit.'</li>
					<li>Product Type: '.$item->source.'</li>
				</ul>';
		return $package_details; 
	}

	public function prepare_items( ){

		$column = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [$column, $hidden, $sortable];

		//  pagination and sortable
		 $per_page     = 20;
         $current_page = $this->get_pagenum();
         $offset = ( $current_page - 1 ) * $per_page;

        $args = [
            'number' => $per_page,
            'offset' => $offset,
        ];

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = $_REQUEST['orderby'];
            $args['order'] = $_REQUEST['order'];
        }

        $this->items = $this->get_license_list($args);

		// $licenseObj = new Licenses();

        // pagination and sortable
		$this->set_pagination_args([
			// 'total_items' => $licenseObj->license_count(),
			'total_items' => 0,
            'per_page'    => $per_page,
		]);
	}

	// Function 
	/**
	 * Get the License
	 *
	 * @return Array
	 */
	function get_license_list( $args = [] ) {
	    global $wpdb;

	    $defaults = [
	        'number' => 20,
	        'offset' => 0,
	        'orderby' => 'id',
	        'order' => 'DESC',
	    ];

	    $args = wp_parse_args( $args, $defaults );
		$license_table = licenser_table('licenses');

	    $product_list = $wpdb->prepare(
	            "SELECT * FROM {$license_table}
	            ORDER BY {$args['orderby']} {$args['order']}
	            LIMIT %d, %d",
	            $args['offset'], $args['number'] 
	    );

	    $items = $wpdb->get_results( $product_list );

	    return $items;
	}

}
