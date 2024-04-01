<?php

?>
<div class="wrap">
   <h1 class="wp-heading-inline"><?php esc_html_e( 'Overview', 'licenser' ); ?></h1>
</div>
 

<div class="lmwppt-main-section">
   <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-plugins' ) ); ?>">
   <div class="lmwppt-inner">
      <div class="lmwppt-content">

         <div class="lmwppt-title">
            <h3 class="wp-heading-inline"><?php esc_html_e( 'Total Plugin', 'licenser' ); ?></h3>
         </div>
         <div class="lmwppt-icon">
           <span class="dashicons dashicons-admin-plugins lmwppt-icon-2"></span>
         </div>

      </div>
      <div class="lmwppt-counter">
         <h1><?php echo 0; ?></h1>
      </div>
   </div>
   </a>
   <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-themes' ) ); ?>">
   <div class="lmwppt-inner">
      <div class="lmwppt-content">

         <div class="lmwppt-title">
            <h3 class="wp-heading-inline"><?php esc_html_e( 'Total Theme', 'licenser' ); ?></h3>
         </div>
         <div class="lmwppt-icon">
            <span class="dashicons dashicons-admin-appearance lmwppt-icon-3"></span>
         </div>

      </div>
      <div class="lmwppt-counter">
         <h1><?php echo 0; ?></h1>
      </div>
   </div>
   </a>
   <a href="<?php echo esc_url( admin_url( 'admin.php?page=licenser-licenses' ) ); ?>">
   <div class="lmwppt-inner">
      <div class="lmwppt-content">
         <div class="lmwppt-title">
            <h3 class="wp-heading-inline"><?php esc_html_e( 'Total License', 'licenser' ); ?></h3>
         </div>
         <div class="lmwppt-icon">
            <span class="dashicons dashicons-tickets-alt lmwppt-icon-1"></span>
         </div>
      </div>
      <div class="lmwppt-counter">
         <h1><?php echo 0; ?></h1>
      </div>
   </div>
   </a>
</div>

