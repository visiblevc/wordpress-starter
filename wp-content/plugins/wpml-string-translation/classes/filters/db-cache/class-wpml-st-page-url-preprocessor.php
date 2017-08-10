<?php


class WPML_ST_Page_URL_Preprocessor {

	const AJAX_REQUEST_VALUE = 'ajax-request';

	/**
	 * @var array
	 */
	private $white_list = array(
		'p',
		'page_id',
		'pagename',
		'shop',
		'lang',
		'category_name',
		'cat',
		'tag',
		'tag_id',
	);

	/**
	 * @var array
	 */
	private $admin_white_list = array(
		'page',
	);

	/**
	 * @var array
	 */
	private $ignore_value = array(
		'shop',
		'p',
		'page_id',
		'cat',
		'tag',
	);

	/**
	 * @var WPML_ST_WP_Wrapper
	 */
	private $wp;

	/**
	 * @param WPML_ST_WP_Wrapper $wp
	 */
	public function __construct( WPML_ST_WP_Wrapper $wp ) {
		$this->wp = $wp;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function process_url( $url ) {
		if ( empty( $url ) ) {
			return $url;
		}

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || wpml_is_ajax()  ) {
			return self::AJAX_REQUEST_VALUE;
		}

		$url = $this->process_query( $url );
		$path = parse_url( $url, PHP_URL_PATH );

		$new_path = $this->wp->parse_request( $path );
		$url = str_replace( $path, $new_path, $url );
		
		return $url;
	}
	
	/**
	 * @param string $url
	 *
	 * @return string
	 */
	private function process_query( $url ) {
		$query = parse_url( $url, PHP_URL_QUERY );
		parse_str( $query, $output );

		$white_list = $this->white_list;
		if ( is_admin() ) {
			$white_list = array_merge( $white_list, $this->admin_white_list );
		}
		$white_list = apply_filters( 'wpml-st-url-preprocessor-whitelist', $white_list );
		$output = array_intersect_key( $output, array_flip( $white_list ) );

		foreach ( array_intersect_key( $output, array_flip( $this->ignore_value ) ) as $key => $value ) {
			$output[ $key ] = '';
		}

		$new_query = http_build_query( $output );

		$url = str_replace( $query, $new_query, $url );

		if ( '?' === $url[ strlen( $url ) - 1 ] ) {
			$url = rtrim( $url, '?' );
		}

		return $url;
	}
}
