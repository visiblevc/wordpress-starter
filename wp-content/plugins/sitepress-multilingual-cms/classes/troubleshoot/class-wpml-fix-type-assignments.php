<?php

class WPML_Fix_Type_Assignments extends WPML_WPDB_And_SP_User {

	/**
	 * WPML_Fix_Type_Assignments constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( $sitepress ) {
		$wpdb = $sitepress->wpdb();
		parent::__construct( $wpdb, $sitepress );
	}

	/**
	 * Runs various database repair and cleanup actions on icl_translations.
	 *
	 * @return int Number of rows in icl_translations that were fixed
	 */
	public function run() {
		$rows_fixed = $this->fix_broken_duplicate_rows();
		$rows_fixed += $this->fix_missing_original();
		$rows_fixed += $this->fix_wrong_source_language();
		$rows_fixed += $this->fix_broken_taxonomy_assignments();
		$rows_fixed += $this->fix_broken_post_assignments();
		$rows_fixed += $this->fix_broken_type_assignments();
		icl_cache_clear();
		wp_cache_init();

		return $rows_fixed;
	}

	/**
	 * Deletes rows from icl_translations that are duplicated in terms of their
	 * element id and within their meta type ( post,taxonomy,package ...),
	 * with the duplicate actually being of the correct type.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_broken_duplicate_rows() {

		$rows_fixed = $this->wpdb->query( "
			DELETE t
			FROM {$this->wpdb->prefix}icl_translations i
			  JOIN {$this->wpdb->prefix}icl_translations t
			    ON i.element_id = t.element_id
			       AND SUBSTRING_INDEX(i.element_type, '_', 1) =
			           SUBSTRING_INDEX(t.element_type, '_', 1)
			       AND i.element_type != t.element_type
			       AND i.translation_id != t.translation_id
			  JOIN (SELECT
			          CONCAT('post_', p.post_type) AS element_type,
			          p.ID                         AS element_id
			        FROM {$this->wpdb->posts} p
			        UNION ALL
			        SELECT
			          CONCAT('tax_', tt.taxonomy) AS element_type,
			          tt.term_taxonomy_id         AS element_id
			        FROM {$this->wpdb->term_taxonomy} tt) AS data
			    ON data.element_id = i.element_id
			       AND data.element_type = i.element_type" );

		if( 0 < $rows_fixed ) {
			do_action( 'wpml_translation_update', array( 'type' => 'delete', 'rows_affected' => $rows_fixed ) );
		}

		return $rows_fixed;
	}

	/**
	 * Fixes all taxonomy term rows in icl_translations, which have a corrupted
	 * element_type set, different from the one actually set in the term_taxonomy
	 * table.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_broken_taxonomy_assignments() {

		$rows_fixed = $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations t
									JOIN {$this->wpdb->term_taxonomy} tt
										ON tt.term_taxonomy_id = t.element_id
											AND t.element_type LIKE 'tax%'
											AND t.element_type <> CONCAT('tax_', tt.taxonomy)
									SET t.element_type = CONCAT('tax_', tt.taxonomy)" );

		if( 0 < $rows_fixed ) {
			do_action(
				'wpml_translation_update',
				array(
					'context' => 'tax',
					'type' => 'element_type_update',
					'rows_affected' => $rows_fixed
				)
			);
		}

		return $rows_fixed;
	}

	/**
	 * Fixes all post rows in icl_translations, which have a corrupted
	 * element_type set, different from the one actually set in the wp_posts
	 * table.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_broken_post_assignments() {

		$rows_fixed = $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations t
									JOIN {$this->wpdb->posts} p
										ON p.ID = t.element_id
											AND t.element_type LIKE 'post%'
											AND t.element_type <> CONCAT('post_', p.post_type)
									SET t.element_type = CONCAT('post_', p.post_type)" );

		if( 0 < $rows_fixed ) {
			do_action(
				'wpml_translation_update',
				array(
					'context' => 'tax',
					'type' => 'element_type_update',
					'rows_affected' => $rows_fixed
				)
			);
		}

		return $rows_fixed;
	}

	/**
	 * Fixes all instances of a different element_type having been set for
	 * an original element and it's translation, by setting the original's type
	 * on the corrupted translation rows.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_broken_type_assignments() {

		$rows_fixed = $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations t
									JOIN {$this->wpdb->prefix}icl_translations c
										ON c.trid = t.trid
											AND c.language_code != t.language_code
									SET t.element_type = c.element_type
									WHERE c.source_language_code IS NULL
										AND t.source_language_code IS NOT NULL" );

		if( 0 < $rows_fixed ) {
			do_action(
				'wpml_translation_update',
				array(
					'type' => 'element_type_update',
					'rows_affected' => $rows_fixed
				)
			);
		}

		return $rows_fixed;
	}

	/**
	 * Fixes all rows that have an empty string instead of NULL or a source language
	 * equal to its actual language set by setting the source language to NULL.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_wrong_source_language() {

		return $this->wpdb->query( "UPDATE {$this->wpdb->prefix}icl_translations
									SET source_language_code = NULL
									WHERE source_language_code = ''
										OR source_language_code = language_code" );
	}

	/**
	 * Fixes instances of the source element of a trid being missing, by assigning
	 * the oldest element ( determined by the lowest element_id ) as the original
	 * element in a trid.
	 *
	 * @return int number of rows fixed
	 */
	private function fix_missing_original() {
		$broken_elements = $this->wpdb->get_results(
			"	SELECT MIN(iclt.element_id) AS element_id, iclt.trid
				FROM {$this->wpdb->prefix}icl_translations iclt
				LEFT JOIN {$this->wpdb->prefix}icl_translations iclo
					ON iclt.trid = iclo.trid
					AND iclo.source_language_code IS NULL
				WHERE iclo.translation_id IS NULL
				GROUP BY iclt.trid" );
		$rows_affected   = 0;
		foreach ( $broken_elements as $element ) {
			$rows_affected_per_element = $this->wpdb->query(
				$this->wpdb->prepare(
					"UPDATE {$this->wpdb->prefix}icl_translations
					 SET source_language_code = NULL
					 WHERE trid = %d AND element_id = %d",
					$element->trid,
					$element->element_id
				)
			);

			if( 0 < $rows_affected_per_element ) {
				do_action(
					'wpml_translation_update',
					array( 'trid' => $element->trid )
				);
			}

			$rows_affected += $rows_affected_per_element;
		}

		return $rows_affected;
	}
}
