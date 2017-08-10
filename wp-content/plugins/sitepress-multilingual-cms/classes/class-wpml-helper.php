<?php

class WPML_Helper {

	/**
	 * @var WPDB
	 */
	private $wpdb;

	public function __construct( &$wpdb ) {
		$this->wpdb = &$wpdb;
	}

	public function current_user_can_translate_strings() {
		return current_user_can( 'translate' )
					 && ! current_user_can( 'manage_options' )
					 && ! current_user_can( 'manage_categories' )
					 && ! current_user_can( 'wpml_manage_string_translation' );
	}

	public function get_user_language_pairs( $current_user ) {
		return get_user_meta( $current_user->ID, $this->wpdb->prefix . 'language_pairs', true );
	}
}