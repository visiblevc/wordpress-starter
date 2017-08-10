<?php

/**
 * Class WPML_Links_Fixed_Status_Factory
 *
 * @package wpml-translation-management
 */
class WPML_Links_Fixed_Status_Factory extends WPML_WPDB_User {

	private $wp_api;
	
	public function __construct( &$wpdb, $wp_api ) {
		parent::__construct( $wpdb );
		
		$this->wp_api = $wp_api;
	}
	
	public function create( $element_id, $element_type ) {
		$links_fixed_status = null;
		
		if(strpos($element_type, 'post') === 0){
			$links_fixed_status = new WPML_Links_Fixed_Status_For_Posts( $this->wpdb, $element_id, $element_type );
		}elseif($element_type=='string'){
			$links_fixed_status = new WPML_Links_Fixed_Status_For_Strings( $this->wp_api, $element_id );
		}
		
		return $links_fixed_status;
	}
	
	
}