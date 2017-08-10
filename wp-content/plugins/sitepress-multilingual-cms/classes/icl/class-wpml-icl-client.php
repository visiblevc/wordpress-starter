<?php

/**
 * @author OnTheGo Systems
 */
class WPML_ICL_Client {
	private $error;
	/** @var WP_HTTP $http */
	private $http;
	/** @var  WPML_WP_API $wp_api */
	private $wp_api;
	private $method  = 'GET';
	private $post_data;

	/**
	 * WPML_ICL_Client constructor.
	 *
	 * @param WP_HTTP $http
	 * @param WPML_WP_API
	 */
	public function __construct( $http, $wp_api ) {
		$this->http   = $http;
		$this->wp_api = $wp_api;
	}

	function request( $request_url ) {

		$results     = false;
		$this->error = false;

		$request_url = $this->get_adjusted_request_url( $request_url );

		$this->adjust_post_data();

		if ( 'GET' === $this->method ) {
			$result = $this->http->get( $request_url );
		} else {
			$result = $this->http->post( $request_url, array( 'body' => $this->post_data ) );
		}

		if ( is_wp_error( $result ) ) {
			$this->error = $result->get_error_message();
		} else {

			$results = icl_xml2array( $result['body'], 1 );

			if ( array_key_exists( 'info', $results ) && '-1' === $results['info']['status']['attr']['err_code'] ) {
				$this->error = $results['info']['status']['value'];

				$results = false;
			}
		}

		return $results;
	}

	public function get_error() {
		return $this->error;
	}

	/**
	 * @return array
	 */
	private function get_debug_data() {
		$debug_vars = array(
			'debug_cms'    => 'WordPress',
			'debug_module' => 'WPML ' . $this->wp_api->constant( 'ICL_SITEPRESS_VERSION' ),
			'debug_url'    => $this->wp_api->get_bloginfo( 'url' ),
		);

		return $debug_vars;
	}

	/**
	 * @param $request_url
	 *
	 * @return mixed|string
	 */
	private function get_adjusted_request_url( $request_url ) {
		$request_url = str_replace( ' ', '%20', $request_url );

		if ( 'GET' === $this->method ) {
			$request_url .= '&' . http_build_query( $this->get_debug_data() );
		}

		return $request_url;
	}

	private function adjust_post_data() {
		if ( 'GET' !== $this->method ) {
			$this->post_data = array_merge( $this->post_data, $this->get_debug_data() );
		}
	}

	/**
	 * @param $method
	 */
	public function set_method( $method ) {
		$this->method = $method;
	}

	public function set_post_data( $post_data ) {
		$this->post_data = $post_data;
	}

}
