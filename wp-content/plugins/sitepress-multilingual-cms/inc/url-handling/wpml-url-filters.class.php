<?php

/**
 * Class WPML_URL_Filters
 */
class WPML_URL_Filters {
	/** @var SitePress */
	private $sitepress;

	/** @var  WPML_Post_Translation $post_translation */
	private $post_translation;

	/** @var WPML_Canonicals */
	private $canonicals;

	/** @var WPML_URL_Converter $url_converter */
	private $url_converter;

	/**
	 * @param WPML_Post_Translation $post_translation
	 * @param WPML_URL_Converter    $url_converter
	 * @param WPML_Canonicals       $canonicals
	 * @param SitePress             $sitepress
	 */
	public function __construct( &$post_translation, &$url_converter, WPML_Canonicals $canonicals, &$sitepress ) {
		$this->sitepress        = &$sitepress;
		$this->post_translation = &$post_translation;

		$this->url_converter = &$url_converter;
		$this->canonicals    = $canonicals;
		if ( $this->frontend_uses_root() === true ) {
			WPML_Root_Page::init();
		}
		$this->add_hooks();
	}

	private function add_hooks() {
		if ( $this->frontend_uses_root() === true ) {
			add_filter( 'page_link', array( $this, 'page_link_filter_root' ), 1, 2 );
		} else {
			add_filter( 'page_link', array( $this, 'page_link_filter' ), 1, 2 );
		}

		$this->add_global_hooks();
		if ( $this->has_wp_get_canonical_url() ) {
			add_filter( 'get_canonical_url', array( $this, 'get_canonical_url_filter' ), 1, 2 );
		}
	}

