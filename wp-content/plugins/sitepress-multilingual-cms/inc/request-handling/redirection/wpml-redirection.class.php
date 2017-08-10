<?php

abstract class WPML_Redirection extends WPML_URL_Converter_User {

	/** @var  WPML_Request $request_handler */
	protected $request_handler;

	/** @var WPML_Language_Resolution $lang_resolution */
	protected $lang_resolution;

	/**
	 * @param WPML_URL_Converter       $url_converter
	 * @param WPML_Request             $request_handler
	 * @param WPML_Language_Resolution $lang_resolution
	 */
	function __construct( &$url_converter, &$request_handler, &$lang_resolution ) {
		parent::__construct( $url_converter );
		$this->request_handler = $request_handler;
		$this->lang_resolution = $lang_resolution;
	}

	public abstract function get_redirect_target();

	protected function redirect_hidden_home() {
		$target = false;
		if ( $this->lang_resolution->is_language_hidden( $this->request_handler->get_request_uri_lang() )
		     && ! $this->request_handler->show_hidden()
		) {
			$target = $this->url_converter->get_abs_home();
		}

		return $target;
	}
}