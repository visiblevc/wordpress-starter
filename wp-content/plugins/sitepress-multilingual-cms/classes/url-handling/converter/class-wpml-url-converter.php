<?php

/**
 * Class WPML_URL_Converter
 *
 * @package    wpml-core
 * @subpackage url-handling
 *
 */

class WPML_URL_Converter {
	/**
	 * @var IWPML_URL_Converter_Strategy
	 */
	private $strategy;

	protected $default_language;
	protected $active_languages;

	/**
	 * @var WPML_URL_Converter_Url_Helper
	 */
	protected $home_url_helper;

	/**
	 * @var WPML_URL_Converter_Lang_Param_Helper
	 */
	protected $lang_param;

	/**
	 * @var WPML_Slash_Management
	 */
	protected $slash_helper;

	/**
	 * @var WPML_Resolve_Object_Url_Helper
	 */
	protected $object_url_helper;

	/**
	 * @param IWPML_URL_Converter_Strategy $strategy
	 * @param WPML_Resolve_Object_Url_Helper $object_url_helper
	 * @param $default_language
	 * @param $active_languages
	 */
	public function __construct(
		IWPML_URL_Converter_Strategy $strategy,
		WPML_Resolve_Object_Url_Helper $object_url_helper,
		$default_language,
		$active_languages
	) {
		$this->strategy = $strategy;
		$this->object_url_helper = $object_url_helper;
		$this->default_language = $default_language;
		$this->active_languages = $active_languages;

		$this->lang_param = new WPML_URL_Converter_Lang_Param_Helper( $active_languages );
		$this->slash_helper = new WPML_Slash_Management();
	}

	public function get_strategy() {
		return $this->strategy;
	}

	/**
	 * @param WPML_URL_Converter_Url_Helper $url_helper
	 */
	public function set_url_helper( WPML_URL_Converter_Url_Helper $url_helper ) {
		$this->home_url_helper = $url_helper;

		if ( $this->strategy instanceof WPML_URL_Converter_Abstract_Strategy ) {
			$this->strategy->set_url_helper( $url_helper );
		}
	}

	/**
	 * @return WPML_URL_Converter_Url_Helper
	 */
	public function get_url_helper() {
		if ( ! $this->home_url_helper ) {
			$this->home_url_helper = new WPML_URL_Converter_Url_Helper();
		}

		return $this->home_url_helper;
	}

	public function get_abs_home() {
		return $this->get_url_helper()->get_abs_home();
	}

	/**
	 * @param WPML_URL_Converter_Lang_Param_Helper $lang_param_helper
	 */
	public function set_lang_param_helper( WPML_URL_Converter_Lang_Param_Helper $lang_param_helper ) {
		$this->lang_param = $lang_param_helper;
	}

	/**
	 * @param WPML_Slash_Management $slash_helper
	 */
	public function set_slash_helper( WPML_Slash_Management $slash_helper ) {
		$this->slash_helper = $slash_helper;
	}

	/**
	 * Scope of this function:
	 * 1. Convert the home URL in the specified language depending on language negotiation:
	 *    1. Add a language directory
	 *    2. Change the domain
	 *    3. Add a language parameter
	 * 2. If the requested URL is equal to the current URL, the URI will be adapted
	 * with potential slug translations for:
	 *    - single post slugs
	 *    - taxonomy term slug
	 *
	 * WARNING: The URI slugs won't be translated for arbitrary URL (not the current one)
	 *
	 * @param $url
	 * @param bool $lang_code
	 *
	 * @return bool|mixed|string
	 */
	public function convert_url( $url, $lang_code = false ) {
		if ( ! $url ) {
			return $url;
		}

		global $sitepress;

		$new_url = false;
		if ( ! $lang_code ) {
			$lang_code = $sitepress->get_current_language();
		}
		$language_from_url  = $this->get_language_from_url( $url );

		if ( $language_from_url === $lang_code ) {
			$new_url = $url;
		} else {
			if ( $this->can_resolve_object_url( $url ) ) {
				$new_url = $this->object_url_helper->resolve_object_url( $url, $lang_code );
			}

			if ( false === $new_url ) {
				$new_url = $this->strategy->convert_url_string( $url, $lang_code );
			}
		}

		return $this->slash_helper->match_trailing_slash_to_reference( $new_url, $url );
	}

	/**
	 * Takes a URL and returns the language of the document it points at
	 *
	 * @param string $url
	 * @return string
	 */
	public function get_language_from_url( $url ) {
		if ( ! ( $language = $this->lang_param->lang_by_param( $url ) ) ) {
			$language = $this->get_strategy()->get_lang_from_url_string( $url );
		}

		return $this->get_strategy()->validate_language( $language, $url );
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	private function can_resolve_object_url( $url ) {
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		$server_name = strpos( $request_uri, '/' ) === 0
			? untrailingslashit( $server_name ) : trailingslashit( $server_name );
		$request_url = stripos( get_option( 'siteurl' ), 'https://' ) === 0
			? 'https://' . $server_name . $request_uri : 'http://' . $server_name . $request_uri;

		$is_request_url     = trailingslashit( $request_url ) === trailingslashit( $url );
		$is_home_url        = trailingslashit( $this->get_url_helper()->get_abs_home() ) === trailingslashit( $url );
		$is_home_url_filter = current_filter() === 'home_url';

		return $is_request_url && ! $is_home_url && ! $is_home_url_filter;
	}
}