	public function add_global_hooks() {
		add_filter( 'home_url', array( $this, 'home_url_filter' ), - 10, 4 );
		// posts and pages links filters
		add_filter( 'post_link', array( $this, 'permalink_filter' ), 1, 2 );
		add_filter( 'post_type_link', array( $this, 'permalink_filter' ), 1, 2 );
		add_filter( 'wpml_filter_link', array( $this, 'permalink_filter' ), 1, 2 );
		add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 1, 3 );
	}

	public function remove_global_hooks() {
		// posts and pages links filters
		remove_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 1 );
		remove_filter( 'wpml_filter_link', array( $this, 'permalink_filter' ), 1 );
		remove_filter( 'post_type_link', array( $this, 'permalink_filter' ), 1 );
		remove_filter( 'post_link', array( $this, 'permalink_filter' ), 1 );

		remove_filter( 'home_url', array( $this, 'home_url_filter' ), - 10 );
	}

	/**
	 * Filters the link to a post's edit screen by appending the language query argument
	 *
	 * @param string $link
	 * @param int    $id
	 * @param string $context
	 *
	 * @return string
	 *
	 * @hook get_edit_post_link
	 */
	public function get_edit_post_link( $link, $id, $context = 'display' ) {
		if ( $id && (bool) ( $lang = $this->post_translation->get_element_lang_code( $id ) ) === true ) {
			$link .= ( 'display' === $context ? '&amp;' : '&' ) . 'lang=' . $lang;
			if ( ! did_action( 'wpml_pre_status_icon_display' ) ) {
				do_action( 'wpml_pre_status_icon_display' );
			}
			$link = apply_filters( 'wpml_link_to_translation', $link, $id,
				$lang, $this->post_translation->get_element_trid( $id ) );
		}

		return $link;
	}

	/**
	 * Permalink filter that is used when the site uses a root page
	 *
	 * @param string      $link
	 * @param int|WP_Post $pid
	 *
	 * @return string
	 */
	public function permalink_filter_root( $link, $pid ) {
		$pid  = is_object( $pid ) ? $pid->ID : $pid;
		$link = $this->sitepress->get_root_page_utils()->get_root_page_id() != $pid
				? $this->permalink_filter( $link, $pid ) : $this->filter_root_permalink( $link );

		return $link;
	}

	/**
	 * @param $link
	 * @param $pid
	 *
	 * @return string|WPML_Notice|WPML_Notice_Render
	 */
	public function page_link_filter_root( $link, $pid ) {
		$pid  = is_object( $pid ) ? $pid->ID : $pid;
		$link = $this->sitepress->get_root_page_utils()->get_root_page_id() != $pid
			? $this->page_link_filter( $link, $pid ) : $this->filter_root_permalink( $link );

		return $link;
	}

	/**
	 * Filters links to the root page, so that they are displayed properly in the front-end.
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function filter_root_permalink( $url ) {
		$root_page_utils = $this->sitepress->get_root_page_utils();
		if ( $root_page_utils->get_root_page_id() > 0 && $root_page_utils->is_url_root_page( $url ) ) {
			$url_parts = wpml_parse_url( $url );
			$query     = isset( $url_parts['query'] ) ? $url_parts['query'] : '';
			$path      = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
			$slugs     = array_filter( explode( '/', $path ) );
			$last_slug = array_pop( $slugs );
			$new_url   = $this->url_converter->get_abs_home();
			$new_url   = is_numeric( $last_slug ) ? trailingslashit( trailingslashit( $new_url ) . $last_slug ) : $new_url;
			$query     = $this->unset_page_query_vars( $query );
			$new_url   = trailingslashit( $new_url );
			$url       = (bool) $query === true ? trailingslashit( $new_url ) . '?' . $query : $new_url;
		}

		return $url;
	}

	/**
	 * @param string      $link
	 * @param int|WP_Post $post
	 *
	 * @return bool|mixed|string
	 */
	public function permalink_filter( $link, $post ) {
		if ( ! $post ) {
			return $link;
		}

		/** @var int $post */
		if ( is_object( $post ) ) {
			$post = $post->ID;
		}

		$canonical_url = $this->canonicals->permalink_filter( $link, $post );
		if ( $canonical_url ) {
			return $canonical_url;
		}

		$post_element = new WPML_Post_Element( $post, $this->sitepress );
		if ( $post_element->is_translatable() ) {
				$link = $this->get_translated_permalink( $link, $post, $post_element );
		}
		return $link;
	}

	/**
	 * @param $link
	 * @param $post
	 *
	 * @return WPML_Notice|WPML_Notice_Render
	 */
	public function page_link_filter( $link, $post ) {
		if ( ! $post ) {
			return $link;
		}

		/** @var int $post */
		if ( is_object( $post ) ) {
			$post = $post->ID;
		}

		$canonical_url = $this->canonicals->permalink_filter( $link, $post );
		if ( $canonical_url ) {
			return $canonical_url;
		}

		$post_element = new WPML_Post_Element( $post, $this->sitepress );
		if ( $post_element->is_translatable() ) {
			$link = $this->get_translated_page_link( $link, $post, $post_element );
		}
		return $link;
	}

	private function has_wp_get_canonical_url() {
		return $this->sitepress->get_wp_api()->function_exists( 'wp_get_canonical_url' );
	}

	/**
	 * @param string|bool $canonical_url
	 * @param WP_Post     $post
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function get_canonical_url_filter( $canonical_url, $post ) {
		return $this->canonicals->get_canonical_url( $canonical_url, $post );
	}

	public function home_url_filter( $url, $path, $orig_scheme, $blog_id ) {
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : "";
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : "";
		$server_name = strpos( $request_uri, '/' ) === 0
				? untrailingslashit( $server_name ) : trailingslashit( $server_name );
		$url_snippet = $server_name . $request_uri;

		$url_language = $this->url_converter->get_language_from_url( $url_snippet );
		$home_url = $this->url_converter->convert_url( $url, $url_language );

		$home_url = apply_filters('wpml_get_home_url', $home_url, $url, $path, $orig_scheme, $blog_id);

		return $home_url;
	}

	public function frontend_uses_root() {
		/** @var array $urls */
		$urls = $this->sitepress->get_setting( 'urls' );

		return isset( $urls['root_page'] ) && isset( $urls['show_on_root'] )
		       && ! empty( $urls['directory_for_default_language'] )
		       && ( $urls['show_on_root'] === 'page' || $urls['show_on_root'] === 'html_file' );
	}

	/**
	 * Finds the correct language a post belongs to by handling the special case of the post edit screen.
	 *
	 * @param int $post_id
	 *
	 * @return bool|mixed|null|String
	 */
	private function get_permalink_filter_lang( $post_id ) {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'sample-permalink' ) {
			$code = $this->get_language_from_url();
			$code = $code
				? $code
				: ( ! isset( $_SERVER['HTTP_REFERER'] )
					? $this->sitepress->get_default_language()
					: $this->url_converter->get_language_from_url( $_SERVER["HTTP_REFERER"] ) );
		} else {
			$code = $this->post_translation->get_element_lang_code( $post_id );
		}

		return $code;
	}

	private function unset_page_query_vars( $query ) {
		parse_str( (string) $query, $query_parts );
		foreach ( array( 'p', 'page_id', 'page', 'pagename', 'page_name', 'attachement_id' ) as $part ) {
			if ( isset( $query_parts[ $part ] ) && ! ( $part === 'page_id' && ! empty( $query_parts['preview'] ) ) ) {
				unset( $query_parts[ $part ] );
			}
		}

		return http_build_query( $query_parts );
	}

	private function get_language_from_url( $return_default_language_if_missing = false ) {
		$query_string_argument = '';
		$language              = '';
		if ( $return_default_language_if_missing ) {
			$language = $this->sitepress->get_current_language();
		}
		if ( array_key_exists( 'wpml_lang', $_GET ) ) {
			$query_string_argument = 'wpml_lang';
		} elseif ( array_key_exists( 'lang', $_GET ) ) {
			$query_string_argument = 'lang';
		}
		if ( '' !== $query_string_argument ) {
			$language = filter_var( $_GET[ $query_string_argument ], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		return $language;
	}

	/**
	 * @param string            $link
	 * @param int               $post
	 * @param WPML_Post_Element $post_element
	 *
	 * @return bool|false|mixed|string
	 */
	public function get_translated_permalink( $link, $post, $post_element ) {
		$code             = $this->get_permalink_filter_lang( $post );
		$post_id          = $post_element->get_element_id();
		$current_language = $this->sitepress->get_current_language();
		if ( ! is_admin()
		     && $this->sitepress->get_setting( 'auto_adjust_ids' )
		     && $post_element->get_language_code() !== $this->sitepress->get_current_language()
		     && ( $post_id = $this->post_translation->element_id_in( $post_id, $current_language ) )
		) {
			$link = get_permalink( $post_id );
		} else {
			$link = $this->url_converter->get_strategy()->convert_url_string( $link, $code );
		}
		if ( $this->sitepress->get_wp_api()->is_feed() ) {
			$link = str_replace( '&lang=', '&#038;lang=', $link );
		}

		return $link;
	}

	/**
	 * @param $link
	 * @param $post
	 * @param $post_element
	 *
	 * @return bool|mixed|string
	 */
	public function get_translated_page_link( $link, $post, $post_element ) {
		$code             = $this->get_permalink_filter_lang( $post );
		$post_id          = $post_element->get_element_id();
		$current_language = $this->sitepress->get_current_language();
		if ( ! is_admin()
		     && $this->sitepress->get_setting( 'auto_adjust_ids' )
		     && $post_element->get_language_code() !== $this->sitepress->get_current_language()
		     && ( $post_id = $this->post_translation->element_id_in( $post_id, $current_language ) )
		) {
			$link = get_page_link( $post_id );
		} else {
			$link = $this->url_converter->get_strategy()->convert_url_string( $link, $code );
		}
		if ( $this->sitepress->get_wp_api()->is_feed() ) {
			$link = str_replace( '&lang=', '&#038;lang=', $link );
		}

		return $link;
	}
}