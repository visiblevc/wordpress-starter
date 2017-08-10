<?php

/**
 * @since      3.2
 *
 * Class WPML_Term_Translation
 *
 * Provides APIs for translating taxonomy terms
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Term_Translation extends WPML_Element_Translation {

	private $ttids;
	private $term_ids;

	/**
	 * @param int $term_id
	 *
	 * @return null|string
	 */
	public function lang_code_by_termid( $term_id ) {

		return $this->get_element_lang_code( $this->adjust_ttid_for_term_id( $term_id ) );
	}

	/**
	 * Converts term_id into term_taxonomy_id
	 *
	 * @param int $term_id
	 *
	 * @return int
	 */
	public function adjust_ttid_for_term_id( $term_id ) {
		$this->maybe_warm_term_id_cache();

		return $term_id && isset( $this->ttids[ $term_id ] ) ? end( $this->ttids[ $term_id ] ) : $term_id;
	}

	/**
	 * Converts term_taxonomy_id into term_id
	 *
	 * @param int $ttid term_taxonomy_id
	 *
	 * @return int
	 */
	public function adjust_term_id_for_ttid( $ttid ) {
		$this->maybe_warm_term_id_cache();

		return $ttid && isset( $this->term_ids[ $ttid ] ) ? $this->term_ids[ $ttid ] : $ttid;
	}

	public function reload() {
		parent::reload();
		$this->term_ids = null;
		$this->ttids    = null;
	}

	/**
	 * @param int        $term_id
	 * @param string     $lang_code
	 * @param bool|false $original_fallback if true will return the the input term_id in case no translation is found
	 *
	 * @return null|int
	 */
	public function term_id_in( $term_id, $lang_code, $original_fallback = false ) {

		return $this->adjust_term_id_for_ttid(
			$this->element_id_in( $this->adjust_ttid_for_term_id( $term_id ), $lang_code, $original_fallback )
		);
	}

	/**
	 * Returns the trid for a given term_id and taxonomy or null on failure
	 *
	 * @param int    $term_id  term_id of a term
	 * @param string $taxonomy taxonomy of the term
	 *
	 * @return null|int
	 */
	public function trid_from_tax_and_id( $term_id, $taxonomy ) {
		$this->maybe_warm_term_id_cache();
		$ttid = $term_id && isset( $this->ttids[ $term_id ][ $taxonomy ] )
				? $this->ttids[ $term_id ][ $taxonomy ] : $term_id;

		return $this->get_element_trid( $ttid );
	}

	/**
	 * Returns all post types to which a taxonomy is linked.
	 *
	 * @param $taxonomy string
	 *
	 * @return array
	 *
	 * @since 3.2.3
	 */
	public function get_taxonomy_post_types( $taxonomy ) {
		global $wp_taxonomies;

		$post_types = array();
		if ( isset( $wp_taxonomies[ $taxonomy ] ) && isset( $wp_taxonomies[ $taxonomy ]->object_type ) ) {
			$post_types = $wp_taxonomies[ $taxonomy ]->object_type;
		}

		return $post_types;
	}

	protected function get_element_join() {

		return "FROM {$this->wpdb->prefix}icl_translations t
				JOIN {$this->wpdb->term_taxonomy} tax
					ON t.element_id = tax.term_taxonomy_id
						AND t.element_type = CONCAT('tax_', tax.taxonomy)";
	}

	protected function get_type_prefix() {
		return 'tax_';
	}

	private function maybe_warm_term_id_cache() {

		if ( ! isset( $this->ttids ) || ! isset( $this->term_ids ) ) {
			$data           = $this->wpdb->get_results( "	SELECT t.element_id, tax.term_id, tax.taxonomy
													 " . $this->get_element_join() . "
													 JOIN {$this->wpdb->terms} terms
													  ON terms.term_id = tax.term_id
													 WHERE tax.term_id != tax.term_taxonomy_id",
			                                      ARRAY_A );
			$this->term_ids = array();
			$this->ttids    = array();
			foreach ( $data as $row ) {
				$this->ttids[ $row['term_id'] ]                     = isset( $this->ttids[ $row['term_id'] ] )
					? $this->ttids[ $row['term_id'] ] : array();
				$this->ttids[ $row['term_id'] ][ $row['taxonomy'] ] = $row['element_id'];
				$this->term_ids[ $row['element_id'] ]               = $row['term_id'];
			}
		}
	}
}
