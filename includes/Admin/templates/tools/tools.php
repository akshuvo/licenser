<?php
use Licenser\Models\Product;

// Product Model
$product_model = Product::instance();

// Products
$products = $product_model->get_all([
   'status' => 'active',
   'number' => -1,
   'inc_packages' => false,
   'columns' => ' name, product_type, uuid',
]);
?>
<div class="wrap">
      <div class="licenser-root">
         <div class="lmwppt-inner-card card-shameless">
            <h1><?php esc_html_e( 'SDK Generator', 'licenser' ); ?></h1>
         </div>
         <form action="" method="post" id="sdk-generator-form">
            <div class="lmwppt-inner-card">
               <div class="lmfwppt-form-section">

                  <div class="lmfwppt-form-field">
                     <label for="product_type"><?php esc_html_e( 'Product Type', 'licenser' ); ?></label>
                     <select name="product_type" class="product_type" id="product_type" required>
                        <option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
                        <?php foreach( $product_model->get_types() as $key => $value ) : ?>
                           <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field">
                     <label for="select_product"><?php esc_html_e( 'Select Product', 'licenser' ); ?></label>
                     <select id="select_product" name="select_product" class="select_product products_list" required>
                        <option value="" class="blank">Select Product</option>
                        <?php foreach ( $products as $product ): ?>   
                           <option value="<?php echo esc_attr( $product->uuid ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt--' ); ?>"><?php echo esc_html( $product->name ); ?></option>
                        <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field">
                     <label for="lmfwppt_menu_select"><?php esc_html_e( 'Output Type', 'licenser' ); ?></label>
                     <select id="lmfwppt_menu_select" name="menu_type" class="menu_select" required>
                        <option value="menu"><?php esc_html_e( 'Menu', 'licenser' ); ?></option>
                        <option value="sub_menu"><?php esc_html_e( 'Sub Menu', 'licenser' ); ?></option>
                        <option value="section"><?php esc_html_e( 'Section', 'licenser' ); ?></option>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field parent-slug-menu  hide-on-section-type">
                     <label for="lmfwppt_parent_menu_slug"><?php esc_html_e( 'Parent Menu Slug', 'licenser' ); ?></label>
                     <input type="text" list="parent_slug_list" name="parent_slug" id="lmfwppt_parent_menu_slug" class="regular-text" placeholder="<?php esc_attr_e( 'Parent Menu Slug', 'licenser' ); ?>">
                     <datalist id="parent_slug_list">
                       <option value="index.php">
                       <option value="edit.php">
                       <option value="upload.php">
                       <option value="edit.php?post_type=page">
                       <option value="edit-comments.php">
                       <option value="themes.php">
                       <option value="plugins.php">
                       <option value="users.php">
                       <option value="tools.php">
                       <option value="options-general.php">
                     </datalist>
                  </div>

                  <div class="lmfwppt-form-field page-title-wrap hide-on-section-type show-on-default-type">
                     <label for="lmfwppt_page_title"><?php esc_html_e( 'Page Title', 'licenser' ); ?></label>
                     <input type="text" name="page_title" id="lmfwppt_page_title" class="regular-text lmfwppt_page_title" placeholder="<?php esc_attr_e( 'Page Title', 'licenser' ); ?>">
                  </div>

                  <div class="lmfwppt-form-field menu-title-wrap hide-on-section-type show-on-default-type">
                     <label for="lmfwppt_menu_title"><?php esc_html_e( 'Menu Title', 'licenser' ); ?></label>
                     <input type="text" name="menu_title" id="lmfwppt_menu_title" class="regular-text lmfwppt_menu_title" placeholder="<?php esc_attr_e( 'Menu Title', 'licenser' ); ?>">
                  </div>

                  <div class="lmfwppt-form-field d-flex">
                     <label class="me-1">
                        <input type="checkbox" name="inc-licensing" id="inc-licensing" checked>
                        <?php esc_html_e( 'Include Licensing', 'licenser' ); ?>
                     </label>
                     <label class="me-1">
                        <input type="checkbox" name="inc-updater" id="inc-updater" checked>
                        <?php esc_html_e( 'Include Updater', 'licenser' ); ?>
                     </label>
                     <label>
                        <input type="checkbox" name="inc-insights" id="inc-insights">
                        <?php esc_html_e( 'Include Insights', 'licenser' ); ?>
                     </label>
                  </div>

               </div>
            </div>
            <div class="lmwppt-inner-card lmfwppt-buttons card-shameless">
               
               <div class="submit_btn_area"> 
      
                  <button type="submit" class="button button-primary" id="submit"><?php esc_html_e( 'Generate SDK', 'licenser' ); ?></button>
                  <span class="spinner"></span>
               </div>
               <div class="lmfwppt-notices"></div>  

               
            </div>

            <div class="lmwppt-inner-card ">
               <div class="lmfwppt-form-section">
                  <h2><?php esc_html_e( 'How to use', 'licenser' ); ?></h2>
                  <h3><?php esc_html_e( 'Step 1: Clone the Licenser client library', 'licenser' ); ?></h3>
                  <p><?php esc_html_e( 'Navigate to your project using shell. And clone the Licenser client repository inside your project', 'licenser' ); ?></p>
                  <p>
                  <pre class="licenser-code-text">
                     cd your-plugin-or-theme
                     git clone https://github.com/LicenserWP/client.git licenser
                  </pre>
                  </p>
                  
                  <p><?php printf( __( 'Or download the client library from %s and include it in your plugin or theme\'s root directory. The client library folder name should be `licenser` and the structure should be like the following:', 'licenser' ), '<a href="' . esc_url( 'https://github.com/LicenserWP/client' ) . '" target="_blank">' . esc_html( 'https://github.com/LicenserWP/client' ) . '</a>' ); ?></p>

                  <p>
                     <pre class="licenser-code-text">
                     ├── your-plugin-or-theme
                     │   ├── licenser
                     │   │   ├── src
                     │   │   │   ├── Client.php
                     │   │   │   ├── Insights.php
                     │   │   │   ├── License.php
                     │   │   │   ├── Updater.php
                     │   │   │
                     </pre>
                  </p>

                  
                  <div class="client_generator_response"></div>
               </div>
            </div>
            
         </form>
      </div>
