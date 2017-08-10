<?php

class WPML_Lang_Domain_Filters {

	private $wpml_url_converter;
	private $wpml_wp_api;
	private $debug_backtrace;

	/**
	 * WPML_Lang_Domain_Filters constructor.
	 *
	 * @param $wpml_url_converter
	 * @param $wpml_wp_api
	 */
	public function __construct(
		WPML_URL_Converter $wpml_url_converter,
		WPML_WP_API $wpml_wp_api,
		WPML_Debug_BackTrace $debug_backtrace
	) {

		$this->wpml_url_converter = $wpml_url_converter;
		$this->wpml_wp_api        = $wpml_wp_api;
		$this->debug_backtrace    = $debug_backtrace;
	}

	public function add_hooks() {
		add_filter( 'upload_dir', array( $this, 'upload_dir_filter_callback' ) );
		add_filter( 'stylesheet_uri', array( $this, 'convert_url' ) );
		add_filter( 'option_siteurl', array( $this, 'siteurl_callback' ) );
		add_filter( 'content_url', array( $this, 'siteurl_callback' ) );
		add_filter( 'login_url', array( $this, 'convert_url' ) );
		add_filter( 'logout_url', array( $this, 'convert_logout_url' ) );
		add_filter( 'admin_url', array( $this, 'admin_url_filter' ), 10, 2 );
		add_filter( 'login_redirect', array( $this, 'convert_url' ), 1, 3);
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function convert_url( $url ) {
		return $this->wpml_url_converter->convert_url( $url );
	}

	/**
	 * @param array $upload_dir
	 *
	 * @return array
	 */
	public function upload_dir_filter_callback( $upload_dir ) {
		$upload_dir['url'] = $this->wpml_url_converter->convert_url( $upload_dir['url'] );
		$upload_dir['baseurl'] = $this->wpml_url_converter->convert_url( $upload_dir['baseurl'] );

		return $upload_dir;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function siteurl_callback( $url ) {
		if ( ! $this->debug_backtrace->is_function_in_call_stack( 'get_home_path' ) ) {
			$parsed_url = wpml_parse_url( $url );
			$host       = is_array( $parsed_url ) && isset( $parsed_url['host'] );
			if ( $host && isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] ) {
				$url = str_replace( $parsed_url['host'], $_SERVER['HTTP_HOST'], $url );
			}
		}

		return $url;
	}

	/**
	 * @param string $url
	 * @param string $path
	 *
	 * @return string
	 */
	public function admin_url_filter( $url, $path ) {
		if ( ( strpos( $url, 'http://' ) === 0
		       || strpos( $url, 'https://' ) === 0 )
		     && 'admin-ajax.php' === $path && $this->wpml_wp_api->is_front_end()
		) {
			global $sitepress;

			$url = $this->wpml_url_converter->convert_url( $url, $sitepress->get_current_language() );
		}

		return $url;
	}

	/**
	 * Convert logout url only for front-end.
	 *
	 * @param $logout_url
	 *
	 * @return string
	 */
	public function convert_logout_url( $logout_url ) {
		if ( $this->wpml_wp_api->is_front_end() ) {
			$logout_url = $this->wpml_url_converter->convert_url( $logout_url );
		}

		return $logout_url;
	}
}