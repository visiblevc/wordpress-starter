<?php

/**
 * Class WPML_URL_Converter_User
 *
 * @since 3.2.3
 */
abstract class WPML_URL_Converter_User {

	/** @var  WPML_URL_Converter */
	protected $url_converter;

	/**
	 * @param $url_converter
	 */
	public function __construct( &$url_converter ) {
		$this->url_converter = &$url_converter;
	}

}