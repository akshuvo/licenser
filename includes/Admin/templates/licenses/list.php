<div class="wrap">
   <div class="licenser-root">
    <!-- Header  -->
    <div class="licenser-header">
        <div class="licenser-header__title">
            <h2><?php esc_html_e( 'Licenses', 'licenser' ); ?></h2>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New License', 'licenser' ); ?></a>
    </div>
    <!-- Header  -->
    <!-- Content  -->
    <div class="licenser-content">
        <form action="" method="post">
            <?php 
                $table = new Licenser\Admin\LicensesListTable();
                $table->prepare_items();
                $table->display();
            ?>
        </form>
    </div>
    <!-- Content  -->
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($){
        // Remove License
        jQuery(document).on('click', '.licenser-delete-license', function(e){
            e.preventDefault();
            let $this = jQuery(this);
            let productId = $this.data('id');
            let wrapper = $this.closest('tr');

            // Return if no id
            if( !productId ){
                return;
            }

            // Confirm
            if( !confirm('Are you sure you want to delete this license?') ){
                return;
            }
            
            // Remove from database
            jQuery.ajax({
                type: 'delete',
                url: Licenser.rest_url + 'licenses/' + productId,
                beforeSend: function(xhr) {
                    // Nonce
                    xhr.setRequestHeader( 'X-WP-Nonce', Licenser.nonce);

                    // Add Opacity
                    wrapper.css('opacity', '0.5');
                },
                success: function(data) {
                    jQuery(document).trigger("lmfwppt_notice", ['License Deleted Successfully.', 'success']);
                    wrapper.remove();
                },
                error: function(data) {
                    jQuery(document).trigger("lmfwppt_notice", ['Something went wrong. Try again.', 'error']);
                },

            });
        });
    });
</script>