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
     * Boards table name
     *
     * @var string
     */
    public $boards;

    /**
     * Board meta table name
     *
     * @var string
     */
    public $board_meta;

    /**
     * Columns table name
     *
     * @var string
     */
    public $columns;

    /**
     * Column meta table name
     *
     * @var string
     */
    public $column_meta;

    /**
     * Items table name
     *
     * @var string
     */
    public $items;

    /**
     * Item meta table name
     *
     * @var string
     */
    public $item_meta;

    /**
     * Item collections table name
     *
     * @var string
     */
    public $item_groups;

    public $tables = [
        'boards',
        'board_meta',
        'columns',
        'column_meta',
        'items',
        'item_meta',
        'item_groups',
    ];
    

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $this->wpdb->prefix . 'ttcrm_';
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