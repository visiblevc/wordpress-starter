<?php

class WPML_URL_Converter_Parameter_Strategy extends WPML_URL_Converter_Abstract_Strategy {

	public function get_lang_from_url_string( $url ) {
		return $this->lang_param->lang_by_param( $url, false );
	}

	public function convert_url_string( $source_url, $lang_code ) {
		if ( ! $lang_code ) {
			$lang_code = $this->default_language;
		}
		if ( $lang_code === $this->default_language ) {
			$lang_code = '';
		}

		$source_url = $this->fix_query_structure( $source_url );

		$source_url = $this->fix_trailingslashit( $source_url );

		$query = wpml_parse_url( $source_url, PHP_URL_QUERY );
		if ( false !== strpos( $query, 'lang=' ) ) {
			$source_url = remove_query_arg( 'lang', $source_url );
		}

		if ( ! empty( $lang_code ) ) {
			$source_url = add_query_arg( 'lang', $lang_code, $source_url );
		}

		return  $source_url ;
	}

	/**
	 * Replace double ? to &
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	private function fix_query_structure( $url ) {
		$query = wpml_parse_url( $url, PHP_URL_QUERY );
		$new_query = str_replace( '?', '&', $query );

		return str_replace( $query, $new_query, $url );
	}

	/**
	 * @param $source_url
	 *
	 * @return mixed|string
	 */
	private function fix_trailingslashit( $source_url ) {
		$query = wpml_parse_url( $source_url, PHP_URL_QUERY );
		if ( ! empty( $query ) ) {
			$source_url = str_replace( '?' . $query, '', $source_url );
		}

		$source_url = $this->slash_helper->maybe_user_trailingslashit( $source_url, 'trailingslashit' );

		if ( ! empty( $query ) ) {
			$source_url .= '?' . $query;
		}

		return $source_url;
	}
}