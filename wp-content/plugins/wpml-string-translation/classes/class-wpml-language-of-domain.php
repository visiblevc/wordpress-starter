<?php

class WPML_Language_Of_Domain {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var array
	 */
	private $language_of_domain = array();

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
		
		$string_settings = $this->sitepress->get_setting( 'st' );
		
		if ( isset( $string_settings[ 'lang_of_domain' ] ) ) {
			$this->language_of_domain = $string_settings[ 'lang_of_domain' ];
		}
		
	}
	
	public function get_language( $domain ) {

		$lang = null;
		if ( isset( $this->language_of_domain[ $domain ] ) ) {
			$lang = $this->language_of_domain[ $domain ];
		}
		
		return $lang;
	}

	public function set_language( $domain, $lang ) {
		$this->language_of_domain[ $domain ] = $lang;
		$string_settings = $this->sitepress->get_setting( 'st' );
		$string_settings[ 'lang_of_domain' ] = $this->language_of_domain;
		$this->sitepress->set_setting( 'st', $string_settings, true );
	}
}