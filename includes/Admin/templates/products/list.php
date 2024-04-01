<?php
// Get Page 
$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;

// Get Type
$product_type = $page == 'licenser-themes' ? 'theme' : 'plugin';

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( sprintf( 'License Manager: %ss', $product_type ), 'licenser' ); ?></h1>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-'.$product_type.'s&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Product', 'licenser' ); ?></a>

    <?php if ( isset( $_GET['updated'] ) ) { ?>
        <div class="notice notice-success">
            <p><?php esc_html_e( 'Product License has been updated successfully!', 'licenser' ); ?></p>
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

<script type="text/javascript">
    jQuery(document).ready(function($){
        // Remove Package
        jQuery(document).on('click', '.licenser-delete-product', function(e, postBox){
            let $this = jQuery(this);
            let productId = $this.data('id');
            let wrapper = $this.closest('tr');

            // Return if no id
            if( !productId ){
                return;
            }

            // Confirm
            if( !confirm('Are you sure you want to delete this product?') ){
                return;
            }
            
            // Remove from database
            jQuery.ajax({
                type: 'delete',
                url: Licenser.rest_url + 'products/' + productId,
                beforeSend: function(xhr) {
                    // Nonce
                    xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);

                    // Add Opacity
                    wrapper.css('opacity', '0.5');
                },
                success: function(data) {
                    jQuery(document).trigger("lmfwppt_notice", ['Product Deleted Successfully.', 'success']);
                    wrapper.remove();
                },
                error: function(data) {
                    jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
                },

            });
        });
    });
</script>