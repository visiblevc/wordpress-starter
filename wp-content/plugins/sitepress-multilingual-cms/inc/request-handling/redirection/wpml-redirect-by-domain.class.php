<?php

class WPML_Redirect_By_Domain extends WPML_Redirection {

	/** @var array $domains */
	private $domains;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	/**
	 * @param array                    $domains
	 * @param WPML_WP_API              $wp_api
	 * @param WPML_URL_Converter       $url_converter
	 * @param WPML_Request             $request_handler
	 * @param WPML_Language_Resolution $lang_resolution
	 */
	public function __construct( $domains, &$wp_api, &$request_handler, &$url_converter, &$lang_resolution ) {
		parent::__construct( $url_converter, $request_handler, $lang_resolution );
		$this->domains = $domains;
		$this->wp_api  = &$wp_api;
	}

	public function get_redirect_target( $language = false ) {
		if ( $this->wp_api->is_admin() && $this->lang_resolution->is_language_hidden( $language )
			&& strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) === false
			&& ! $this->wp_api->user_can( wp_get_current_user(), 'manage_options' )
			) {
			$target = trailingslashit( $this->domains[ $language ] ) . 'wp-login.php';
		} else {
			$target = $this->redirect_hidden_home();
		}

		return $target;
	}
}