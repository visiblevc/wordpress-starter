<?php


class WPML_post_slug_translation_settings extends WPML_SP_User {

	private $settings;
	
	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
		
		$this->settings = $sitepress->get_setting( 'posts_slug_translation', array( ) );
	}
	
	public function is_on( ) {
		return isset( $this->settings[ 'on' ] ) && $this->settings[ 'on' ];
	}
	
	public function is_translate( $type ) {
		return ! empty( $this->settings[ 'types' ][ $type ] );
	}
}