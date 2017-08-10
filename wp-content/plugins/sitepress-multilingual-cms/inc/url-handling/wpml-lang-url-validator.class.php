<?php

class WPML_Lang_URL_Validator {
	/** @var  SitePress $sitepress */
	private $sitepress;

	/** @var WP_Http $http_client */
	private $http_client;
	/** @var WPML_URL_Converter $wpml_url_converter */
	private $url_converter;
	/** @var array|WP_Error $response */
	private $response;
	/** @var  string $validation_url */
	private $posted_url;

	/**
	 * @param  WP_Http            $client
	 * @param  WPML_URL_Converter $wpml_url_converter
	 * @param  string             $posted_url
	 * @param  SitePress          $sitepress
	 */
	public function __construct( $client, $wpml_url_converter, $posted_url, $sitepress ) {
		$this->sitepress     = $sitepress;
		$this->url_converter = $wpml_url_converter;
		$this->http_client   = $client;
	}

	public function get_validation_url( $sample_lang_code ) {
		$url_glue = false === strpos ( $this->posted_url, '?' ) ? '?' : '&';

		return $this->get_sample_url ( $sample_lang_code ) . $url_glue . '____icl_validate_directory=1';
	}

	public function validate_langs_in_dirs( $sample_lang ) {
		$icl_folder_url_disabled = true;

		$validation_url = $this->get_validation_url( $sample_lang );
		$response       = $this->do_request( $validation_url );

		if ( $response ) {
			$validation_token = '<!--' . $this->get_sample_url( $sample_lang ) . '-->';
			$is_wp_error      = is_wp_error( $response );

			$is_self_signed_certificate_error = isset( $response->errors['http_request_failed'] ) && $response->errors['http_request_failed'][0] === 'SSL certificate problem: self signed certificate';
			$is_valid_response                = ! $is_wp_error && isset( $response['response']['code'] ) && ( '200' === (string) $response['response']['code'] );

			if ( ( $is_valid_response && false !== strpos( $response['body'], $validation_token ) )
			     || ( $is_wp_error && $is_self_signed_certificate_error )
			) {
				$icl_folder_url_disabled = false;
			}
		}

		return $icl_folder_url_disabled;
	}

	public function print_error_response() {
		$response = $this->response;
		$output = '';
		if ( is_wp_error ( $response ) ) {
			$output .= '<strong>';
			$output .= $response->get_error_message ();
			$output .= '</strong>';
		} elseif ( $response[ 'response' ][ 'code' ] != '200' ) {
			$output .= '<strong>';
			$output .= sprintf (
				__ ( 'HTTP code: %s (%s)', 'sitepress' ),
				$response[ 'response' ][ 'code' ],
				$response[ 'response' ][ 'message' ]
			);
			$output .= '</strong>';
		} else {
			$output .= '<div style="width:100%;height:150px;overflow:auto;background-color:#fff;color:#000;font-family:Courier;font-style:normal;border:1px solid #aaa;">'
			           . htmlentities ( $response[ 'body' ] ) . '</div>';
		}

		return $output;
	}

	public function print_explanation( $sample_lang_code, $root = false ) {
		$def_lang_code = $this->sitepress->get_default_language();
		$sample_lang   = $this->sitepress->get_language_details( $sample_lang_code );
		$def_lang      = $this->sitepress->get_language_details( $def_lang_code );
		$output        = '<span class="explanation-text">(';

		$output .= sprintf(
			'%s - %s, %s - %s',
			trailingslashit( $this->get_sample_url( $root ? $def_lang_code : '' ) ),
			$def_lang['display_name'],
			trailingslashit( $this->get_sample_url( $sample_lang_code ) ),
			$sample_lang['display_name']
		);
		$output .= ')</span>';

		return $output;
	}

	private function do_request( $validation_url ) {
		$this->response = $this->http_client->request (
			$validation_url,
			array( 'timeout' => 15, 'decompress' => false )
		);

		return $this->response;
	}

	private function get_sample_url( $sample_lang_code ) {
		$abs_home = $this->url_converter->get_abs_home ();

		return untrailingslashit( trailingslashit ( $abs_home ) . $sample_lang_code );
	}
}