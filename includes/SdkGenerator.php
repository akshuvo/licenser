<?php 
namespace Licenser;

class SdkGenerator{
	
	/**
     * Initialize the class
     */

	function __construct(){
		
	}

	// Setting add form action
   function sdkgenerator_add(){

      if ( isset( $_POST['lmaction'] ) && $_POST['lmaction'] == "sdk_generator_add_form" ) {

      	$product_type =  isset( $_POST['product_type'] ) ? sanitize_text_field( $_POST['product_type'] ) : '';
        $select_product = isset( $_POST['select_product'] ) ? sanitize_text_field( $_POST['select_product'] ) : '';
        $menu_type = isset( $_POST['menu_type'] ) ? sanitize_text_field( $_POST['menu_type'] ) : '';
        $page_title = isset( $_POST['page_title'] ) ? sanitize_text_field( $_POST['page_title'] ) : '';
        $menu_title = isset( $_POST['menu_title'] ) ? sanitize_text_field( $_POST['menu_title'] ) : '';
        $parent_slug = isset( $_POST['parent_slug'] ) ? sanitize_text_field( $_POST['parent_slug'] ) : '';

        $fn_prefix = str_replace('-', '_', sanitize_title($menu_title));

        ob_start(); ?>

        <?php if( $menu_type == 'section' ) : ?>
            <div class="lmwppt-inner-card card-shameless mb-0">
                <h3>Place this code where you like to show the license activation form</h3>
            </div>
            <div class="lmwppt-inner-card">
                <pre><?php echo esc_html("<?php do_action( 'lmfwppt_license_activation_form_fields' ); ?>"); ?></pre>
            </div>
        <?php endif; ?>

        <div class="lmwppt-inner-card card-shameless mb-0">
            <h3>Below code will be going to your plugin's main file</h3>
        </div>
        <div class="lmwppt-inner-card">

            <pre>
/**
 * License Management
 */
add_action( 'init', '<?php echo esc_html( $fn_prefix ); ?>_updates' );
function <?php echo esc_html( $fn_prefix ); ?>_updates( ){

    // Load Class
    include_once( dirname( __FILE__ ) . '/updates/LmfwpptAutoUpdatePlugin.php' );

    // Plugin Args
    $plugin = plugin_basename( __FILE__ );
    $plugin_slug = (dirname(plugin_basename(__FILE__)));
    $current_version = '1.0.0';
    $remote_url = '<?php echo home_url('/'); ?>';

    // Required args
    $args = array(
        'plugin' => $plugin,
        'plugin_slug' => $plugin_slug,
        'current_version' => $current_version,
        'remote_url' => $remote_url,
        'menu_type' => '<?php echo esc_html( $menu_type ); ?>',
        'parent_slug' => '<?php echo esc_html( $parent_slug ); ?>',
        'page_title' => '<?php echo esc_html( $page_title ); ?>',
        'menu_title' => '<?php echo esc_html( $menu_title ); ?>',
    );

    new LmfwpptAutoUpdatePlugin( $args );
}
            </pre>
        </div>

        <div class="lmwppt-inner-card">
            <pre>
// Another Code block
            </pre>
        </div>


        <?php $output = ob_get_clean();

        echo $output;

        }
      die();
   }

}