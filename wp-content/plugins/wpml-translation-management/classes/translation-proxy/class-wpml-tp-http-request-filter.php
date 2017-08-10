<?php

class WPML_TP_HTTP_Request_Filter {

	/** @var  array $request */
	private $request;

	public function __construct( $request ) {
		$this->request = $request;
	}

	/**
	 * @return array filtered response
	 */
	public function out() {
		if ( $this->contains_resource( $this->request ) === false ) {
			$this->request['headers'] = 'Content-type: application/json';
			$this->request['body']    = wp_json_encode( $this->request['body'] );
		} else {
			list( $headers, $body ) = $this->_prepare_multipart_request( $this->request['body'] );
			$this->request['headers'] = $headers;
			$this->request['body']    = $body;
		}

		if ( $this->request['method'] === 'GET' ) {
			unset( $this->request['body'] );
		}

		return $this->request;
	}

	/**
	 * Checks if a request contains a file resource handle
	 *
	 * @param array $request_snippet
	 *
	 * @return bool
	 */
	private function contains_resource( array $request_snippet ) {
		foreach ( $request_snippet as $part ) {
			if ( is_resource( $part ) === true || ( is_array( $part ) && $this->contains_resource( $part ) ) ) {
				return true;
			}
		}

		return false;
	}

	private function _prepare_multipart_request( $params ) {
		$boundary = '----' . microtime();
		$header   = "Content-Type: multipart/form-data; boundary=$boundary";
		$content  = self::_add_multipart_contents( $boundary, $params );
		$content .= "--$boundary--\r\n";

		return array( $header, $content );
	}

	private function _add_multipart_contents(
		$boundary,
		$params,
		$context = array()
	) {
		$initial_context = $context;
		$content         = '';

		foreach ( $params as $key => $value ) {
			$context   = $initial_context;
			$context[] = $key;

			if ( is_array( $value ) ) {
				$content .= self::_add_multipart_contents( $boundary, $value,
					$context );
			} else {
				$pieces = array_slice( $context, 1 );
				if ( $pieces ) {
					$name = "{$context[0]}[" . implode( "][", $pieces ) . "]";
				} else {
					$name = "{$context[0]}";
				}

				$content .= "--$boundary\r\n" . "Content-Disposition: form-data; name=\"$name\"";

				if ( is_resource( $value ) ) {
					$filename = self::get_file_name( $params, $key );
					$content .= "; filename=\"$filename\"\r\n" . "Content-Type: application/octet-stream\r\n\r\n" . gzencode( stream_get_contents( $value ) ) . "\r\n";
				} else {
					$content .= "\r\n\r\n$value\r\n";
				}
			}
		}

		return $content;
	}

	private function get_file_name( $params, $default = 'file' ) {

		$title = isset( $params['title'] ) ? sanitize_title_with_dashes( strtolower( filter_var( $params['title'],
			FILTER_SANITIZE_STRING,
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ) ) )
			: '';
		if ( str_replace( array( '-', '_' ), '', $title ) == '' ) {
			$title = $default;
		}
		$source_language = isset( $params['source_language'] ) ? $params['source_language'] : '';
		$target_language = isset( $params['target_language'] ) ? $params['target_language'] : '';

		$filename = implode( '-', array_filter( array(
			$title,
			$source_language,
			$target_language
		) ) );

		return $filename . ".xliff.gz";
	}
}