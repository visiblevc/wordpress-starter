<?php

class WPML_Root_Page {

	public static function init() {
		if ( WPML_Root_Page::uses_html_root()
		     || WPML_Root_Page::get_root_id() > 0
		     || strpos( (string) filter_var( $_SERVER['REQUEST_URI'] ), 'wpml_root_page=1' ) !== false
		     || (bool) filter_input( INPUT_POST, '_wpml_root_page' ) === true
		) {
			global $wpml_root_page_actions;

			$wpml_root_page_actions = wpml_get_root_page_actions_obj();
			add_action( 'init', array( $wpml_root_page_actions, 'wpml_home_url_init' ), 0 );
			add_filter( 'wp_page_menu_args', array( $wpml_root_page_actions, 'wpml_home_url_exclude_root_page_from_menus' ) );
			add_filter( 'wp_get_nav_menu_items', array( $wpml_root_page_actions, 'exclude_root_page_menu_item' ), 10, 1 );
			add_filter( 'wp_list_pages_excludes', array( $wpml_root_page_actions, 'wpml_home_url_exclude_root_page' ) );
			add_filter( 'page_attributes_dropdown_pages_args', array( $wpml_root_page_actions, 'wpml_home_url_exclude_root_page2' ) );
			add_filter( 'get_pages', array( $wpml_root_page_actions, 'wpml_home_url_get_pages' ) );
			add_action( 'save_post', array( $wpml_root_page_actions, 'wpml_home_url_save_post_actions' ), 0, 2 );

			if ( self::get_root_id() > 0 ) {
				add_filter( 'template_include', array( 'WPML_Root_Page', 'wpml_home_url_template_include' ) );
				add_filter( 'the_preview', array( 'WPML_Root_Page', 'front_page_id_filter' ) );
			}
			$root_page_actions = wpml_get_root_page_actions_obj();
			add_action( 'icl_set_element_language', array( $root_page_actions, 'delete_root_page_lang' ), 10, 0 );
		}
	}

	/**
	 * Checks if the value in $_SERVER['REQUEST_URI] points towards the root page.
	 * Therefore this can be used to check if the current request points towards the root page.
	 * @return bool
	 */
	public static function is_current_request_root() {
		return self::is_root_page( $_SERVER[ 'REQUEST_URI' ] );
	}

	/**
	 * @param $requested_url string
	 *                       Checks if a requested url points towards the root page.
	 *
	 * @return bool
	 */
	public static function is_root_page( $requested_url ) {
		$cached_val = wp_cache_get( md5( $requested_url ) );

		if ( $cached_val !== false ) {
			return (bool) $cached_val;
		}

		$request_parts = self::get_slugs_and_get_query( $requested_url );
		$slugs         = $request_parts[ 'slugs' ];
		$gets          = $request_parts[ 'querystring' ];

		$target_of_gets = self::get_query_target_from_query_string( $gets );

		if ( $target_of_gets == WPML_QUERY_IS_ROOT ) {
			$result = true;
		} elseif ( $target_of_gets == WPML_QUERY_IS_OTHER_THAN_ROOT || $target_of_gets == WPML_QUERY_IS_NOT_FOR_POST && self::query_points_to_archive( $gets ) ) {
			$result = false;
		} else {
			$result = self::slugs_point_to_root( $slugs );
		}

		wp_cache_add( md5( $requested_url ), ( $result === true ? 1 : 0 ) );

		return $result;
	}

	public static function uses_html_root() {
		$urls = icl_get_setting( 'urls' );

		return isset( $urls[ 'root_page' ] ) && isset( $urls[ 'show_on_root' ] ) && $urls[ 'show_on_root' ] === 'html_file';
	}

	/**
	 * Returns the id of the root page or false if it isn't set.
	 * @return bool|int
	 */
	public static function get_root_id() {
		$root_actions = wpml_get_root_page_actions_obj();

		return $root_actions->get_root_page_id();
	}

	/**
	 * Returns the slug of the root page or false if non exists.
	 * @return bool|string
	 */
	private static function get_root_slug() {

		$root_id = self::get_root_id();

		$root_slug = false;
		if ( $root_id ) {
			$root_page_object = get_post( $root_id );
			if ( $root_page_object && isset( $root_page_object->post_name ) ) {
				$root_slug = $root_page_object->post_name;
			}
		}

		return $root_slug;
	}

	/**
	 * @param $requested_url string
	 *                       Takes a request_url in the format of $_SERVER['REQUEST_URI']
	 *                       and returns an associative array containing its slugs ans query string.
	 *
	 * @return array
	 */
	private static function get_slugs_and_get_query( $requested_url ) {
		$result            = array();
		$request_path      = wpml_parse_url( $requested_url, PHP_URL_PATH );
		$request_path      = wpml_strip_subdir_from_url( $request_path );
		$slugs             = self::get_slugs_array( $request_path );
		$result[ 'slugs' ] = $slugs;

		$query_string            = wpml_parse_url( $requested_url, PHP_URL_QUERY );
		$result[ 'querystring' ] = ! $query_string ? '' : $query_string;

		return $result;
	}

