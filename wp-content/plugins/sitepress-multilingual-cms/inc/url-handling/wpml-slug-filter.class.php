<?php

/**
 * Class WPML_Slug_Filter
 *
 * @package    wpml-core
 * @subpackage url-handling
 *
 */
class WPML_Slug_Filter extends WPML_Full_PT_API {

	/**
	 * @param wpdb                  $wpdb
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 */
	public function __construct( &$wpdb, &$sitepress, &$post_translations ) {
		parent::__construct( $wpdb, $sitepress, $post_translations );
		add_filter( 'pre_term_slug', array( $this, 'pre_term_slug_filter' ), 10, 2 );
		add_filter( 'wp_unique_post_slug', array( $this, 'wp_unique_post_slug' ), 100, 6 );
	}

	/**
	 * @param String $slug
	 * @param String $taxonomy
	 * Filters slug input, so to ensure uniqueness of term slugs.
	 *
	 * @return String Either the original slug or a new slug that has been generated from the original one in order to
	 *                ensure slug uniqueness.
	 */
	public function pre_term_slug_filter( $slug, $taxonomy ) {
		if ( ( isset( $_REQUEST[ 'tag-name' ] ) || isset( $_REQUEST[ 'name' ] ) )
		     && ( ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] === 'add-tag' ) )
		) {
			$lang = $this->lang_term_slug_save ($taxonomy);
			if ( $slug === '' ) {
				if ( isset( $_REQUEST[ 'tag-name' ] ) ) {
					$slug = sanitize_title ( $_REQUEST[ 'tag-name' ] );
				} elseif ( isset( $_REQUEST[ 'name' ] ) ) {
					$slug = sanitize_title ( $_REQUEST[ 'name' ] );
				}
			}
			$slug = $slug !== '' ? WPML_Terms_Translations::term_unique_slug ( $slug, $taxonomy, $lang ) : $slug;
		}

		return $slug;
	}

	private function lang_term_slug_save( $taxonomy ) {
		$active_lang_codes = array_keys( $this->sitepress->get_active_languages() );
		if ( ! in_array(
				( $lang = (string) filter_input( INPUT_POST, 'icl_tax_' . $taxonomy . '_language' ) ),
				$active_lang_codes,
				true )
		     && ! in_array( ( $lang = (string) filter_input( INPUT_POST, 'language' ) ), $active_lang_codes, true )
		) {
			$lang = $this->sitepress->get_current_language();
		}
		$lang = 'all' === $lang ? $this->sitepress->get_default_language() : $lang;

		return $lang;
	}

	function wp_unique_post_slug( $slug_suggested, $post_id, $post_status, $post_type, $post_parent, $slug ) {
		if ( $post_status !== 'auto-draft' && $this->sitepress->is_translated_post_type( $post_type ) ) {
			$post_language       = $post_id
				? $this->post_translations->get_element_lang_code( $post_id )
				: $this->sitepress->get_current_language();
			$post_language       = $post_language
				? $post_language : $this->sitepress->post_translations()->get_save_post_lang( $post_id, $this->sitepress );
			$parent              = is_post_type_hierarchical( $post_type ) ? (int) $post_parent : false;
			$slug_suggested_wpml = $this->find_unique_slug_post( $post_id, $post_type, $post_language, $parent, $slug );
		}

		return isset( $slug_suggested_wpml ) ? $slug_suggested_wpml : $slug_suggested;
	}

	private function post_slug_exists( $post_id, $post_language, $slug, $post_type, $parent = false ) {
		$parent_snippet           = $parent === false ? "" : $this->wpdb->prepare ( " AND p.post_parent = %d ", $parent );
		$post_name_check_sql	  = "	SELECT p.post_name
										FROM {$this->wpdb->posts} p
										JOIN {$this->wpdb->prefix}icl_translations t
											ON p.ID = t.element_id
												AND t.element_type = CONCAT('post_', p.post_type)
										WHERE p.post_name = %s
											AND p.ID != %d
											AND t.language_code = %s
											AND p.post_type = %s
											{$parent_snippet}
										LIMIT 1";
		$post_name_check_prepared = $this->wpdb->prepare ( $post_name_check_sql, $slug, $post_id, $post_language, $post_type );
		$post_name_check          = $this->wpdb->get_var ( $post_name_check_prepared );

		return (bool) $post_name_check;
	}

	private function find_unique_slug_post( $post_id, $post_type, $post_language, $post_parent, $slug ) {
		global $wp_rewrite;

		$feeds = is_array ( $wp_rewrite->feeds ) ? $wp_rewrite->feeds : array();
		if ( $this->post_slug_exists ( $post_id, $post_language, $slug, $post_type, $post_parent )
		     || in_array ( $slug, $feeds, true )
		     || ($post_parent !== false && preg_match( "@^($wp_rewrite->pagination_base)?\d+$@", $slug ))
		     || apply_filters ( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type )
		) {
			$suffix = 2;
			do {
				$alt_post_name = substr ( $slug, 0, 200 - ( strlen ( $suffix ) + 1 ) ) . "-$suffix";
				$suffix ++;
			} while ( $this->post_slug_exists ( $post_id, $post_language, $alt_post_name, $post_type, $post_parent ) );
			$slug = $alt_post_name;
		}

		return $slug;
	}
}
