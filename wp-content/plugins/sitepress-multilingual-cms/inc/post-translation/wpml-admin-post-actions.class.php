<?php

/**
 * Class WPML_Admin_Post_Actions
 *
 * @package    wpml-core
 * @subpackage post-translation
 */
class WPML_Admin_Post_Actions extends WPML_Post_Translation {

	public function init() {
		parent::init ();
		if ( $this->is_setup_complete() ) {
			add_action ( 'delete_post', array( $this, 'delete_post_actions' ) );
			add_action ( 'wp_trash_post', array( $this, 'trashed_post_actions' ) );
			add_action ( 'untrashed_post', array( $this, 'untrashed_post_actions' ) );
		}
	}

	/**
	 * @param Integer $post_id
	 * @param String $post_status
	 * @return null|int
	 */
	function get_save_post_trid( $post_id, $post_status ) {
		$trid = $this->get_element_trid( $post_id );
		$trid = $trid ? $trid : filter_var( isset( $_POST['icl_trid'] ) ? $_POST['icl_trid'] : '', FILTER_SANITIZE_NUMBER_INT );
		$trid = $trid ? $trid : filter_var( isset( $_GET['trid'] ) ? $_GET['trid'] : '', FILTER_SANITIZE_NUMBER_INT );
		$trid = $trid ? $trid : $this->get_trid_from_referer();
		$trid = apply_filters( 'wpml_save_post_trid_value', $trid, $post_status );

		return $trid;
	}

	public function save_post_actions( $pidd, $post ) {
		global $sitepress;

		wp_defer_term_counting( true );
		$post = isset( $post ) ? $post : get_post( $pidd );
		// exceptions
		if ( ! $this->has_save_post_action( $post ) ) {
			wp_defer_term_counting( false );

			return;
		}
		if ( WPML_WordPress_Actions::is_bulk_trash( $pidd ) ||
		     WPML_WordPress_Actions::is_bulk_untrash( $pidd ) ||
		     WPML_WordPress_Actions::is_heartbeat( )
		) {

			return;
		}
		$default_language = $sitepress->get_default_language();
		$post_vars        = (array) $_POST;
		foreach ( (array) $post as $k => $v ) {
			$post_vars[ $k ] = $v;
		}

		$post_vars['post_type'] = isset( $post_vars['post_type'] ) ? $post_vars['post_type'] : $post->post_type;
		$post_id                = $pidd;
		if ( isset( $post_vars['action'] ) && $post_vars['action'] === 'post-quickpress-publish' ) {
			$language_code = $default_language;
		} else {
			$post_id       = isset( $post_vars['post_ID'] ) ? $post_vars['post_ID'] : $pidd; //latter case for XML-RPC publishing
			$language_code = $this->get_save_post_lang( $post_id, $sitepress );
		}

		if ( $this->is_inline_action( $post_vars ) && ! ( $language_code = $this->get_element_lang_code(
				$post_id
			) )
		) {
			return;
		}

		if ( isset( $post_vars['icl_translation_of'] ) && is_numeric( $post_vars['icl_translation_of'] ) ) {
			$translation_of_data_prepared = $this->wpdb->prepare(
				"SELECT trid, language_code
				 FROM {$this->wpdb->prefix}icl_translations
				 WHERE element_id=%d
					AND element_type=%s
				 LIMIT 1",
				$post_vars['icl_translation_of'],
				'post_' . $post->post_type
			);
			list( $trid, $source_language ) = $this->wpdb->get_row( $translation_of_data_prepared, 'ARRAY_N' );
		}

		if ( isset( $post_vars['icl_translation_of'] ) && $post_vars['icl_translation_of'] == 'none' ) {
			$trid            = null;
			$source_language = $language_code;
		} else {
			$trid = isset( $trid ) && $trid ? $trid : $this->get_save_post_trid( $post_id, $post->post_status );
			// after getting the right trid set the source language from it by referring to the root translation
			// of this trid, in case no proper source language has been set yet
			$source_language = isset( $source_language )
				? $source_language : $this->get_save_post_source_lang( $trid, $language_code, $default_language );
		}
		if ( isset( $post_vars['icl_tn_note'] ) ) {
			update_post_meta( $post_id, '_icl_translator_note', $post_vars['icl_tn_note'] );
		}
		$save_filter_action_state = new WPML_WP_Filter_State( 'save_post' );
		$this->after_save_post( $trid, $post_vars, $language_code, $source_language );
		$save_filter_action_state->restore();
	}

	/**
	 * @param integer   $post_id
	 * @param SitePress $sitepress
	 *
	 * @return null|string
	 */
	public function get_save_post_lang( $post_id, $sitepress ) {
		$language_code = filter_var(
			( isset( $_POST['icl_post_language'] ) ? $_POST['icl_post_language'] : '' ),
			FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$language_code = $language_code
			? $language_code
			: filter_input(
				INPUT_GET,
				'lang',
				FILTER_SANITIZE_FULL_SPECIAL_CHARS
			);

		return $language_code ? $language_code : parent::get_save_post_lang( $post_id, $sitepress );
	}

	/**
	 * @param array $post_vars
	 * @return bool
	 */
	private function is_inline_action( $post_vars ) {

		return isset( $post_vars[ 'action' ] )
		       && $post_vars[ 'action' ] == 'inline-save'
		       || isset( $_GET[ 'bulk_edit' ] )
		       || isset( $_GET[ 'doing_wp_cron' ] )
		       || ( isset( $_GET[ 'action' ] )
		            && $_GET[ 'action' ] == 'untrash' );
	}

	/**
	 * @param int    $trid
	 * @param string $language_code
	 * @param string $default_language
	 *
	 * @uses \WPML_Backend_Request::get_source_language_from_referer to retrieve the source_language when saving via ajax
	 *
	 * @return null|string
	 */
	protected function get_save_post_source_lang( $trid, $language_code, $default_language ) {
		/** @var WPML_Backend_Request|WPML_Frontend_Request $wpml_request_handler */
		global $wpml_request_handler;

		$source_language = filter_input ( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$source_language = $source_language ? $source_language : $wpml_request_handler->get_source_language_from_referer();
		$source_language = $source_language ? $source_language : SitePress::get_source_language_by_trid ( $trid );
		$source_language = $source_language === 'all' ? $default_language : $source_language;
		$source_language = $source_language !== $language_code ? $source_language : null;

		return $source_language;
	}

	public function get_trid_from_referer() {
		if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
			$query = wpml_parse_url ( $_SERVER[ 'HTTP_REFERER' ], PHP_URL_QUERY );
			parse_str ( $query, $vars );
		}

		if ( isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$request_uri = wpml_parse_url( $_SERVER[ 'REQUEST_URI' ], PHP_URL_QUERY );
			parse_str( $request_uri, $request_uri_vars );
		}

		/**
		 * trid from `HTTP_REFERER` should be return only if `REQUEST_URI` also has trid set.
		 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-1351
		 */
		return isset( $vars[ 'trid' ] ) && isset( $request_uri_vars['trid'] ) ? filter_var ( $vars[ 'trid' ], FILTER_SANITIZE_NUMBER_INT ) : false;
	}
}