	/**
	 * @param $path string
	 *              Turns a query string into an array of its slugs.
	 *              The array is filtered so to not contain empty values and
	 *              consecutively and numerically indexed starting at 0.
	 *
	 * @return array
	 */
	private static function get_slugs_array( $path ) {
		$slugs = explode( '/', $path );
		$slugs = array_filter( $slugs );
		$slugs = array_values( $slugs );

		return $slugs;
	}

	/**
	 * @param $slugs array
	 *               Checks if a given set of slugs points towards the root page or not.
	 *               The result of this can always be overridden by GET parameters and is not a certain
	 *               check as to being on the root page or not.
	 *
	 * @return bool
	 */
	private static function slugs_point_to_root( $slugs ) {
		$result = true;
		if ( ! empty( $slugs ) ) {
			$root_slug = self::get_root_slug();

			$last_slug   = array_pop( $slugs );
			$second_slug = array_pop( $slugs );
			$third_slug  = array_pop( $slugs );

			if ( ( $root_slug != $last_slug && ! is_numeric( $last_slug ) )
					 || ( is_numeric( $last_slug )
								&& $second_slug != null
								&& $root_slug !== $second_slug
								&& ( ( 'page' !== $second_slug )
										 || ( 'page' === $second_slug && ( $third_slug && $third_slug != $root_slug ) ) ) )
			) {
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @param $query_string
	 * Turns a given query string into an associative array of its parameters.
	 *
	 * @return array
	 */
	private static function get_query_array_from_string( $query_string ) {
		$all_query_params = array();
		parse_str( $query_string, $all_query_params );

		return $all_query_params;
	}

	/**
	 * @param $query_string string
	 *                      Checks if the WP_Query functionality can decisively recognize if a querystring points
	 *                      towards an archive.
	 *
	 * @return bool
	 */
	private static function query_points_to_archive( $query_string ) {

		$root_page_actions = wpml_get_root_page_actions_obj();
		remove_action( 'parse_query', array( $root_page_actions, 'wpml_home_url_parse_query' ) );
		$query_string = str_replace( '?', '', $query_string );
		$query        = new WP_Query( $query_string );
		$is_archive   = $query->is_archive();
		add_action( 'parse_query', array( $root_page_actions, 'wpml_home_url_parse_query' ) );

		return $is_archive;
	}

	/**
	 * @param $query_string string
	 *                      Checks if a given query string decisively points towards or away from the root page.
	 *
	 * @return int
	 */
	private static function get_query_target_from_query_string( $query_string ) {
		$params_array = self::get_query_array_from_string( $query_string );

		return self::get_query_target_from_params_array( $params_array );
	}

	/**
	 * @param $query_params array
	 *                      Checks if a set of query parameters decisively points towards or away from the root page.
	 *
	 * @return int
	 */
	private static function get_query_target_from_params_array( $query_params ) {

		if ( ! isset( $query_params[ 'p' ] )
				 && ! isset( $query_params[ 'page_id' ] )
				 && ! isset( $query_params[ 'name' ] )
				 && ! isset( $query_params[ 'pagename' ] )
				 && ! isset( $query_params[ 'page_name' ] )
				 && ! isset( $query_params[ 'attachment_id' ] )
		) {
			$result = WPML_QUERY_IS_NOT_FOR_POST;
		} else {

			$root_id   = self::get_root_id();
			$root_slug = self::get_root_slug();

			if ( ( isset( $query_params[ 'p' ] ) && $query_params[ 'p' ] != $root_id )
					 || ( isset( $query_params[ 'page_id' ] ) && $query_params[ 'page_id' ] != $root_id )
					 || ( isset( $query_params[ 'name' ] ) && $query_params[ 'name' ] != $root_slug )
					 || ( isset( $query_params[ 'pagename' ] ) && $query_params[ 'pagename' ] != $root_slug )
					 || ( isset( $query_params[ 'preview_id' ] ) && $query_params[ 'preview_id' ] != $root_id )
					 || ( isset( $query_params[ 'attachment_id' ] ) && $query_params[ 'attachment_id' ] != $root_id )
			) {
				$result = WPML_QUERY_IS_OTHER_THAN_ROOT;
			} else {
				$result = WPML_QUERY_IS_ROOT;
			}
		}

		return $result;
	}

	/**
	 * @param $post false|WP_Post
	 *              Filters the postID used by the preview for the case of the root page preview.
	 *
	 * @return null|WP_Post
	 */
	public static function front_page_id_filter( $post ) {
		$preview_id = isset( $_GET[ 'preview_id' ] ) ? $_GET[ 'preview_id' ] : - 1;

		if ( $preview_id == self::get_root_id() ) {
			$post = get_post( $preview_id );
		}

		return $post;
	}

	/**
	 * Filters the template that is used for the root page
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public static function wpml_home_url_template_include( $template ) {

		return self::is_current_request_root() ? get_page_template() : $template;
	}
}