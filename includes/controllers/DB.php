<?php
namespace Licenser\Controllers;

class DB {

    /**
     * Singleton instance
     *
     * @var DB
     */
    private static $instance;

    /**
     * Database instance
     *
     * @var wpdb
     */
    public $wpdb;

    /**
     * Database prefix
     *
     * @var string
     */
    public $prefix;

    /**
     * Product table name
     */
    public $products;

    /**
     * Product releases table name
     */
    public $product_releases;

    /**
     * License packages table name
     */
    public $license_packages;

    /**
     * Licenses table name
     */
    public $licenses;

    /**
     * License domains table name
     */
    public $license_domains;

    /**
     * Tables
     *
     * @var array
     */
    public $tables = [
        'products',
        'product_releases',
        'license_packages',
        'licenses',
        'license_domains',
    ];
    
    /**
     * Initialize the class
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $this->wpdb->prefix . 'licenser_';
        $this->set_table_names();
    }

    /**
     * Get the singleton instance
     *
     * @return DB
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set table names
     * 
     * @return void
     */
    private function set_table_names() {

        foreach ( $this->tables as $table ) {
            $this->$table = $this->prefix . $table;
        }

        // error_log('DB tables set');
    }
}