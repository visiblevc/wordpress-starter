<?php

class WPML_ST_String_Factory {
	private $wpdb;

	/**
	 * WPML_ST_String_Factory constructor.
	 *
	 * @param WPDB $wpdb
	 */
	public function __construct( WPDB $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/** @var int[] $string_id_cache */
	private $string_id_cache = array();

	/** @var WPML_ST_String $string_cache */
	private $string_cache = array();

	/**
	 * @param int $string_id
	 *
	 * @return WPML_ST_String
	 */
	public function find_by_id( $string_id ) {
		$this->string_cache[ $string_id ] = isset( $this->string_cache[ $string_id ] )
			? $this->string_cache[ $string_id ] : new WPML_ST_String( $string_id, $this->wpdb );

		return $this->string_cache[ $string_id ];
	}

	/**
	 * @param string $name
	 *
	 * @return WPML_ST_String
	 */
	public function find_by_name( $name ) {
		$sql                                 = $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE name=%s LIMIT 1", $name );
		$cache_key                           = md5( $sql );
		$this->string_id_cache[ $cache_key ] = isset( $this->string_id_cache[ $cache_key ] )
			? $this->string_id_cache[ $cache_key ]
			: (int) $this->wpdb->get_var( $sql );
		$string_id                           = $this->string_id_cache[ $cache_key ];
		$this->string_cache[ $string_id ]    = isset( $this->string_cache[ $string_id ] )
			? $this->string_cache[ $string_id ] : new WPML_ST_String( $string_id, $this->wpdb );

		return $this->string_cache[ $this->string_id_cache[ $cache_key ] ];
	}

	/**
	 * @param string $name
	 *
	 * @return WPML_ST_Admin_String
	 */
	public function find_admin_by_name( $name ) {
		$sql       = $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE name=%s LIMIT 1", $name );
		$string_id = (int) $this->wpdb->get_var( $sql );
		return new WPML_ST_Admin_String( $string_id, $this->wpdb );
	}

	/**
	 * @param string     $string
	 * @param string     $context
	 * @param bool|false $name
	 *
	 * @return mixed
	 */
	public function get_string_id( $string, $context, $name = false ) {
		$sql          = "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE value=%s AND context=%s";
		$prepare_args = array( $string, $context );
		if ( $name !== false ) {
			$sql .= " AND name = %s ";
			$prepare_args[] = $name;
		}
		$sql                                 = $this->wpdb->prepare( $sql . " LIMIT 1", $prepare_args );
		$cache_key                           = md5( $sql );
		$this->string_id_cache[ $cache_key ] = isset( $this->string_id_cache[ $cache_key ] )
			? $this->string_id_cache[ $cache_key ]
			: (int) $this->wpdb->get_var( $sql );

		return $this->string_id_cache[ $cache_key ];
	}
}