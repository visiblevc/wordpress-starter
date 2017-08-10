<?php

class WPML_ST_WP_Wrapper {
	/**
	 * @var WP
	 */
	private $wp;

	/**
	 * @param WP $wp
	 */
	public function __construct( WP $wp ) {
		$this->wp = clone $wp;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function parse_request( $path ) {
		global $wp_filter;

		$tmp_wp_filter = $wp_filter;

		$filters = array();
		if ( isset( $tmp_wp_filter['sanitize_title'] ) ) {
			$filters['sanitize_title'] = $tmp_wp_filter['sanitize_title'];
		}
		$GLOBALS['wp_filter'] = $filters;

		$result = $path;

		$this->wp->parse_request();
		if ( $this->wp->matched_rule ) {
			$result = $this->wp->matched_rule;
			$this->wp->matched_rule = null;
		}

		$GLOBALS['wp_filter'] = $tmp_wp_filter;

		return $result;
	}
}