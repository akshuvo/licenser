<?php

namespace Licenser;
use Licenser\Models\Product;
use Licenser\Models\License;

/**
 * Installer class
 */
class MigrateOldDb {

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->run_migration();
    }

	// Get Data
	public function get_data( $table ){
		if( $table == 'product' ){
			return $this->migrate_products();
		} else if( $table == 'license' ){
			return $this->migrate_licenses();
		}

	}

	// Products
	public function migrate_products(){
		global $wpdb;
		$products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lmfwppt_products");

		// Truncate the tables
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_license_packages");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_product_releases");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_products");

		// Loop through the results and add to new table
		foreach( $products as $key => $product ){
			// error_log( print_r( $product, true ) );
			$packages = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}lmfwppt_license_packages WHERE product_id = %d", $product->id) );
			
			$banners = !empty( $product->banners ) ? unserialize( $product->banners ) : [
				'low' => '',
				'high' => '',
			];

			$sections = !empty( $product->sections ) ? unserialize( $product->sections ) : [];

			$new_packages = [];
			if( !empty( $packages ) ){
				foreach( $packages as $key => $package ){
					$new_packages[] = [
						'package_id' => $package->package_id,
						'label' => $package->label,
						'update_period' => $package->update_period,
						'domain_limit' => $package->domain_limit,
					];
				}
			}
			
			// Insert to new table
			$add_product = Product::instance()->create([
				'name' => $product->name,
				'slug' => $product->slug,
				'product_type' => $product->product_type,
				'tested' => $product->tested,
				'requires' => $product->requires,
				'requires_php' => $product->requires_php,
				'banners' => $banners,
				'description' => '',
				'author_name' => $product->author,
				'homepage_url' => '',
				'demo_url' => '',
				'icon_url' => '',
				'created_by' => $product->created_by,
				'status' => 'active',
	
				'version' => $product->version,
				'changelog' => isset( $sections['changelog']['content'] ) ? $sections['changelog']['content'] : '',
				'file_name' => basename( $product->download_link ),
				'download_link' => $product->download_link,
				'release_date' => $product->dated,
	
				'license_packages' => $new_packages,
			]);
		}

		return $products;
	}


	function run_migration(){
			
		global $wpdb;

	}


}