<?php

class WPML_ST_DB_Mappers_Strings {
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
	 * @param string $context
	 *
	 * @return array
	 */
	public function get_all_by_context( $context ) {
		$query = "
			SELECT * FROM {$this->wpdb->prefix}icl_strings
        	WHERE context=%s
		";

		$query = $this->wpdb->prepare( $query, esc_sql( $context ) );

		return $this->wpdb->get_results( $query, ARRAY_A );
	}
}