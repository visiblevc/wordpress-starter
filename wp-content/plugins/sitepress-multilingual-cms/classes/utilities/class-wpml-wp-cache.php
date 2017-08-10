<?php

class WPML_WP_Cache {

	private $group;

	public function __construct( $group = '' ) {
		$this->group = $group;
	}

	public function get( $key, &$found ) {

		$value = wp_cache_get( $this->get_current_key( $key ), $this->group );
		if ( is_array( $value ) && array_key_exists( 'data', $value ) ) {
			// we know that we have set something in the cache.
			$found = true;
			return $value['data'];
		} else {
			$found = false;
			return $value;
		}
	}

	public function set( $key, $value, $expire = 0 ) {
		// Save $value in an array. We need to do this because W3TC 0.9.4.1 doesn't
		// set the $found value when fetching data.
		wp_cache_set( $this->get_current_key( $key ), array( 'data' => $value ), $this->group, $expire );
	}

	/**
	 * Get specific number for group.
	 * Which later can be incremented to flush cache for group.
	 *
	 * @return int
	 */
	public function get_current_key( $key ) {
		$current_key_index = wp_cache_get( 'current_key_index', $this->group );

		if ( false === $current_key_index ) {
			$current_key_index = 1;
			wp_cache_set( 'current_key_index', $current_key_index, $this->group );
		}

		return $key . $current_key_index;
	}

	/**
	 * Increment the number stored with group name as key.
	 */
	public function flush_group_cache() {
		wp_cache_incr( 'current_key_index', 1, $this->group );
	}
}
