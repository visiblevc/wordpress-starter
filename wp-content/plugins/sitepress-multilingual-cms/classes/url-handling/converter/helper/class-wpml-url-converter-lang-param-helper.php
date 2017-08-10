<?php

class WPML_URL_Converter_Lang_Param_Helper {
	/**
	 * @var array
	 */
	private static $cache = array();

	/**
	 * @var array
	 */
	private $active_languages;

	/**
	 * @param array $active_languages
	 */
	public function __construct( array $active_languages ) {
		$this->active_languages = $active_languages;
	}

	/**
	 *
	 * @param string $url
	 * @param bool   $only_admin If set to true only language parameters on Admin Screen URLs will be recognized. The
	 *                           function will return null for non-Admin Screens.
	 *
	 * @return null|string Language code
	 */
	public function lang_by_param( $url, $only_admin = true ) {
		if ( isset( self::$cache[ $url ] ) ) {
			return self::$cache[ $url ];
		}

		$lang = $this->extract_lang_param_from_url( $url, $only_admin );

		self::$cache[ $url ] = $lang;

		return $lang;
	}

	/**
	 * @param string $url
	 * @param bool $only_admin
	 *
	 * @return string|null
	 */
	private function extract_lang_param_from_url( $url, $only_admin ) {
		$url = wpml_strip_subdir_from_url( $url );
		$url_query_parts = wpml_parse_url( $url );
		$url_query = $this->has_query_part( $only_admin, $url_query_parts ) ? untrailingslashit( $url_query_parts['query'] ) : null;

		if ( null !== $url_query ) {
			parse_str( $url_query, $vars );
			if ( $this->can_retrieve_lang_from_query( $only_admin, $vars ) ) {
				return $vars['lang'];
			}
		}

		return null;
	}

	/**
	 * @param bool $only_admin
	 * @param array $url_query_parts
	 *
	 * @return bool
	 */
	private function has_query_part( $only_admin, $url_query_parts ) {
		if ( ! isset( $url_query_parts['query'] ) ) {
			return false;
		}

		if ( false === $only_admin ) {
			return true;
		}

		if ( ! isset( $url_query_parts['path'] ) ) {
			return false;
		}

		if ( 0 !== strpos( $url_query_parts['path'], '/wp-admin' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param bool $only_admin
	 * @param array $vars
	 *
	 * @return bool
	 */
	private function can_retrieve_lang_from_query( $only_admin, $vars ) {
		if ( ! isset( $vars['lang'] ) ) {
			return false;
		}

		if ( $only_admin && 'all' === $vars['lang'] ) {
			return true;
		}

		if ( in_array( $vars['lang'], $this->active_languages, true ) ) {
			return true;
		}

		return false;
	}
}