</div>
<style>
.client_generator_response .CodeMirror {
   height: 100%;
   border: 1px solid #dbdbdb;
}
.licenser-code-text {
   background-color: #f1f1f1;
   padding: 10px;
   border-radius: 5px;
   white-space: pre-line;
}
</style>
<script type="text/javascript">
   jQuery(document).ready(function($){
      // Implement Fields
      jQuery(document).on('change', '#select_product', function(e){
         let product_id = jQuery(this).val();
         let product_name = jQuery('#select_product option:selected').text();
         let product_slug = product_name.toLowerCase().replace(/ /g, '_');

         // Set Page Title
         jQuery('#lmfwppt_page_title').val(product_name + ' License Activation');

         // Set Menu Title
         jQuery('#lmfwppt_menu_title').val(product_name + ' License');

      });

      jQuery(document).on('submit', '#sdk-generator-form', function(e){
         e.preventDefault();
         licenser_generate_client();
      });

      function licenser_generate_client(){
         let product_type = jQuery('#product_type').val();
         let product_id = jQuery('#select_product').val();
         let product_name = jQuery('#select_product option:selected').text();
         let product_slug = product_name.toLowerCase().replace(/ /g, '_');
         let menu_type = jQuery('#lmfwppt_menu_select').val();
         let parent_slug = jQuery('#lmfwppt_parent_menu_slug').val();
         let page_title = jQuery('#lmfwppt_page_title').val();
         let menu_title = jQuery('#lmfwppt_menu_title').val();
         let apiUrl = '<?php echo licenser_api_url(); ?>';
         let inc_licensing = jQuery('#inc-licensing').is(':checked');
         let inc_updater = jQuery('#inc-updater').is(':checked');
         let inc_insights = jQuery('#inc-insights').is(':checked');

         // Section HTML
         let sectionHtml = '';
         if( product_type == 'plugin' ){
            sectionHtml = `<h3><?php esc_html_e( 'Step 2: Add the following code to your plugin file.', 'licenser' ); ?></h3>`;
         } else {
            sectionHtml = `<h3><?php esc_html_e( "Step 2: Add the following code to your theme's functions.php file.", 'licenser' ); ?></h3>`;
         }

         let output = `
&lt?php
/**
 * Initialize Licenser client
 *
 * @return void
 */
function ${product_slug}_licenser_client_init() {

   if ( ! class_exists( 'Licenser\Client' ) ) {
      require_once __DIR__ . '/licenser/src/Client.php';
   }

   $client = new Licenser\Client( '${product_id}', '${product_name}', __FILE__, '${apiUrl}' );
   `;

   if( inc_licensing ){
      output += `
	// Active license page and checker
	$license = $client->license();`;

   if( menu_type != 'section' ){
      output += `
	$license->add_settings_page([
      'type'        => '${menu_type}',
      'menu_title'  => '${menu_title}',
      'page_title'  => '${page_title}',
      'menu_slug'   => '${product_slug}_settings',
      'parent_slug' => '${parent_slug}',
	]);
   `;
   } else {
      output += `
    $license->add_settings_page([
       'type' => 'section'
    ]);
   `;
   }
   }


   if( inc_updater ){
      output += `
	// Active updater
	$client->updater()->init( $client );
   `;
   }


   if( inc_insights ){
      output += `
    // Active insights
    $client->insights()->init();`;
   }

   output += `

}
${product_slug}_licenser_client_init();`;


         output = `${sectionHtml}<textarea class="fancy-textarea" readonly>${output}</textarea>`;
         jQuery('.client_generator_response').html(output);

         wp.codeEditor.initialize(jQuery('.fancy-textarea'), licenser_cm);

      }
   });
</script>