<?php

class WPML_Allowed_Redirect_Hosts extends WPML_SP_User {

	public function __construct( &$sitepress ) {
		parent::__construct( $sitepress );
	}
	
	public function get_hosts( $hosts ) {
		$domains          = $this->sitepress->get_setting( 'language_domains' );
		$default_language = $this->sitepress->get_default_language();
		$default_home     = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_home_url(), $default_language );
		$home_schema      = wpml_parse_url( $default_home, PHP_URL_SCHEME ) . '://';

		if ( ! isset( $domains[ $default_language ] ) ) {
			$domains[ $default_language ] = wpml_parse_url( $default_home, PHP_URL_HOST );
		}

		$active_languages = $this->sitepress->get_active_languages();

		foreach ( $domains as $code => $url ) {
			if ( !empty( $active_languages[ $code ] ) ) {
				$url = $home_schema . $url;
				$parts = wpml_parse_url( $url );
				if ( isset($parts[ 'host' ]) && !in_array( $parts[ 'host' ], $hosts ) ) {
					$hosts[ ] = $parts[ 'host' ];
				}
			}
		}
		
		return $hosts;
	}
}