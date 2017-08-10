<?php

class WPML_URL_Converter_Url_Helper {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var WPML_Include_Url
	 */
	private $wpml_include_url_filter;

	/**
	 * @var string
	 */
	private $absolute_home;

	/**
	 *
	 * @param wpdb $wpdb
	 * @param WPML_Include_Url $wpml_include_url_filter
	 */
	public function __construct( wpdb $wpdb = null, WPML_Include_Url $wpml_include_url_filter = null ) {
		if ( ! $wpdb ) {
			global $wpdb;
		}

		if ( ! $wpml_include_url_filter ) {
			global $wpml_include_url_filter;
		}

		$this->wpdb                    = $wpdb;
		$this->wpml_include_url_filter = $wpml_include_url_filter;
	}

	/**
	 * Returns the unfiltered home_url by directly retrieving it from wp_options.
	 *
	 * @return string
	 */
	public function get_abs_home() {
		if ( ! $this->absolute_home ) {
			$is_multisite = is_multisite();
			if ( ! $is_multisite && defined( 'WP_HOME' ) ) {
				$this->absolute_home = WP_HOME;
			} elseif ( $is_multisite && ! is_main_site() ) {
				$protocol = preg_match( '/^(https)/', get_option( 'home' ) ) === 1 ? 'https://' : 'http://';
				$sql      = "	
					SELECT CONCAT(b.domain, b.path)
					FROM {$this->wpdb->blogs} b
					WHERE blog_id = {$this->wpdb->blogid}
					LIMIT 1
				";

				$this->absolute_home = $protocol . $this->wpdb->get_var( $sql );
			} else {
				$this->absolute_home = $this->get_unfiltered_home_option();
			}
		}

		return apply_filters( 'wpml_url_converter_get_abs_home', $this->absolute_home );
	}

	/**
	 * Checks if a $url points to a WP Admin screen.
	 *
	 * @param string $url
	 * @return bool True if the input $url points to an admin screen.
	 */
	public function is_url_admin( $url ) {
		$url_query_parts = wpml_parse_url( strpos( $url, 'http' ) === false ? 'http://' . $url : $url );

		return isset( $url_query_parts['path'] )
		       && strpos( wpml_strip_subdir_from_url( $url_query_parts['path'] ), '/wp-admin' ) === 0;
	}

	/**
	 * Returns the unfiltered home option from the database.
	 *
	 * @uses \WPML_Include_Url::get_unfiltered_home in case the $wpml_include_url_filter global is loaded
	 *
	 * @return string
	 */
	private function get_unfiltered_home_option() {
		if ( $this->wpml_include_url_filter ) {
			return $this->wpml_include_url_filter->get_unfiltered_home();
		} else {
			$sql = "	
				SELECT option_value
				FROM {$this->wpdb->options}
				WHERE option_name = 'home'
				LIMIT 1
			";

			return $this->wpdb->get_var( $sql );
		}
	}
}