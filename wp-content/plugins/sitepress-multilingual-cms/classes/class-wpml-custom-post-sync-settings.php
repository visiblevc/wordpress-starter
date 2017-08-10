<?php


class WPML_custom_post_sync_settings extends WPML_SP_User {

	private $settings;
	
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
		
		$this->settings = $sitepress->get_setting( 'custom_posts_sync_option', array( ) );
	}
	
	public function is_sync( $type ) {
		return isset( $this->settings[ $type ] ) && $this->settings[ $type ] == 1;
	}
}