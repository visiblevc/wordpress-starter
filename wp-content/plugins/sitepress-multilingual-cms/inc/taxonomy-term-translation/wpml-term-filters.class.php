<?php

/**
 * Class WPML_Term_Filters
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 *
 */
class WPML_Term_Filters extends WPML_WPDB_And_SP_User {

	public function init() {
		add_action( 'registered_taxonomy', array( $this, 'init' ), 10, 0 );
		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			if ( is_taxonomy_translated( $taxonomy ) ) {
				add_filter( "pre_option_{$taxonomy}_children", array( $this, 'pre_option_tax_children' ), 10, 0 );
				add_action( "create_{$taxonomy}", array( $this, 'update_tax_children_option' ), 10, 0 );
				add_action( "edit_{$taxonomy}", array( $this, 'update_tax_children_option' ), 10, 0 );
			}
		}
	}

	public function update_tax_children_option( $taxonomy_input = false ) {
		global $wpml_language_resolution, $wp_taxonomies;

		$language_codes   = $wpml_language_resolution->get_active_language_codes();
		$language_codes[] = 'all';
		$taxonomy         = str_replace( array( 'create_', 'edit_' ), '', current_action() );
		$taxonomy         = isset( $wp_taxonomies[ $taxonomy ] ) ? $taxonomy : $taxonomy_input;
		foreach ( $language_codes as $lang ) {
			$tax_children = $this->get_tax_hier_array( $taxonomy, $lang );
			$option_key   = "{$taxonomy}_children_{$lang}";
			update_option( $option_key, $tax_children );
		}
	}

	public function pre_option_tax_children() {
		$taxonomy     = str_replace( array( 'pre_option_', '_children' ), '', current_filter() );
		$lang         = $this->sitepress->get_current_language();
		$option_key   = "{$taxonomy}_children_{$lang}";
		$tax_children = get_option( $option_key, false );
		if ( $tax_children === false ) {
			$tax_children = $this->get_tax_hier_array( $taxonomy, $lang );
			update_option( $option_key, $tax_children );
		}

		return ! empty( $tax_children ) ? $tax_children : false;
	}

	/**
	 * @param string $taxonomy
	 * @param string $lang_code
	 *
	 * @return array
	 */
	public function get_tax_hier_array( $taxonomy, $lang_code ) {
		$hierarchy = array();

		if ( $lang_code != 'all' ) {
			$terms = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT term_id, parent
					 FROM {$this->wpdb->term_taxonomy} tt
					 JOIN {$this->wpdb->prefix}icl_translations iclt
					  ON tt.term_taxonomy_id = iclt.element_id
					 WHERE tt.parent > 0
					  AND tt.taxonomy = %s
					  AND iclt.language_code = %s
					  AND iclt.element_type = %s
					 ORDER BY term_id",
					$taxonomy,
					$lang_code,
					'tax_' . $taxonomy
				)
			);
		} else {
			$terms = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT term_id, parent
					 FROM {$this->wpdb->term_taxonomy} tt
					 WHERE tt.parent > 0
					  AND tt.taxonomy = %s
					 ORDER BY term_id",
					$taxonomy,
					$lang_code
				)
			);
		}
		foreach ( $terms as $term ) {
			if ( $term->parent > 0 ) {
				$hierarchy[ $term->parent ]   = isset( $hierarchy[ $term->parent ] )
					? $hierarchy[ $term->parent ] : array();
				$hierarchy[ $term->parent ][] = $term->term_id;
			}
		}

		return $hierarchy;
	}
}