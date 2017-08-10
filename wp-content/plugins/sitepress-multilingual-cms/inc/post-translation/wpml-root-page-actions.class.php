<?php

class WPML_Root_Page_Actions {

	/** @var array $sp_settings */
	private $sp_settings;

	public function __construct( &$sitepress_settings ) {
		$this->sp_settings = &$sitepress_settings;
	}

	public function delete_root_page_lang() {
		global $wpdb;
		$root_id = $this->get_root_page_id ();

		if ( $root_id ) {

			$update_args = array(
				'element_id' => $root_id,
				'element_type' => 'post_page',
				'context' => 'post'
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

			$wpdb->delete (
				$wpdb->prefix . 'icl_translations', array( 'element_id' => $root_id, 'element_type' => 'post_page' ), array( '%d', '%s' )
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
		}
	}

	/**
	 * Checks if a given $url points at the root page
	 *
	 * @param string $url
	 *
	 * @return bool
	 *
	 * @uses \WPML_Root_Page::is_root_page
	 */
	public function is_url_root_page( $url ) {
		$ret = false;

		if ( $this->get_root_page_id() ) {
			$ret = WPML_Root_Page::is_root_page( $url );
		}

		return $ret;
	}

	/**
	 * If a page is used as the root page, returns the id of that page, otherwise false.
	 *
	 * @return bool|false|int
	 */
	public function get_root_page_id() {
		$urls_in_dirs = isset($this->sp_settings['language_negotiation_type']) && (int)$this->sp_settings['language_negotiation_type'] === 1;
		$urls = isset( $this->sp_settings['urls'] ) ? $this->sp_settings['urls'] : array();

		return $urls_in_dirs && isset( $urls['root_page'] )
		       && ! empty( $urls['directory_for_default_language'] )
		       && isset( $urls['show_on_root'] )
		       && $urls['show_on_root'] === 'page'
		       && $urls['root_page']
				? $urls['root_page'] : false;
	}

	function wpml_home_url_init() {
		global $pagenow, $sitepress;

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {

			$root_id = $this->get_root_page_id();
			if ( ! empty( $_GET['wpml_root_page'] ) && ! empty( $root_id ) ) {
				$rp = get_post( $root_id );
				if ( $rp && $rp->post_status != 'trash' ) {
					wp_redirect( get_edit_post_link( $root_id, 'no-display' ) );
					exit;
				}
			}

			if ( isset( $_GET['wpml_root_page'] ) && $_GET['wpml_root_page'] || ( isset( $_GET['post'] ) && $_GET['post'] == $root_id ) ) {
				remove_action( 'admin_head', array( $sitepress, 'post_edit_language_options' ) );
				add_action( 'admin_head', array( $this, 'wpml_home_url_language_box_setup' ) );
				remove_action( 'page_link', array( $sitepress, 'permalink_filter' ), 1, 2 );
			}
		}
	}

	function wpml_home_url_exclude_root_page_from_menus( $args ) {
		if ( !empty( $args[ 'exclude' ] ) ) {
			$args[ 'exclude' ] .= ',';
		} else {
			$args[ 'exclude' ] = '';
		}
		$args[ 'exclude' ] .= $this->get_root_page_id();

		return $args;
	}

	/**
	 * Filters out all page menu items that point to the root page.
	 *
	 * @param object[] $items
	 *
	 * @return array
	 *
	 * @hook wp_get_nav_menu_items
	 */
	function exclude_root_page_menu_item( $items ) {
		$root_id = $this->get_root_page_id();
		foreach ( $items as $key => $item ) {
			if ( isset( $item->object_id )
			     && isset( $item->type )
			     && $item->object_id == $root_id
			     && $item->type === 'post_type'
			) {
				unset( $items[ $key ] );
			}
		}

		return $items;
	}

	function wpml_home_url_exclude_root_page( $excludes ) {
		$excludes[ ] = $this->get_root_page_id();

		return $excludes;

	}

	function wpml_home_url_exclude_root_page2( $args ) {
		$args[ 'exclude' ][ ] = $this->get_root_page_id();

		return $args;
	}

	function wpml_home_url_get_pages( $pages ) {
		$root_id = $this->get_root_page_id();
		foreach ( $pages as $k => $page ) {
			if ( $page->ID == $root_id ) {
				unset( $pages[ $k ] );
				$pages = array_values ( $pages );
				break;
			}
		}

		return $pages;
	}

	function wpml_home_url_language_box_setup() {
		add_meta_box (
			'icl_div',
			__ ( 'Language', 'sitepress' ),
			array( $this, 'wpml_home_url_language_box' ),
			'page',
			'side',
			'high'
		);
	}

	function wpml_home_url_language_box( $post ) {
		$root_id = $this->get_root_page_id();
		if ( isset( $_GET[ 'wpml_root_page' ] )
		     || ( !empty( $root_id )
		          && $post->ID == $root_id ) ) {
			_e ( "This page does not have a language since it's the site's root page." );
			echo '<input type="hidden" name="_wpml_root_page" value="1" />';
		}
	}

	function wpml_home_url_save_post_actions( $pidd, $post ) {
		global $sitepress, $wpdb, $iclTranslationManagement;

		if ( (bool) filter_input ( INPUT_POST, '_wpml_root_page' ) === true ) {

			if ( isset( $_POST[ 'autosave' ] ) || ( isset( $post->post_type ) && $post->post_type == 'revision' ) ) {
				return;
			}

			$iclsettings[ 'urls' ][ 'root_page' ] = $post->ID;
			$sitepress->save_settings ( $iclsettings );

			remove_action ( 'save_post', array( $sitepress, 'save_post_actions' ), 10, 2 );

			if ( !is_null ( $iclTranslationManagement ) ) {
				remove_action ( 'save_post', array( $iclTranslationManagement, 'save_post_actions' ), 11, 2 );
			}

			$update_args = array(
				'element_id' => $post->ID,
				'element_type' => 'post_page',
				'context' => 'post'
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

			$wpdb->query (
				$wpdb->prepare (
					"DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type='post_page' AND element_id=%d",
					$post->ID
				)
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
		}
	}

	function wpml_home_url_setup_root_page() {
		global $sitepress, $wpml_query_filter;

		remove_action( 'template_redirect', 'redirect_canonical' );
		add_action( 'parse_query', array( $this, 'wpml_home_url_parse_query' ) );

		remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10, 2 );
		remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10, 2 );
		$root_id = $this->get_root_page_id();
		$rp      = get_post( $root_id );
		if ( $rp && $rp->post_status != 'trash' ) {
			$sitepress->ROOT_URL_PAGE_ID = $root_id;
		}
	}

	/**
	 * @param WP_Query $q
	 *
	 * @return mixed
	 */
	function wpml_home_url_parse_query( $q ) {
		if ( ! $q->is_main_query() ) {
			return $q;
		}
		if ( ! WPML_Root_Page::is_current_request_root() ) {
			return $q;
		} else {
			remove_action( 'parse_query', array( $this, 'wpml_home_url_parse_query' ) );

			$request_array                  = explode( '/', $_SERVER['REQUEST_URI'] );
			$sanitized_query                = array_pop( $request_array );
			$potential_pagination_parameter = array_pop( $request_array );

			if ( is_numeric( $potential_pagination_parameter ) ) {
				if ( $sanitized_query ) {
					$sanitized_query .= '&';
				}
				$sanitized_query .= 'page=' . $potential_pagination_parameter;
			}

			$sanitized_query = str_replace( '?', '', $sanitized_query );
			$q->parse_query( $sanitized_query );
			$root_id                  = $this->get_root_page_id();
			add_action( 'parse_query', array( $this, 'wpml_home_url_parse_query' ) );
			if ( false !== $root_id ) {
				$q = $this->set_page_query_parameters( $q, $root_id );
			} else {
				$front_page = get_option( 'page_on_front' );
				if ( $front_page ) {
					$q = $this->set_page_query_parameters( $q, $front_page );
				}
			}
		}

		return $q;
	}

	private function set_page_query_parameters( $q, $page_id ) {
		$q->query_vars['page_id'] = $page_id;
		$q->query['page_id']      = $page_id;
		$q->is_page               = 1;
		$q->queried_object        = new WP_Post( get_post( $page_id ) );
		$q->queried_object_id     = $page_id;
		$q->query_vars['error']   = '';
		$q->is_404                = false;
		$q->query['error']        = null;
		$q->is_home               = false;

		return $q;
	}
}

/**
 * Checks if the language switcher is to be displayed.
 * Used to check if the displayed page is a root page and the switcher is to be hidden because of it.
 *
 * @return bool true if the switcher is to be hidden
 */
function wpml_home_url_ls_hide_check() {
	global $sitepress;

	return $sitepress->get_setting( 'language_negotiation_type' ) == 1
	       && (bool) ( $urls = $sitepress->get_setting( 'urls' ) ) === true
	       && ! empty( $urls['directory_for_default_language'] )
	       && isset( $urls['show_on_root'] ) && $urls['show_on_root'] === 'page'
	       && ! empty( $urls['hide_language_switchers'] )
	       && WPML_Root_Page::is_current_request_root();
}