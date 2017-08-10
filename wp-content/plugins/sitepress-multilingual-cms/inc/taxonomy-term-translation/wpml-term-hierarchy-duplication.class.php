<?php

/**
 * Class WPML_Term_Hierarchy_Duplication
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 *
 */
class WPML_Term_Hierarchy_Duplication extends WPML_WPDB_And_SP_User {

	public function duplicates_require_sync( $post_ids, $duplicates_only = true ) {
		$taxonomies = $this->sitepress->get_translatable_taxonomies( true );
		foreach ( $taxonomies as $key => $tax ) {
			if ( ! is_taxonomy_hierarchical( $tax ) ) {
				unset( $taxonomies[ $key ] );
			}
		}
		if ( (bool) $post_ids === true ) {
			$need_sync_taxonomies = $duplicates_only === true
				? $this->get_need_sync_new_dupl( $post_ids, $taxonomies )
				: $this->get_need_sync_all_terms( $taxonomies, $post_ids );
		} else {
			$need_sync_taxonomies = array();
		}

		return array_values( array_unique( $need_sync_taxonomies ) );
	}

	private function get_need_sync_new_dupl( $duplicated_ids, $taxonomies ) {
		$new_terms           = $this->get_new_terms_just_duplicated( $duplicated_ids, $taxonomies );
		$affected_taxonomies = array();
		foreach ( $new_terms as $term ) {
			$affected_taxonomies[] = $term->taxonomy;
		}
		$affected_taxonomies   = array_unique( $affected_taxonomies );
		$hierarchy_sync_helper = wpml_get_hierarchy_sync_helper( 'term' );

		$unsynced_terms = $hierarchy_sync_helper->get_unsynced_elements(
			$affected_taxonomies,
			$this->sitepress->get_default_language()
		);

		foreach ( $new_terms as $key => $new_term ) {
			$sync = true;
			foreach ( $unsynced_terms as $term_unsynced ) {
				if ( $term_unsynced->translated_id == $new_term->term_taxonomy_id ) {
					$sync = false;
					break;
				}
			}
			if ( $sync === true ) {
				unset( $new_terms[ $key ] );
			}
		}
		$need_sync_taxonomies = array();
		foreach ( $new_terms as $term ) {
			$need_sync_taxonomies[] = $term->taxonomy;
		}

		return $need_sync_taxonomies;
	}

	private function get_need_sync_all_terms( $translated_taxonomies, $post_ids ) {
		$hierarchy_sync_helper = wpml_get_hierarchy_sync_helper( 'term' );
		$post_ids_in           = wpml_prepare_in( (array) $post_ids, '%d' );
		$taxonomies_in         = wpml_prepare_in( $translated_taxonomies );

		$this->wpdb->get_col( "SELECT DISTINCT tt.taxonomy
						 FROM {$this->wpdb->term_taxonomy} tt
						 JOIN {$this->wpdb->term_relationships} tr
						  ON tt.term_taxonomy_id = tr.term_taxonomy_id
						 WHERE tr.object_id IN ({$post_ids_in}) AND tt.taxonomy IN ({$taxonomies_in})" );

		foreach ( $translated_taxonomies as $key => $tax ) {
			$unsynced_terms = $hierarchy_sync_helper->get_unsynced_elements(
				$tax,
				$this->sitepress->get_default_language()
			);
			if ( (bool) $unsynced_terms === false ) {
				unset( $translated_taxonomies[ $key ] );
			}
		}

		return $translated_taxonomies;
	}

	private function get_new_terms_just_duplicated( $duplicate_ids, $taxonomies ) {
		if ( (bool) $duplicate_ids === false || (bool) $taxonomies === false ) {
			return array();
		}

		$duplicate_ids_in = wpml_prepare_in( $duplicate_ids, '%d' );
		$taxonomies_in    = wpml_prepare_in( $taxonomies );
		$terms            = $this->wpdb->get_results(
			"SELECT tt.term_taxonomy_id, tt.taxonomy
			 FROM {$this->wpdb->term_taxonomy} tt
			 JOIN {$this->wpdb->term_relationships} tr
				ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 JOIN {$this->wpdb->postmeta} pm
			    ON pm.post_id = tr.object_id
			 JOIN {$this->wpdb->terms} t_duplicate
			    ON t_duplicate.term_id = tt.term_id
			 JOIN  {$this->wpdb->terms} t_original
			    ON t_original.name = t_duplicate.name
			 JOIN {$this->wpdb->term_taxonomy} tt_master
			    ON tt_master.term_id = t_original.term_id
			 JOIN {$this->wpdb->term_relationships} tr_master
			    ON tt_master.term_taxonomy_id = tr_master.term_taxonomy_id
			 LEFT JOIN {$this->wpdb->term_relationships} tr_other
			    ON tt.term_taxonomy_id = tr_other.term_taxonomy_id
			      AND tr_other.object_id != tr.object_id
			      AND tr_other.object_id NOT IN ({$duplicate_ids_in})
		      LEFT JOIN {$this->wpdb->postmeta} pm_other
		        ON pm_other.post_id = tr_other.object_id
		          AND NOT (pm_other.meta_key = '_icl_lang_duplicate_of'
		                    AND  pm_other.meta_value IN ({$duplicate_ids_in}))
		     WHERE pm.meta_key = '_icl_lang_duplicate_of'
		        AND tr_other.object_id IS NULL
		        AND pm_other.post_id IS NULL
		        AND pm.meta_value IN ({$duplicate_ids_in})
		        AND tr_master.object_id IN ({$duplicate_ids_in})
		        AND tt.taxonomy IN ({$taxonomies_in})"
		);

		return $terms;
	}
}