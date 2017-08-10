<?php

class WPML_Lang_Parameter_Filters {

	public function add_hooks() {
		add_filter( 'request', array( $this, 'request_filter' ) );
		add_filter( 'get_pagenum_link', array( $this, 'paginated_url_filter' ) );
		add_filter( 'wp_link_pages_link', array( $this, 'paginated_link_filter' ) );
	}

	public function request_filter( $request ) {
		// This is required so that home page detection works for other languages.
		if ( ! defined( 'WP_ADMIN' ) && isset( $request['lang'] ) ) {
			unset( $request['lang'] );
		}

		return $request;
	}

	/**
	 * Filters the pagination links on taxonomy archives to properly have the language parameter after the URI.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function paginated_url_filter( $url ) {
		$url       = urldecode( $url );
		$parts     = explode( '?', $url );
		$last_part = count( $parts ) > 2 ? array_pop( $parts ) : '';
		$url       = join( '?', $parts );
		$url       = preg_replace( '#(.+?)(/\?|\?)(.*?)(/.+?$)$#', '$1$4$5?$3', $url );
		$url       = preg_replace( '#(\?.+)(%2F|\/)$#', '$1', $url );

		if ( '' !== $last_part && strpos( $url, '?' . $last_part ) === false ) {
			$url .= '&' . $last_part;
		}
		$parts     = explode( '?', $url );

		if ( isset( $parts[1] ) ) {
			// Maybe remove duplicated lang param
			$params = array();
			parse_str( $parts[1], $params );
			$url = $parts[0] . '?' . build_query( $params );
		}

		return $url;
	}

	/**
	 * Filters the pagination links on paginated posts and pages, acting on the links html
	 * output containing the anchor tag the link is a property of.
	 *
	 * @param string $link_html
	 *
	 * @return string
	 *
	 * @hook wp_link_pages_link
	 */
	public function paginated_link_filter( $link_html ) {
		return preg_replace( '#"([^"].+?)(/\?|\?)([^/]+)(/[^"]+)"#', '"$1$4?$3"', $link_html );
	}
}