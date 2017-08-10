<?php

/** NOTE:
 *  Use the $wpml_post_translations or $wpml_term_translations globals for posts and taxonomy
 *  They are more efficient
 */

class WPML_Element_Type_Translation {

	/** @var WPDB $wpdb */
	private $wpdb;
	/** @var  WPML_Cache_Factory $cache_factory */
	private $cache_factory;
	/** @var  string $element_type */
	private $element_type;

	public function __construct( WPDB $wpdb, WPML_Cache_Factory $cache_factory, $element_type ) {
		$this->wpdb = $wpdb;
		$this->cache_factory = $cache_factory;
		$this->element_type = $element_type;
	}

	function get_element_lang_code( $element_id ) {

		$cache_key_array = array( $element_id, $this->element_type );
		$cache_key       = md5( serialize( $cache_key_array ) );
		$cache_group     = 'WPML_Element_Type_Translation::get_language_for_element';
		$cache_found     = false;

		$cache  = $this->cache_factory->get( $cache_group );
		$result = $cache->get( $cache_key, $cache_found );
		if ( ! $cache_found ) {

			$language_for_element_prepared = $this->wpdb->prepare(
				"SELECT language_code 
				FROM {$this->wpdb->prefix}icl_translations
				WHERE element_id=%d
				AND element_type=%s
				LIMIT 1",
				array( $element_id, $this->element_type )
			);

			$result = $this->wpdb->get_var( $language_for_element_prepared );

			if ( $result ) {
				$cache->set( $cache_key, $result );
			}
		}

		return $result;

	}

}