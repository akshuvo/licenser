<?php
namespace TinyTopCRM\Models;

/**
 * Board class
 */
class Board {

    use \TinyTopCRM\Traits\SingletonTraitSelf;

    public function __construct() {
        global $ttcdb;

        $this->ttcdb = $ttcdb;

        error_log( 'Board Model' );
    }

    /**
     * Get Board
     *
     * @var int
     */
    public function get( $id ) {
        global $ttcdb;

        $board = $ttcdb->wpdb->get_row(
            $ttcdb->wpdb->prepare(
                "SELECT * FROM {$ttcdb->boards} WHERE id = %d",
                $id
            )
        );

        return $board;
    }
}