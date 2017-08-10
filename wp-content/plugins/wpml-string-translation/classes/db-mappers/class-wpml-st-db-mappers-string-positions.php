<?php

class WPML_ST_DB_Mappers_String_Positions {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param $string_id
	 * @param $kind
	 *
	 * @return int
	 */
	public function get_count_of_positions_by_string_and_kind( $string_id, $kind ) {
		$query = "
			SELECT COUNT(id)
	        FROM {$this->wpdb->prefix}icl_string_positions 
	        WHERE string_id = %d AND kind = %d
        ";

		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $query, $string_id, $kind ) );
	}

	/**
	 * @param int $string_id
	 * @param int $kind
	 *
	 * @return array
	 */
	public function get_positions_by_string_and_kind( $string_id, $kind ) {
		$query = "
			SELECT position_in_page
            FROM {$this->wpdb->prefix}icl_string_positions
          	WHERE string_id = %d AND kind = %d
        ";

		return $this->wpdb->get_col( $this->wpdb->prepare( $query, $string_id, $kind ) );
	}

	/**
	 * @param $string_id
	 * @param $position
	 * @param $kind
	 *
	 * @return bool
	 */
	public function is_string_tracked( $string_id, $position, $kind ) {
		$query = "
			SELECT id
            FROM {$this->wpdb->prefix}icl_string_positions
            WHERE string_id=%d AND position_in_page=%s AND kind=%s
		";

		return (bool) $this->wpdb->get_var( $this->wpdb->prepare( $query, $string_id, $position, $kind ) );
	}

	/**
	 * @param $string_id
	 * @param $position
	 * @param $kind
	 */
	public function insert( $string_id, $position, $kind ) {
		$this->wpdb->insert( $this->wpdb->prefix . 'icl_string_positions', array(
			'string_id'        => $string_id,
			'kind'             => $kind,
			'position_in_page' => $position,
		) );
	}
}