<?php
use Licenser\Models\Product;

// Product Model
$product_model = Product::instance();

// Products
$products = $product_model->get_all([
   'status' => 'active',
   'number' => -1,
   'inc_packages' => true,
   'columns' => 'id, name, product_type',
]);
?>
<div class="wrap">
      <div class="lmwppt-wrap">
         <div class="lmwppt-inner-card card-shameless">
            <h1><?php _e( 'SDK Generator', 'lmfwppt' ); ?></h1>
         </div>
         <form action="" method="post" id="sdk-generator-add-form">
            <div class="lmwppt-inner-card">
               <div class="lmfwppt-form-section">

                  <div class="lmfwppt-form-field">
                     <label for="product_type"><?php esc_html_e( 'Product Type', 'lmfwppt' ); ?></label>
                     <select name="product_type" class="product_type" id="product_type" required>
                        <option value=""><?php esc_html_e( 'Select Product Type', 'licenser' ); ?></option>
                           <?php foreach( $product_model->get_types() as $key => $value ) : ?>
                              <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $product->product_type, $key ); ?> ><?php echo esc_html( $value ); ?></option>
                           <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field">
                     <label for="select_product"><?php esc_html_e( 'Select Product', 'lmfwppt' ); ?></label>
                     <select id="select_product" name="select_product" class="select_product products_list" required>
                        <option value="" class="blank">Select Product</option>
                        <?php foreach ( $products as $product ): ?>   
                           <option value="<?php echo esc_attr( $product->id ); ?>" class="<?php echo esc_attr( $product->product_type . '-opt--' ); ?>" <?php selected( $product_id, $product->id ); ?>><?php echo esc_html( $product->name ); ?></option>
                        <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field">
                     <label for="lmfwppt_menu_select"><?php esc_html_e( 'Output Type', 'lmfwppt' ); ?></label>
                     <select id="lmfwppt_menu_select" name="menu_type" class="menu_select" required>
                        <option value="menu"><?php esc_html_e( 'Menu', 'lmfwppt' ); ?></option>
                        <option value="sub_menu"><?php esc_html_e( 'Sub Menu', 'lmfwppt' ); ?></option>
                        <option value="section"><?php esc_html_e( 'Section', 'lmfwppt' ); ?></option>
                     </select>
                  </div>

                  <div class="lmfwppt-form-field parent-slug-menu hidden hide-on-section-type">
                     <label for="lmfwppt_parent_menu_slug"><?php esc_html_e( 'Parent Menu Slug', 'lmfwppt' ); ?></label>
                     <input type="text" list="parent_slug_list" name="parent_slug" id="lmfwppt_parent_menu_slug" class="regular-text" placeholder="<?php esc_attr_e( 'Parent Menu Slug', 'lmfwppt' ); ?>">
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
                     <label for="lmfwppt_page_title"><?php esc_html_e( 'Page Title', 'lmfwppt' ); ?></label>
                     <input type="text" name="page_title" id="lmfwppt_page_title" class="regular-text lmfwppt_page_title" placeholder="<?php esc_attr_e( 'Page Title', 'lmfwppt' ); ?>">
                  </div>

                  <div class="lmfwppt-form-field menu-title-wrap hide-on-section-type show-on-default-type">
                     <label for="lmfwppt_menu_title"><?php esc_html_e( 'Menu Title', 'lmfwppt' ); ?></label>
                     <input type="text" name="menu_title" id="lmfwppt_menu_title" class="regular-text lmfwppt_menu_title" placeholder="<?php esc_attr_e( 'Menu Title', 'lmfwppt' ); ?>">
                  </div>

               </div>
            </div>
            <div class="lmwppt-inner-card lmfwppt-buttons card-shameless">
               
               <div class="submit_btn_area"> 
      
                  <?php submit_button( __( 'Generate', 'lmfwppt' ), 'primary' ); ?> 
                  <span class="spinner"></span>
               </div>
               <div class="lmfwppt-notices"></div>  
            </div>
            <div class="sdk_generator_response"></div>
         </form>
      </div>
</div>