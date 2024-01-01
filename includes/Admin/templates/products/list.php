<?php
// Get Page 
$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;

// Get Type
$product_type = $page == 'licenser-themes' ? 'theme' : 'plugin';

?>
<?php if( isset( $_GET['deleted'] ) ) :   ?>
<div class="notice notice-alt is-dismissible notice-success">
    <p><?php echo sprintf(__("%s Deleted.", "lmfwppt"), ucfirst($product_type)) ?></p>
</div>
<?php endif; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'License Manager: '.$product_type.'s', 'lmfwppt' ); ?></h1>

    <a href="<?php echo admin_url( 'admin.php?page=licenser-'.$product_type.'s&action=new' ); ?>" class="page-title-action"><?php _e( 'Add New Product', 'lmfwppt' ); ?></a>

    <?php if ( isset( $_GET['updated'] ) ) { ?>
        <div class="notice notice-success">
            <p><?php _e( 'Product License has been updated successfully!', 'lmfwppt' ); ?></p>
        </div>
    <?php } ?>

    <form action="" method="post">
    	<?php 
    		$table = new Licenser\Admin\ProductsListTable();
    		$table->prepare_items($product_type);
    		$table->display();

    	?>
    </form>

</div>

