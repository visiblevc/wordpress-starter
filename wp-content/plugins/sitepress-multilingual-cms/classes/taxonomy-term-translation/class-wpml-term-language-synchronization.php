<?php

/**
 * @since      3.1.8.4
 *
 * Class WPML_Term_Language_Synchronization
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Term_Language_Synchronization extends WPML_WPDB_And_SP_User{

	/** @var string $taxonomy */
	private $taxonomy;
	/** @var array $data */
	private $data;
	/** @var array $missing_terms */
	private $missing_terms = array();
	/** @var WPML_Terms_Translations $term_utils */
	private $term_utils;

	/**
	 * @param SitePress               $sitepress
	 * @param WPML_Terms_Translations $term_utils
	 * @param string                  $taxonomy
	 */
	public function __construct( &$sitepress, &$term_utils, $taxonomy ) {
		$wpdb = $sitepress->wpdb();
		parent::__construct( $wpdb, $sitepress );
		$this->term_utils = $term_utils;
		$this->taxonomy   = $taxonomy;
		$this->data       = $this->set_affected_ids();
		$this->prepare_missing_terms_data();
	}

	/**
	 * Wrapper for the two database actions performed by this object.
	 * First those terms are created that lack translations and then following that,
	 * the assignment of posts and languages is corrected, taking advantage of the newly created terms
	 * and resulting in a state of no conflicts in the form of a post language being different from
	 * an assigned terms language, remaining.
	 */
	public function set_translated() {
		$this->prepare_missing_originals();
		$this->reassign_terms();
		$this->set_initial_term_language();
	}

	/**
	 * Helper function for the installation process,
	 * finds all terms missing an entry in icl_translations and then
	 * assigns them the default language.
	 */
	public function set_initial_term_language() {
		$element_ids      = $this->wpdb->get_col( $this->wpdb->prepare( "
													SELECT tt.term_taxonomy_id
													FROM {$this->wpdb->term_taxonomy} AS tt
													LEFT JOIN {$this->wpdb->prefix}icl_translations AS i
														ON tt.term_taxonomy_id = i.element_id
															AND CONCAT('tax_', tt.taxonomy) = i.element_type
													WHERE taxonomy = %s
														AND i.element_id IS NULL",
		                                                                $this->taxonomy ) );
		$default_language = $this->sitepress->get_default_language();
		foreach ( $element_ids as $id ) {
			$this->sitepress->set_element_language_details( $id, 'tax_' . $this->taxonomy, false, $default_language );
		}
	}

	/**
	 * Performs an SQL query assigning all terms to their correct language equivalent if it exists.
	 * This should only be run after the previous functionality in here has finished.
	 * Afterwards the term counts are recalculated globally, since term assignments bypassing the WordPress Core,
	 * will not trigger any sort of update on those.
	 */
	private function reassign_terms() {
		$update_query = $this->wpdb->prepare(
			"UPDATE {$this->wpdb->term_relationships} AS o,
					{$this->wpdb->prefix}icl_translations AS ic,
					{$this->wpdb->prefix}icl_translations AS iw,
					{$this->wpdb->prefix}icl_translations AS ip,
					{$this->wpdb->posts} AS p
						SET o.term_taxonomy_id = ic.element_id
						WHERE ic.trid = iw.trid
							AND ic.element_type = iw.element_type
							AND iw.element_id = o.term_taxonomy_id
							AND ic.language_code = ip.language_code
							AND ip.element_type = CONCAT('post_', p.post_type)
							AND ip.element_id = p.ID
							AND o.object_id = p.ID
							AND o.term_taxonomy_id != ic.element_id
							AND iw.element_type = %s",
			'tax_' . $this->taxonomy
		);

		$rows_affected = $this->wpdb->query( $update_query );
		if ( $rows_affected ) {
			$term_ids = $this->wpdb->get_col(
				$this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE taxonomy = %s",
				                      $this->taxonomy ) );
			// Do not run the count update on taxonomies that are not actually registered as proper taxonomy objects, e.g. WooCommerce Product Attributes.
			$taxonomy_object = $this->sitepress->get_wp_api()->get_taxonomy( $this->taxonomy );
			if ( $taxonomy_object && isset( $taxonomy_object->object_type ) ) {
				$this->sitepress->get_wp_api()->wp_update_term_count( $term_ids, $this->taxonomy );
			}
		}
	}

	/**
	 * @param object[] $sql_result holding the information retrieved in \self::set_affected_ids
	 *
	 * @return array The associative array to be returned by \self::set_affected_ids
	 */
	private function format_data( $sql_result ) {
		$res = array();
		foreach ( $sql_result as $pair ) {
			$res[ $pair->ttid ] = isset( $res[ $pair->ttid ] )
				? $res[ $pair->ttid ]
				: array( 'tlang'  => array(), 'plangs' => array() );

			if ( $pair->term_lang && $pair->trid ) {
				$res[ $pair->ttid ]['tlang'] = array( 'lang' => $pair->term_lang, 'trid' => $pair->trid );
			}
			if ( $pair->post_lang ) {
				$res[ $pair->ttid ]['plangs'][ $pair->post_id ] = $pair->post_lang;
			}
		}

		return $res;
	}

	/**
	 * Uses the API provided in \WPML_Terms_Translations to create missing term translations.
	 * These arise when a term, previously having been untranslated, is set to be translated
	 * and assigned to posts in more than one language.
	 *
	 * @param $trid        int The trid value for which term translations are missing.
	 * @param $source_lang string The source language of this trid.
	 * @param $langs       array The languages' codes for which term translations are missing.
	 */
	private function prepare_missing_translations(
		$trid,
		$source_lang,
		$langs
	) {
		$existing_translations = $this->sitepress->term_translations()->get_element_translations( false,
			$trid );
		foreach ( $langs as $lang ) {
			if ( ! isset( $existing_translations[ $lang ] ) ) {
				$this->term_utils->create_automatic_translation( array(
					'lang_code'       => $lang,
					'source_language' => $source_lang,
					'trid'            => $trid,
					'taxonomy'        => $this->taxonomy
				) );
			}
		}
	}

	/**
	 * Retrieves all term_ids, and if applicable, their language and assigned to posts,
	 * in an associative array,
	 * which are in the situation of not being assigned to any language or in which a term
	 * is assigned to a post in a language different from its own.
	 *
	 * @return array
	 */
	private function set_affected_ids() {
		$query_for_post_ids = $this->wpdb->prepare( "
				SELECT tl.trid AS trid, tl.ttid AS ttid, tl.tlang AS term_lang, tl.pid AS post_id, pl.plang AS post_lang
				FROM (
					SELECT
					o.object_id AS pid,
					tt.term_taxonomy_id AS ttid,
					i.language_code AS tlang,
					i.trid AS trid
				FROM {$this->wpdb->term_relationships} AS o
				JOIN {$this->wpdb->term_taxonomy} AS tt
					ON o.term_taxonomy_id = tt.term_taxonomy_id
				LEFT JOIN {$this->wpdb->prefix}icl_translations AS i
					ON i.element_id = tt.term_taxonomy_id
						AND i.element_type = CONCAT('tax_', tt.taxonomy)
				WHERE tt.taxonomy = %s) AS tl
				LEFT JOIN
				( SELECT p.ID AS pid, i.language_code AS plang
					FROM {$this->wpdb->posts} AS p
					JOIN {$this->wpdb->prefix}icl_translations AS i
						ON i.element_id = p.ID
							AND i.element_type = CONCAT('post_', p.post_type)
				) AS pl
					ON tl.pid = pl.pid
				",
		                                            $this->taxonomy );
		$ttid_pid_pairs     = $this->wpdb->get_results( $query_for_post_ids );

		return $this->format_data( $ttid_pid_pairs );
	}

	/**
	 * Assigns language information to terms that are to be treated as originals at the time of
	 * their taxonomy being set to translated instead of 'do nothing'.
	 */
	private function prepare_missing_originals() {
		foreach ( $this->missing_terms as $ttid => $missing_lang_data ) {
			if ( ! isset( $this->data[ $ttid ]['tlang']['trid'] ) ) {
				foreach ( $missing_lang_data as $lang => $post_ids ) {
					$this->sitepress->set_element_language_details( $ttid,
						'tax_' . $this->taxonomy, null, $lang );
					$trid = $this->sitepress->term_translations()->get_element_trid( $ttid );
					if ( $trid ) {
						$this->data[ $ttid ]['tlang']['trid'] = $trid;
						$this->data[ $ttid ]['tlang']['lang'] = $lang;
						unset( $this->missing_terms[ $ttid ][ $lang ] );
						break;
					}
				}
			}
			if ( isset( $this->data[ $ttid ]['tlang']['trid'] ) ) {
				$this->prepare_missing_translations( $this->data[ $ttid ]['tlang']['trid'],
					$this->data[ $ttid ]['tlang']['lang'],
					array_keys( $this->missing_terms[ $ttid ] ) );
			}
		}
	}

	/**
	 * Uses the data retrieved from the database and saves information about,
	 * in need of fixing terms to this object.
	 *
	 * @return array
	 */
	private function prepare_missing_terms_data() {
		$default_lang = $this->sitepress->get_default_language();
		$data         = $this->data;
		$missing      = array();
		foreach ( $data as $ttid => $data_item ) {
			if ( empty( $data_item['plangs'] ) && empty( $data_item['tlang'] ) ) {
				$missing[ $ttid ][ $default_lang ] = - 1;
			} else {
				$affected_languages = array_diff( $data_item['plangs'], $data_item['tlang'] );
				if ( ! empty( $affected_languages ) ) {
					foreach ( $data_item['plangs'] as $post_id => $lang ) {
						if ( ! isset( $missing[ $ttid ][ $lang ] ) ) {
							$missing[ $ttid ][ $lang ] = array( $post_id );
						} else {
							$missing[ $ttid ][ $lang ][] = $post_id;
						}
					}
				}
			}
		}
		$this->missing_terms = $missing;
	}
}