<?php

abstract class WPML_URL_Converter_Abstract_Strategy implements IWPML_URL_Converter_Strategy {
	protected $absolute_home;

	protected $default_language;
	protected $active_languages;

	protected $cache;

	/**
	 * @var WPML_URL_Converter_Url_Helper
	 */
	protected $url_helper;

	/**
	 * @var WPML_URL_Converter_Lang_Param_Helper
	 */
	protected $lang_param;

	/**
	 * @var WPML_Slash_Management
	 */
	protected $slash_helper;

	/**
	 * @var WP_Rewrite
	 */
	protected $wp_rewrite;

	/**
	 * @param $default_language
	 * @param $active_languages
	 * @param WP_Rewrite $wp_rewrite
	 */
	public function __construct( $default_language, $active_languages, $wp_rewrite = null ) {
		$this->default_language = $default_language;
		$this->active_languages = $active_languages;

		$this->lang_param = new WPML_URL_Converter_Lang_Param_Helper( $active_languages );
		$this->slash_helper = new WPML_Slash_Management();

		if ( ! $wp_rewrite ) {
			global $wp_rewrite;
		}
		$this->wp_rewrite = $wp_rewrite;
	}

	public function validate_language( $language, $url ) {
		return in_array( $language, $this->active_languages, true )
		       || 'all' === $language && $this->get_url_helper()->is_url_admin( $url ) ? $language : $this->get_default_language();
	}

	/**
	 * @param WPML_URL_Converter_Url_Helper $url_helper
	 */
	public function set_url_helper( WPML_URL_Converter_Url_Helper $url_helper ) {
		$this->url_helper = $url_helper;
	}

	/**
	 * @return WPML_URL_Converter_Url_Helper
	 */
	public function get_url_helper() {
		if ( ! $this->url_helper ) {
			$this->url_helper = new WPML_URL_Converter_Url_Helper();
		}

		return $this->url_helper;
	}

	/**
	 * @param WPML_URL_Converter_Lang_Param_Helper $lang_param
	 */
	public function set_lang_param( WPML_URL_Converter_Lang_Param_Helper $lang_param ) {
		$this->lang_param = $lang_param;
	}

	/**
	 * @param WPML_Slash_Management $slash_helper
	 */
	public function set_slash_helper( WPML_Slash_Management $slash_helper ) {
		$this->slash_helper = $slash_helper;
	}

	private function get_default_language() {
		if ( $this->default_language ) {
			return $this->default_language;
		} else {
			return icl_get_setting( 'default_language' );
		}
	}
}