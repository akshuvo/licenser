<?php

namespace Licenser;
use Licenser\Models\Product;
use Licenser\Models\License;

/**
 * Installer class
 */
class MigrateOldDb {
	
	// Get Data
	public function migrate_data( $table ){
		if( $table == 'product' ){
			return $this->migrate_products();
		} else if( $table == 'license' ){
			return $this->migrate_licenses();
		}
	}

	// Licenses
	public function migrate_licenses(){
		global $wpdb;
		$licenses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lmfwppt_licenses");

		// Set Packages for preventing duplicate query
		$packages = [];

		// Truncate the tables
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_licenses");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_license_domains");

		// Loop through the results and add to new table
		foreach( $licenses as $key => $license ){
			$get_domains = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}lmfwppt_license_domains WHERE license_id = %d", $license->id) );

			// Get the package
			if( isset( $packages[$license->package_id] ) ) {
				$package = $packages[$license->package_id];
			} else {
				$package = $wpdb->get_row( $wpdb->prepare("SELECT id, product_id  FROM {$wpdb->prefix}lmfwppt_license_packages WHERE package_id = %s", $license->package_id) );
				$packages[$license->package_id] = $package;

				
			}

			// Add License
			$add_license = License::instance()->create([
				'status' => $license->status,
				'license_key' => $license->license_key,
				'product_id' => isset( $package->product_id ) ? $package->product_id : 0,
				'package_id' => $license->package_id,
				'source' => 'wc',
				'source_id' => $license->order_id,
				'end_date' => $license->end_date,
				'is_lifetime' => $license->is_lifetime,
				'domain_limit' => $license->domain_limit,
			]);

			// Add Domains
			if( !empty( $get_domains ) ){
				foreach( $get_domains as $key => $domain ){
					$add_domain = License::instance()->add_domain([
						'license_id' => $add_license,
						'domain' => $domain->domain,
						'status' => $domain->status,
						'dated' => $domain->dated,
					]);
				}
			}

		}

		return $licenses;
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