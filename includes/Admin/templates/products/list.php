<?php
// Get Page 
$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;

// Get Type
$product_type = $page == 'licenser-themes' ? 'theme' : 'plugin';

?>

<div class="wrap">
   <div class="licenser-root">
    <!-- Header  -->
    <div class="licenser-header">
        <div class="licenser-header__title">
            <h1>
                <?php 
                    /* translators: %s: Product Type */
                    echo esc_html( sprintf( __('License Manager: %ss', 'licenser' ), $product_type )  ); 
                ?>
            </h1>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-'.$product_type.'s&action=new' ) ); ?>" class="page-title-action"><?php echo esc_html( sprintf( __('Add New %s', 'licenser'), $product_type ) ); ?></a>
    </div>
    <!-- Header  -->
    <!-- Content  -->
    <div class="licenser-content">
        <form action="" method="post">
            <?php 
                $table = new Licenser\Admin\ProductsListTable();
                $table->prepare_items($product_type);
                $table->display();

            ?>
        </form>
    </div>
    <!-- Content  -->
    </div>
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