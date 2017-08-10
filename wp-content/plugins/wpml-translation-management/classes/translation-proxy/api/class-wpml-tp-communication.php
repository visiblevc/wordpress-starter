<?php

class WPML_TP_Communication {
	private $http_transport;
	private $path;
	private $method          = 'GET';
	private $request_format  = 'json';
	private $response_format = 'json';
	private $must_respond    = false;

	/**
	 * WPML_TP_Communication constructor.
	 *
	 * @param string      $proxy_url
	 * @param WP_Http     $http_transport
	 */
	public function __construct( $proxy_url, WP_Http $http_transport = null ) {
		$this->proxy_url = $proxy_url;
		if ( null === $http_transport ) {
			$http_transport = new WP_Http();
		}
		$this->http_transport = $http_transport;
	}

	public function request_must_respond( $expected ) {
		$this->must_respond = $expected;
	}

	/**
	 * @param string $format
	 */
	public function set_request_format( $format = 'json' ) {
		$this->request_format = $format;
	}

	/**
	 * @param string $format
	 */
	public function set_response_format( $format = 'json' ) {
		$this->response_format = $format;
	}

	function projects( $params ) {
		$this->path   = '/projects';

		return $this->send( $params );
	}

	/**
	 * @param array $params
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	private function send( $params ) {
		$url = $this->build_url();
		if ( ! $url ) {
			throw new InvalidArgumentException( 'Empty target URL given!' );
		}

		$response = null;

		if ( $params ) {
			$url = $this->add_parameters_to_url( $url, $params );
			if ( $this->method === 'GET' ) {
				$url = $this->add_params_to_query_string( $url, $params );
			}
		}

		$context      = $this->filter_request_params( $params, $this->method );
		$api_response = $this->http_transport->request( esc_url_raw( $url ), $context );
		$this->handle_request_exceptions( $api_response );

		if ( $this->must_respond ) {
			$api_response = $this->validate_response_content_type( $api_response );

			$content_type = $api_response['headers']['content-type'];
			$response     = $api_response['body'];

			if ( false !== strpos( $content_type, 'zip' ) ) {
				$response = gzdecode( $api_response['body'] );
			}

			if ( 'json' === $this->response_format ) {
				$response = json_decode( $response );
			}
		}

		return $response;
	}

	private function handle_request_exceptions( $response ) {
		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			throw new RuntimeException( 'Cannot communicate with the remote service: (' . $response->get_error_code() . ') ' . $response->get_error_message() . '.' );
		}

		if ( $this->get_response_code( $response ) > 400 ) {
			throw new RuntimeException( 'Cannot communicate with the remote service: (' . $response['response']['code'] . ').' );
			/** @todo: see if we can include a message as well */
		}

		if ( $this->must_respond && ! $response ) {
			throw new RuntimeException( 'Request got no response.' );
			/** @todo: see if we can include more details */
		}
	}

	private function get_response_code( $data ) {
		$response = $this->get_response( $data );

		return $response && array_key_exists( 'code', $response ) ? (int) $response['code'] : 0;
	}

	private function get_response( $data ) {
		return $data && array_key_exists( 'response', $data ) ? $data['response'] : null;
	}

	/**
	 * @param array  $params request parameters
	 * @param string $method HTTP request method
	 *
	 * @return array
	 */
	private function filter_request_params( $params, $method ) {
		$request        = array(
			'method'    => $method,
			'body'      => $params,
			'sslverify' => false,
			'timeout'   => 60,

		);
		$request_filter = new WPML_TP_HTTP_Request_Filter( $request );

		return $request_filter->out();
	}

	private function add_parameters_to_url( $url, $params ) {
		if ( preg_match_all( '/\{.+?\}/', $url, $symbs ) ) {
			foreach ( $symbs[0] as $symb ) {
				$without_braces = preg_replace( '/\{|\}/', '', $symb );
				if ( preg_match_all( '/\w+/', $without_braces, $indexes ) ) {
					foreach ( $indexes[0] as $index ) {
						if ( array_key_exists( $index, $params ) ) {
							$value = $params[ $index ];
							$url   = preg_replace( preg_quote( "/$symb/" ), $value, $url );
						}
					}
				}
			}
		}

		return $url;
	}

	/**
	 * @return string
	 */
	private function build_url() {
		$url = '';
		if ( $this->proxy_url && $this->path ) {
			$url = http_build_url( $this->proxy_url, array( 'path' => $this->path . '.' . $this->request_format ) );
		}

		return $url;
	}

	/**
	 * @param string $url
	 * @param array  $params
	 *
	 * @return string
	 */
	private function add_params_to_query_string( $url, $params ) {
		return add_query_arg( $params, $url );
	}

	/**
	 * @param $api_response
	 *
	 * @return mixed
	 * @throws \RuntimeException
	 */
	private function validate_response_content_type( $api_response ) {
		if ( ! array_key_exists( 'headers', $api_response ) || ! array_key_exists( 'content-type', $api_response['headers'] ) ) {
			throw new RuntimeException( 'Invalid HTTP response, no content type in header given!' );
		}

		return $api_response;
	}

	public function set_method( $method ) {
		$this->method = $method;
	}
}
