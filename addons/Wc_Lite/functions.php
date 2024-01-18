<?php
//add_action( 'woocommerce_thankyou', 'wc_generate_license', 15 );
add_action( 'woocommerce_order_status_changed', 'wc_generate_license', 150 );
//add_action( 'woocommerce_checkout_update_order_meta', 'wc_generate_license', 15 );
