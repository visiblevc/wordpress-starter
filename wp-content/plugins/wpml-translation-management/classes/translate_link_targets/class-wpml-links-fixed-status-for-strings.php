<?php

/**
 * Class WPML_Links_Fixed_Status_For_Posts
 *
 * @package wpml-tm
 */
class WPML_Links_Fixed_Status_For_Strings extends WPML_Links_Fixed_Status {

	private $wp_api;
	private $string_id;
	private $option_name = 'wpml_strings_need_links_fixed';
	
	public function __construct( &$wp_api, $string_id ) {
		$this->wp_api = &$wp_api;
		$this->string_id = $string_id;		
	}
	
	public function set( $status ) {
		if ( $status ) {
			$this->remove_string_from_strings_that_need_fixing();
		} else {
			$this->add_string_to_strings_that_need_fixing();
		}
	}
	
	public function are_links_fixed() {
		$strings_that_need_links_fixed = $this->load_strings_that_need_fixing();
		return array_search($this->string_id, $strings_that_need_links_fixed) === false;
	}

	private function remove_string_from_strings_that_need_fixing() {	
		$strings_that_need_links_fixed = $this->load_strings_that_need_fixing();

		if( ( $key = array_search( $this->string_id, $strings_that_need_links_fixed ) ) !== false ) {
			unset( $strings_that_need_links_fixed[ $key ] );
		}			
	
		$this->save_strings_that_need_fixing( $strings_that_need_links_fixed );
	}

	private function add_string_to_strings_that_need_fixing() {	
		$strings_that_need_links_fixed = $this->load_strings_that_need_fixing();

		if( ( array_search( $this->string_id, $strings_that_need_links_fixed ) ) === false ) {
			$strings_that_need_links_fixed[] = $this->string_id;
		}
	
		$this->save_strings_that_need_fixing( $strings_that_need_links_fixed );
	}
	
	private function load_strings_that_need_fixing() {
		return $this->wp_api->get_option( $this->option_name, array() );
	}
	
	private function save_strings_that_need_fixing( $strings_that_need_links_fixed ) {
		$this->wp_api->update_option( $this->option_name, $strings_that_need_links_fixed );
	}
	
}