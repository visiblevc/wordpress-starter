<?php

class WPML_URL_Converter_Domain_Strategy extends WPML_URL_Converter_Abstract_Strategy {

	/** @var string[] $domains */
	private $domains = array();

	/**
	 * @param array       $domains
	 * @param string      $default_language
	 * @param array       $active_languages
	 */
	public function __construct(
		$domains,
		$default_language,
		$active_languages
	) {
		parent::__construct( $default_language, $active_languages );

		$this->domains = $this->strip_protocol( array_map( 'trailingslashit', $domains ) );
		if ( isset( $this->domains[ $default_language ] ) ) {
			unset( $this->domains[ $default_language ] );
		}
	}

	public function get_lang_from_url_string( $url ) {
		$url = $this->strip_protocol( $url );

		if ( strpos( $url, '?' ) ) {
			$parts = explode( '?', $url );
			$url   = $parts[0];
		}

		foreach ( $this->domains as $code => $domain ) {
			if ( strpos( trailingslashit( $url ), $domain ) === 0 ) {
				return $code;
			}
		}

		return null;
	}

	public function convert_url_string( $source_url, $lang ) {
		$original_source_url = untrailingslashit( $source_url );
		if ( is_admin() && $this->get_url_helper()->is_url_admin( $original_source_url ) ) {
			return $original_source_url;
		}

		$base_url = isset( $this->domains[ $lang ] ) ? $this->domains[ $lang ] : $this->get_url_helper()->get_abs_home();
		$base_url = trailingslashit( $base_url );
		$base_url = preg_replace(
			array( '#^(http(?:s?))://#', '#(\w/).+$#' ),
			array( '', '$1' ),
			$base_url
		);

		$original_source_url = strpos( $original_source_url, '?' ) !== false
			? $original_source_url
			: trailingslashit( $original_source_url );

		$converted_url = preg_replace(
			'#^(https?://)?([^\/]*)\/?#',
			'${1}' . $base_url,
			$original_source_url
		);

		return $this->slash_helper->maybe_user_trailingslashit( $converted_url, 'untrailingslashit' );
	}

	/**
	 * @param array|string $url
	 *
	 * @return array|string
	 */
	private function strip_protocol( $url ) {
		return preg_replace( '#^(http(?:s?))://#', '', $url );
	}
}