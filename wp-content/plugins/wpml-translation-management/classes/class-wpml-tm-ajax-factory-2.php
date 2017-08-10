<?php

class WPML_TM_Ajax_Factory extends WPML_Ajax_Factory {
	
	private $wpdb;
	private $sitepress;
	private $post_data;
	private $wp_api;
	
	public function __construct( $wpdb, $sitepress, $post_data ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
		$this->post_data = $post_data;
		$this->wp_api    = $sitepress->get_wp_api();
	}

	public function get_class_names() {
		return array(
			'WPML_Ajax_Update_Link_Targets_In_Posts',
			'WPML_Ajax_Update_Link_Targets_In_Strings'
		);
	}

	public function create( $class_name ) {
		global $ICL_Pro_Translation;

		switch ( $class_name ) {
			case 'WPML_Ajax_Update_Link_Targets_In_Posts':
				return new WPML_Ajax_Update_Link_Targets_In_Posts( new WPML_Translate_Link_Target_Global_State( $this->sitepress ),
					$this->wpdb,
					$ICL_Pro_Translation,
					$this->post_data );

			case 'WPML_Ajax_Update_Link_Targets_In_Strings':
				return new WPML_Ajax_Update_Link_Targets_In_Strings( new WPML_Translate_Link_Target_Global_State( $this->sitepress ),
					$this->wpdb,
					$this->wp_api,
					$ICL_Pro_Translation,
					$this->post_data );

			default:
				throw new Exception( 'Class ' . $class_name . ' not found' );

		}
	}
}
