<?php

/**
 * Class WPML_Frontend_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Frontend_Request extends WPML_Request {

	/**
	 * WPML_Frontend_Request constructor.
	 *
	 * @param WPML_URL_Converter $url_converter
	 * @param array $active_languages
	 * @param string $default_language
	 * @param WPML_Cookie $cookie
	 * @param WPML_WP_API$wp_api
	 */
	public function __construct( &$url_converter, $active_languages, $default_language, $cookie, $wp_api ) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookie, $wp_api );
	}

	public function get_requested_lang() {
		return $this->wp_api->is_comments_post_page() ? $this->get_comment_language() : $this->get_request_uri_lang();
	}

	protected function get_cookie_name() {

		return '_icl_current_language';
	}
}