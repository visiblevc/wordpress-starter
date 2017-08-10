<?php

class WPML_Language_Domain_Validation {
	const VALIDATE_DOMAIN_KEY = '____icl_validate_domain';

	/** @var WPML_WP_API $wp_api */
	private $wp_api;
	/** @var WP_Http $http */
	private $http;
	/** @var  string $url */
	private $url;
	/** @var  string $validation_url */
	private $validation_url;

	/**
	 * @param WPML_WP_API $wp_api
	 * @param WP_Http     $http
	 * @param string      $url
	 *
	 * @throws \InvalidArgumentException
	 */

	public function __construct( $wp_api, $http, $url ) {
		$this->wp_api         = $wp_api;
		$this->http           = $http;
		$this->url            = $url;
		$this->validation_url = $this->get_validation_url();
		if ( ! $this->has_scheme_and_host() ) {
			throw new InvalidArgumentException( 'Invalid URL :' . $this->url );
		}
	}

	/**
	 * @return bool
	 */
	public function is_valid() {
		if( is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
			return true;
		}

		$response = $this->get_validation_response();

		if ( $this->is_valid_response( $response ) ) {
			return in_array( $response['body'], $this->get_accepted_responses( $this->validation_url ), true );
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function has_scheme_and_host() {
		$url_parts = wpml_parse_url( $this->url );
		return array_key_exists( 'scheme', $url_parts ) && array_key_exists( 'host', $url_parts );
	}

	/**
	 * @return string
	 */
	private function get_validation_url() {
		return add_query_arg( array( self::VALIDATE_DOMAIN_KEY => 1 ), trailingslashit( $this->url ) );
	}

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	private function get_accepted_responses( $url ) {
		$accepted_responses = array(
			'<!--' . untrailingslashit( $this->wp_api->get_home_url() ) . '-->',
			'<!--' . untrailingslashit( $this->wp_api->get_site_url() ) . '-->',
		);
		if ( defined( 'SUNRISE' ) && SUNRISE === 'on' ) {
			$accepted_responses[] = '<!--' . str_replace( '?' . self::VALIDATE_DOMAIN_KEY . '=1', '', $url ) . '-->';
			return $accepted_responses;
		}
		return $accepted_responses;
	}

	/**
	 * @return array|WP_Error
	 */
	private function get_validation_response() {
		return $this->http->request( $this->validation_url, 'timeout=15' );
	}

	/**
	 * @param array|WP_Error $response
	 *
	 * @return bool
	 */
	private function is_valid_response( $response ) {
		return ! is_wp_error( $response ) && '200' === (string) $response['response']['code'];
	}
}
