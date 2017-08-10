<?php

class WPML_Tax_Permalink_Filters {
	/**
	 * @var WPML_Term_Translation
	 */
	private $wpml_term_translations;

	/**
	 * @var WPML_URL_Converter
	 */
	private $url_converter;

	/**
	 * @param WPML_URL_Converter $url_converter
	 * @param WPML_Term_Translation $wpml_term_translations
	 */
	public function __construct( WPML_URL_Converter $url_converter, WPML_Term_Translation $wpml_term_translations = null ) {
		if ( ! $wpml_term_translations ) {
			global $wpml_term_translations;
		}
		$this->wpml_term_translations = $wpml_term_translations;

		$this->url_converter          = $url_converter;
	}

	public function add_hooks() {
		add_filter( 'term_link', array( $this, 'cached_filter_tax_permalink' ), 1, 3 );
	}

	public function cached_filter_tax_permalink( $permalink, $tag, $taxonomy ) {
		$tag                  = is_object( $tag ) ? $tag : get_term( $tag, $taxonomy );
		$tag_id               = $tag ? $tag->term_taxonomy_id : 0;
		$cached_permalink_key = $tag_id . '.' . $taxonomy;
		$cache_group          = 'icl_tax_permalink_filter';
		$found                = false;
		$cache                = new WPML_WP_Cache( $cache_group );
		$cached_permalink     = $cache->get( $cached_permalink_key, $found );
		if ( $found ) {
			return $cached_permalink;
		}

		$permalink = $this->filter_tax_permalink( $permalink, $tag_id );

		$cache->set( $cached_permalink_key, $permalink );

		return $permalink;
	}

	/**
	 * Filters the permalink pointing at a taxonomy archive to correctly reflect the language of its underlying term
	 *
	 * @param string $permalink url pointing at a term's archive
	 * @param int $tag_id
	 *
	 * @return string
	 */
	public function filter_tax_permalink( $permalink, $tag_id ) {
		$term_language = $tag_id ? $this->wpml_term_translations->get_element_lang_code( $tag_id ) : false;

		if ( (bool) $term_language ) {
			$permalink = $this->url_converter->convert_url( $permalink, $term_language );
		}

		return $permalink;
	}
}
