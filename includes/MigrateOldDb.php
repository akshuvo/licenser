<?php

namespace Licenser;
use Licenser\Models\Product;
use Licenser\Models\License;

/**
 * Migrate Old Database
 */
class MigrateOldDb {
	
	// Get Data
	public function migrate_data( $table ){
		if( $table == 'product' ){
			return $this->migrate_products();
		} elseif( $table == 'license' ){
			return $this->migrate_licenses();
		} elseif( $table == 'reset' ){
			return $this->reset_tables();
		}
	}

	// Reset Tables
	public function reset_tables(){
		// Messages
		$messages = [];

		global $wpdb;
		$table_prefix = $wpdb->prefix . 'licenser_';

		$wpdb->query("DROP TABLE {$table_prefix}products");
		if( $wpdb->last_error ){
			$messages[] = 'Error: ' . $wpdb->last_error;
		} else {
			$messages[] = 'Products Table Dropped';
		}

		$wpdb->query("DROP TABLE {$table_prefix}product_releases");
		if( $wpdb->last_error ){
			$messages[] = 'Error: ' . $wpdb->last_error;
		} else {
			$messages[] = 'Product Releases Table Dropped';
		}

		$wpdb->query("DROP TABLE {$table_prefix}license_packages");
		if( $wpdb->last_error ){
			$messages[] = 'Error: ' . $wpdb->last_error;
		} else {
			$messages[] = 'License Packages Table Dropped';
		}

		$wpdb->query("DROP TABLE {$table_prefix}licenses");
		if( $wpdb->last_error ){
			$messages[] = 'Error: ' . $wpdb->last_error;
		} else {
			$messages[] = 'Licenses Table Dropped';
		}

		$wpdb->query("DROP TABLE {$table_prefix}license_domains");
		if( $wpdb->last_error ){
			$messages[] = 'Error: ' . $wpdb->last_error;
		} else {
			$messages[] = 'License Domains Table Dropped';
		}

		// Delete option
		delete_option( 'licenser_db_version' );
		$messages[] = 'DB_Version Option Deleted';

		$messages[] = 'Completed!';
		$messages[] = 'Please deactivate and reactivate the plugin to recreate the tables';

		return implode( '<br>', $messages );
	}

	// Licenses
	public function migrate_licenses(){
		global $wpdb;
		$licenses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lmfwppt_licenses");

		// Messages
		$messages = [];

		// Set Packages for preventing duplicate query
		$packages = [];

		// Truncate the tables
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_licenses");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_license_domains");

		$messages[] = 'Tables Truncated';

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
				'package_id' => isset( $package->id ) ? $package->id : 0,
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

			$messages[] = 'License Added: ' . $license->license_key . ' - (' . count( $get_domains ) . ' Domains)';

		}

		$messages[] = 'Total Licenses: ' . count( $licenses );
		$messages[] = 'Migration Completed';

		return implode( '<br>', $messages );
	}

	// Products
	public function migrate_products(){
		global $wpdb;
		$products = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lmfwppt_products");

		// Messages
		$messages = [];

		// Truncate the tables
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_license_packages");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_product_releases");
		$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}licenser_products");

		$messages[] = 'Tables Truncated';

		// Loop through the results and add to new table
		foreach( $products as $key => $product ){
			// Banners
			$banners = !empty( $product->banners ) ? unserialize( $product->banners ) : [
				'low' => '',
				'high' => '',
			];

			// Sections
			$sections = !empty( $product->sections ) ? unserialize( $product->sections ) : [];

			// Get the packages
			$packages = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}lmfwppt_license_packages WHERE product_id = %d", $product->id) );
			
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

			$messages[] = 'Product Added: ' . $product->name . ' - (' . count( $new_packages ) . ' Packages)';
		}


		$messages[] = 'Total Products: ' . count( $products );
		$messages[] = 'Migration Completed';

		return implode( '<br>', $messages );
	}

}