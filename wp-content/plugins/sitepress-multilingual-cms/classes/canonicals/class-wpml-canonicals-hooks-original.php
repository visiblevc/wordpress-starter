<?php

/**
 * Class WPML_Canonicals_Hooks
 */
class WPML_Canonicals_Hooks {

	/** @var SitePress $sitepress */
	private $sitepress;

	/**
	 * WPML_Canonicals_Hooks constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		$urls = $this->sitepress->get_setting( 'urls' );

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $this->sitepress->get_setting( 'language_negotiation_type' )
		     && ! empty( $urls['directory_for_default_language'] )
		) {
			add_action( 'template_redirect', array( $this, 'redirect_pages_from_root_to_default_lang_dir' ) );
		}

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( strtolower( $_SERVER['SERVER_SOFTWARE'] ), 'nginx' ) !== false ) {
			add_action( 'template_redirect', array( $this, 'maybe_fix_nginx_redirection_callback' ) );
		}
	}

	public function redirect_pages_from_root_to_default_lang_dir() {
		if ( is_page() ) {
			$current_path   = wpml_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$canonical_url  = get_permalink( get_queried_object_id() );
			$canonical_path = wpml_parse_url( $canonical_url, PHP_URL_PATH );
			$canonical_path = $this->get_paginated_canonical_path( $canonical_path );

			if ( $current_path !== $canonical_path ) {
				$current_query = wpml_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
				parse_str( $current_query, $current_query_array );
				$canonical_url = add_query_arg( $current_query_array, $canonical_url );
				$this->sitepress->get_wp_api()->wp_safe_redirect( $canonical_url, 301 );
			}
		}
	}

	/**
	 * @param string $canonical_path
	 *
	 * @return string
	 */
	private function get_paginated_canonical_path( $canonical_path ) {
		global $wp_rewrite;

		$paged = get_query_var( 'page' );

		if ( $paged ) {
			$pagination_base = '';

			if ( is_front_page() ) {
				$pagination_base = trailingslashit( $wp_rewrite->pagination_base );
			}

			$canonical_path = trailingslashit( $canonical_path ) . $pagination_base . user_trailingslashit( $paged );
		}

		return $canonical_path;
	}

	/**
	 * @param string $redirect
	 *
	 * @return bool|string
	 */
	public function maybe_fix_nginx_redirection_callback( $redirect ) {
		if ( is_front_page() ) {
			$redirect = false;
		}

		return $redirect;
	}

}