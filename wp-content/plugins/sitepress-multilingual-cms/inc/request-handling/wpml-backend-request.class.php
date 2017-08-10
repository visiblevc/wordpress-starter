<?php

/**
 * Class WPML_Backend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Backend_Request extends WPML_Request {

	/**
	 * @param WPML_URL_Converter $url_converter
	 * @param array              $active_languages
	 * @param string             $default_language
	 * @param WPML_Cookie        $cookie
	 * @param WPML_WP_API        $wp_api
	 */
	public function __construct( &$url_converter, $active_languages, $default_language, $cookie, $wp_api ) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookie, $wp_api );
		global $wpml_url_filters;

		if ( strpos( (string) filter_var( $_SERVER['REQUEST_URI'] ), 'wpml_root_page=1' ) !== false
		     || $wpml_url_filters->frontend_uses_root() !== false
		) {
			WPML_Root_Page::init();
		}
	}

	public function check_if_admin_action_from_referer() {
		$referer = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '';

		return strpos( $referer, strtolower( '/wp-admin/' ) ) !== false;
	}

	private function force_default() {
		return isset( $_GET[ 'page' ] )
			   && ( ( defined( 'WPML_ST_FOLDER' )
					  && $_GET[ 'page' ] === WPML_ST_FOLDER . '/menu/string-translation.php' )
					|| ( defined( 'WPML_TM_FOLDER' )
						 && $_GET[ 'page' ] === WPML_TM_FOLDER . '/menu/translations-queue.php' ) );
	}

	private function get_ajax_request_lang() {
		$al   = $this->active_languages;
		$lang = isset( $_POST['lang'] ) && in_array( $_POST['lang'], $al, true ) ? sanitize_text_field( $_POST['lang'] ) : null;
		$lang = null === $lang ? ( $cookie_lang = $this->get_cookie_lang() ) : $lang;
		$lang = null === $lang && isset( $_SERVER[ 'HTTP_REFERER' ] )
			? $this->url_converter->get_language_from_url( $_SERVER[ 'HTTP_REFERER' ] ) : $lang;
		$lang = $lang ? $lang : ( isset( $cookie_lang ) ? $cookie_lang : $this->get_cookie_lang() );
		$lang = $lang ? $lang : $this->default_language;

		return $lang;
	}

	/**
	 * Determines the requested language in the WP Admin backend from URI, $_POST, $_GET and cookies.
	 *
	 * @return string requested language code
	 */
	public function get_requested_lang() {
		/**
		 * @var WPML_Language_Resolution $wpml_language_resolution
		 * @var WPML_Post_Translation    $wpml_post_translations
		 */
		global $wpml_language_resolution, $wpml_post_translations;

		$url_lang_param = '';
		if ( isset( $_GET[ 'lang' ] ) ) {
			$url_lang_param = filter_var( $_GET[ 'lang' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		$icl_post_language = '';
		if ( isset( $_POST[ 'icl_post_language' ] ) ) {
			$icl_post_language = filter_var( $_POST[ 'icl_post_language' ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		if ( $this->force_default() === true ) {
			$lang = $this->default_language;
		} elseif ( $url_lang_param
				   && $wpml_language_resolution->is_language_active( $url_lang_param, true )
		) {
			$lang = $url_lang_param;
		} elseif ( wpml_is_ajax() ) {
			$lang = $this->get_ajax_request_lang();
		} elseif ( $icl_post_language
				   && $wpml_language_resolution->is_language_active( $icl_post_language )
		) {
			$lang = $icl_post_language;
		} elseif ( isset( $_GET[ 'p' ] )
				   && ( $p = (int) $_GET[ 'p' ] ) > 0
				   && (bool) ( $posts_lang = $wpml_post_translations->get_element_lang_code( $p ) ) === true
		) {
			$lang = $posts_lang;
		} else {
			$lang = $this->get_cookie_lang();
		}

		return $lang;
	}

	protected function get_cookie_name() {

		return wpml_is_ajax() && $this->check_if_admin_action_from_referer() === false
			? '_icl_current_language' : '_icl_current_admin_language_' . md5( $this->get_cookie_domain() );
	}
}