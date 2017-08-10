<?php

class WPML_Cache_Factory {

	private $valid_caches = array(
		'TranslationManagement::get_translation_job_id' =>
			array( 'clear_actions' => array( 'wpml_tm_save_post', 'wpml_cache_clear' ) ),

		'WPML_Element_Type_Translation::get_language_for_element' =>
			array( 'clear_actions' => array( 'wpml_translation_update' ) ),

		'WPML_Post_Status::needs_update' =>
			array( 'clear_actions' => array( 'wpml_translation_status_update' ) ),

	);

	public function __construct() {
		foreach ( $this->valid_caches as $clear_actions ) {
			foreach ( $clear_actions['clear_actions'] as $clear_action ) {
				if ( ! has_action( $clear_action, array( $this, 'action_handler' ) ) ) {
					add_action( $clear_action, array( $this, 'action_handler' ) );
				}
			}
		}
	}

	public function get( $cache_name ) {
		if ( isset( $this->valid_caches[ $cache_name] ) ) {
			return new WPML_WP_Cache( $cache_name );
		} else {
			throw new InvalidArgumentException( $cache_name . ' is not a valid cache for the WPML_Cache_Factory' );
		}
	}

	public function action_handler() {
		$current_action = current_filter();
		foreach ( $this->valid_caches as $cache_name => $clear_actions ) {
			foreach ( $clear_actions['clear_actions'] as $clear_action ) {
				if ( $current_action == $clear_action ) {
					$cache = new WPML_WP_Cache( $cache_name );
					$cache->flush_group_cache();
				}
			}
		}
	}

}