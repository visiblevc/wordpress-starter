<?php

class WPML_ST_Package_Cleanup {

	/** @var  WPDB */
	private $wpdb;

	private $existing_strings_in_package = array();

	public function __construct( WPDB $wpdb) {
		$this->wpdb = $wpdb;
	}

	public function record_existing_strings( WPML_Package $package ) {
		$strings = $package->get_package_strings();
		$this->existing_strings_in_package[ $package->ID ] = array();
		if( $strings ) {
			foreach ( $strings as $string ) {
				$this->existing_strings_in_package[ $package->ID ][ $string->id ] = $string;
			}
		}
	}

	public function record_register_string( WPML_Package $package, $string_id ) {
		unset( $this->existing_strings_in_package[ $package->ID ][ $string_id ] );
	}

	public function delete_unused_strings( WPML_Package $package ) {
		if( array_key_exists( $package->ID, $this->existing_strings_in_package ) ){
			foreach ( $this->existing_strings_in_package[ $package->ID ] as $string_data ) {
				icl_unregister_string( $package->get_string_context_from_package(), $string_data->name );
				$field_type = 'package-string-' . $package->ID . '-' . $string_data->id;
				$this->wpdb->delete( $this->wpdb->prefix . 'icl_translate', array( 'field_type' => $field_type ), array( '%s' ) );
			}
		}
	}
}