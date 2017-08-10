<?php

class WPML_URL_Cached_Converter extends WPML_URL_Converter {

	/** @var  string[] $cache */
	private $cache;

	/**
	 * @param string $url
	 * @param string|bool $lang_code
	 *
	 * @return string
	 */
	public function convert_url( $url, $lang_code = false ) {
		global $sitepress;

		if ( ! $lang_code ) {
			$lang_code = $sitepress->get_current_language();
		}
		$negotiation_type = $sitepress->get_setting( 'language_negotiation_type' );

		$cache_key_args = array( $url, $lang_code, $negotiation_type );
		$cache_key      = md5( wp_json_encode( $cache_key_args ) );
		$cache_group    = 'convert_url';
		$cache_found    = false;
		$cache          = new WPML_WP_Cache( $cache_group );

		$new_url        = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found ) {
			$new_url = parent::convert_url( $url, $lang_code );
			$cache->set( $cache_key, $new_url );
		}

		return $new_url;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function get_language_from_url( $url ) {
		if ( isset( $this->cache[ $url ] ) ) {
			return $this->cache[ $url ];
		}

		$lang = parent::get_language_from_url( $url );

		$this->cache[ $url ] = $lang;

		return $lang;
	}
}
