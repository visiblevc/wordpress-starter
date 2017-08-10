<?php

/**
 * Class WPML_Links_Fixed_Status_For_Posts
 *
 * @package wpml-tm
 */
class WPML_Links_Fixed_Status_For_Posts extends WPML_Links_Fixed_Status {

	/* @var int $translation_id */
	private $translation_id;
	private $wpdb;
	
	public function __construct( &$wpdb, $element_id, $element_type ) {
		$this->wpdb = &$wpdb;
		
		$this->translation_id = $wpdb->get_var( $wpdb->prepare( "SELECT translation_id
														 FROM {$wpdb->prefix}icl_translations
														 WHERE element_id=%d
														 AND element_type=%s",
														 $element_id,
														 $element_type ) );
	}
	
	public function set( $status ) {
		$status = $status ? 1 : 0;
		
		$q          = "UPDATE {$this->wpdb->prefix}icl_translation_status SET links_fixed=%d WHERE translation_id=%d";
		$q_prepared = $this->wpdb->prepare( $q, array( $status, $this->translation_id ) );
		$this->wpdb->query($q_prepared);
	}
	
	public function are_links_fixed() {
		$state = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT links_fixed
														FROM {$this->wpdb->prefix}icl_translation_status
														WHERE translation_id=%d",
														$this->translation_id ) );
		return (bool) $state;
	}
	
	
}