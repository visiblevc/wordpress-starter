<?php

class WPML_Resolve_Object_Url_Helper {
	const CACHE_GROUP = 'resolve_object_url';

	/**
	 * @var bool
	 */
	protected $lock = false;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WP_Query
	 */
	private $wp_query;

	/**
	 * @var WPML_Term_Translation
	 */
	private $wpml_term_translations;

	/**
	 * @var WPML_Post_Translation
	 */
	private $wpml_post_translations;

	/**
	 * @param SitePress $sitepress
	 * @param WP_Query $wp_query
	 * @param WPML_Term_Translation $wpml_term_translations
	 * @param WPML_Post_Translation $wpml_post_translations
	 */
	public function __construct(
		SitePress $sitepress = null,
		WP_Query $wp_query = null,
		WPML_Term_Translation $wpml_term_translations = null,
		WPML_Post_Translation $wpml_post_translations = null
	) {
		$this->sitepress              = $sitepress;
		$this->wp_query               = $wp_query;
		$this->wpml_term_translations = $wpml_term_translations;
		$this->wpml_post_translations = $wpml_post_translations;
	}


	/**
	 * Try to parse the URL to find a related post or term
	 *
	 * @param string $url
	 * @param string $lang_code
	 *
	 * @return string|bool
	 */
	public function resolve_object_url( $url, $lang_code ) {
		if ( $this->lock ) {
			return false;
		}

		$this->lock = true;
		$new_url    = false;

		$translations = $this->cached_retrieve_translations( $url );
		if ( $translations && isset( $translations[ $lang_code ]->element_type ) ) {

			$current_lang = $this->sitepress->get_current_language();
			$this->sitepress->switch_lang( $lang_code );

			$element = explode( '_', $translations[ $lang_code ]->element_type );
			$type = array_shift( $element );
			$subtype = implode( '_', $element );
			switch ( $type ) {
				case 'post':
					$new_url = get_permalink( $translations[ $lang_code ]->element_id );
					break;
				case 'tax':
					$term = get_term( $translations[ $lang_code ]->element_id, $subtype );
					$new_url = get_term_link( $term );
					break;
			}

			$this->sitepress->switch_lang( $current_lang );
		}

		$this->lock = false;
		return $new_url;
	}

	private function cached_retrieve_translations( $url ) {
		$cache_key      = md5( $url );
		$cache_found    = false;
		$cache          = new WPML_WP_Cache( self::CACHE_GROUP );
		$translations   = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found && is_object( $this->wp_query ) ) {
			$translations = $this->retrieve_translations( );
			$cache->set( $cache_key, $translations );
		}

		return $translations;
	}

	private function retrieve_translations() {
		$this->sitepress->set_wp_query(); // Make sure $sitepress->wp_query is set
		$_wp_query_back = clone $this->wp_query;
		$wp_query = $this->sitepress->get_wp_query();
		$wp_query = is_object( $wp_query ) ? clone $wp_query : clone $_wp_query_back;

		$languages_helper = new WPML_Languages( $this->wpml_term_translations, $this->sitepress, $this->wpml_post_translations );
		list( $translations) = $languages_helper->get_ls_translations( $wp_query, $_wp_query_back, $this->sitepress->get_wp_query() );

		return $translations;
	}
}
