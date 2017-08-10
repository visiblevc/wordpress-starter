<?php

interface IWPML_URL_Converter_Strategy {

	public function convert_url_string( $source_url, $lang );

	public function validate_language( $language, $url );

	public function get_lang_from_url_string( $url );
}