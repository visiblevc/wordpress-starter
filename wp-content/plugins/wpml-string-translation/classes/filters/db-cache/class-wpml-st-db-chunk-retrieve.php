<?php

class WPML_ST_DB_Chunk_Retrieve {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var int
	 */
	private $chunk_size;

	/**
	 * @param wpdb $wpdb
	 * @param int $chunk_size
	 */
	public function __construct( wpdb $wpdb, $chunk_size = 1000 ) {
		$this->wpdb       = $wpdb;
		$this->chunk_size = $chunk_size;
	}

	/**
	 * @param string $query
	 * @param array $args
	 * @param int $elements_num
	 *
	 * @return array
	 */
	public function retrieve($query, $args, $elements_num) {
		$result = array();

		$offset = 0;
		while ($offset < $elements_num) {
			$new_query = $query . sprintf( ' LIMIT %d OFFSET %s', $this->chunk_size, $offset );
			$new_query = $this->wpdb->prepare($new_query, $args);
			$rowset = $this->wpdb->get_results( $new_query, ARRAY_A );

			if (is_array( $rowset ) && count($rowset)) {
				$result = array_merge( $result, $rowset );
			}

			$offset += $this->chunk_size;
		}

		return $result;
	}
}