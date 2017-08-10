<?php

abstract class WPML_Hierarchy_Sync extends WPML_WPDB_User{

	protected $original_elements_table_alias            = 'org';
	protected $translated_elements_table_alias          = 'tra';
	protected $original_elements_language_table_alias   = 'iclo';
	protected $translated_elements_language_table_alias = 'iclt';
	protected $correct_parent_table_alias               = 'corr';
	protected $correct_parent_language_table_alias      = 'iclc';
	protected $original_parent_table_alias              = 'parents';
	protected $original_parent_language_table_alias     = 'parent_lang';
	protected $element_id_column;
	protected $parent_element_id_column;
	protected $parent_id_column;
	protected $element_type_column;
	protected $element_type_prefix;
	protected $elements_table;
	protected $lang_info_table;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		$this->lang_info_table = $wpdb->prefix . 'icl_translations';
	}

	public function get_unsynced_elements( $element_types, $ref_lang_code = false ) {
		$element_types = (array) $element_types;
		$results       = array();
		if ( $element_types ) {
			$results_sql_parts = array();

			$results_sql_parts['source_element_table']           = $this->get_source_element_table();
			$results_sql_parts['source_element_join']            = $this->get_source_element_join();
			$results_sql_parts['join_translation_language_data'] = $this->get_join_translation_language_data( $ref_lang_code );
			$results_sql_parts['translated_element_join']        = $this->get_translated_element_join();
			$results_sql_parts['original_parent_join']           = $this->get_original_parent_join();
			$results_sql_parts['original_parent_language_join']  = $this->get_original_parent_language_join();
			$results_sql_parts['correct_parent_language_join']   = $this->get_correct_parent_language_join();
			$results_sql_parts['correct_parent_element_join']    = $this->get_correct_parent_element_join();
			$results_sql_parts['where_statement']                = $this->get_where_statement( $element_types,
			                                                                                   $ref_lang_code );

			$results_sql = $this->get_select_statement();
			$results_sql .= " FROM ";
			$results_sql .= implode( ' ', $results_sql_parts );
			$results = $this->wpdb->get_results( $results_sql );
		}

		return $results;
	}

	public function sync_element_hierarchy( $element_types, $ref_lang_code = false ) {
		$unsynced = $this->get_unsynced_elements( $element_types, $ref_lang_code );

		foreach ( $unsynced as $row ) {
			$this->update_hierarchy_for_element( $row );
		}
	}

	private final function update_hierarchy_for_element( $row ) {
		$update = $this->validate_parent_synchronization( $row );

		if ( $update ) {
			$target_element_id = $row->translated_id;
			$new_parent        = (int) $row->correct_parent;
			$this->wpdb->update( $this->elements_table, array( $this->parent_id_column => $new_parent ), array( $this->element_id_column => $target_element_id ) );
		}
	}

	private function validate_parent_synchronization( $row ) {
		$is_valid     = false;
		$is_for_posts = ( $this->elements_table === $this->wpdb->posts );
		if ( ! $is_for_posts ) {
			$is_valid = true;
		}

		if ( $row && $is_for_posts ) {
			global $sitepress;

			$target_element_id = $row->translated_id;
			$target_post                = get_post( $target_element_id );
			if ( $target_post ) {
				$parent_must_empty = false;
				$post_type               = $target_post->post_type;
				$element_type            = 'post_' . $post_type;
				$target_element_language = $sitepress->get_element_language_details( $target_element_id, $element_type );
				$original_element_id     = $sitepress->get_original_element_id( $target_element_id, $element_type );
				if ( $original_element_id ) {
					$parent_has_translation_in_target_language = false;

					$original_element        = get_post( $original_element_id );
					$original_post_parent_id = $original_element->post_parent;
					if ( $original_post_parent_id ) {
						$original_post_parent_trid         = $sitepress->get_element_trid( $original_post_parent_id, $element_type );
						$original_post_parent_translations = $sitepress->get_element_translations( $original_post_parent_trid, $element_type );
						foreach ( $original_post_parent_translations as $original_post_parent_translation ) {
							if ( $original_post_parent_translation->language_code == $target_element_language->language_code ) {
								$parent_has_translation_in_target_language = true;
								break;
							}
						}
					} else {
						$parent_must_empty = true;
					}
					/**
					 * Check if the parent of the original post has a translation in the language of the target post or if the parent must be set to 0
					 */
					$is_valid = $parent_has_translation_in_target_language || $parent_must_empty;
				}
			}
		}

		return $is_valid;
	}

	private final function get_source_element_join() {

		return "JOIN {$this->lang_info_table} {$this->original_elements_language_table_alias}
					ON {$this->original_elements_table_alias}.{$this->element_id_column}
						= {$this->original_elements_language_table_alias}.element_id
	                    AND {$this->original_elements_language_table_alias}.element_type
	                        = CONCAT('{$this->element_type_prefix}', {$this->original_elements_table_alias}.{$this->element_type_column})";
	}

	private final function get_translated_element_join() {

		return "JOIN {$this->elements_table} {$this->translated_elements_table_alias}
					ON {$this->translated_elements_table_alias}.{$this->element_id_column}
					= {$this->translated_elements_language_table_alias}.element_id ";
	}

	private final function get_source_element_table() {

		return " {$this->elements_table} {$this->original_elements_table_alias} ";
	}

	private function get_join_translation_language_data($ref_language_code) {

		$res = " JOIN {$this->lang_info_table} {$this->translated_elements_language_table_alias}
	               ON {$this->translated_elements_language_table_alias}.trid
	                 = {$this->original_elements_language_table_alias}.trid ";
		if ( (bool) $ref_language_code === true ) {
			$res .= "AND {$this->translated_elements_language_table_alias}.language_code
						!= {$this->original_elements_language_table_alias}.language_code ";
		} else {
			$res .= " AND {$this->translated_elements_language_table_alias}.source_language_code
                         = {$this->original_elements_language_table_alias}.language_code ";
		}

		return $res;
	}

	private function get_select_statement() {

		return " SELECT {$this->translated_elements_table_alias}.{$this->element_id_column} AS translated_id
						 , IFNULL({$this->correct_parent_table_alias}.{$this->parent_element_id_column}, 0) AS correct_parent ";
	}

	private final function get_original_parent_join() {

		return " LEFT JOIN {$this->elements_table} {$this->original_parent_table_alias}
	                ON {$this->original_parent_table_alias}.{$this->parent_element_id_column}
	                    = {$this->original_elements_table_alias}.{$this->parent_id_column} ";
	}

	private final function get_original_parent_language_join() {

		return " LEFT JOIN {$this->lang_info_table} {$this->original_parent_language_table_alias}
	               ON {$this->original_parent_table_alias}.{$this->element_id_column}
	                = {$this->original_parent_language_table_alias}.element_id
	                 AND {$this->original_parent_language_table_alias}.element_type
	                     = CONCAT('{$this->element_type_prefix}', {$this->original_parent_table_alias}.{$this->element_type_column}) ";
	}

	private final function get_correct_parent_language_join() {

		return " LEFT JOIN {$this->lang_info_table} {$this->correct_parent_language_table_alias}
	              ON {$this->correct_parent_language_table_alias}.language_code
	                    = {$this->translated_elements_language_table_alias}.language_code
	                AND {$this->original_parent_language_table_alias}.trid
	                    = {$this->correct_parent_language_table_alias}.trid ";
	}

	private final function get_correct_parent_element_join() {

		return " LEFT JOIN {$this->elements_table} {$this->correct_parent_table_alias}
	              ON {$this->correct_parent_table_alias}.{$this->element_id_column}
	                = {$this->correct_parent_language_table_alias}.element_id ";
	}

	private final function get_where_statement( $element_types, $ref_lang_code ) {

		$filter_originals_snippet = $ref_lang_code
			? $this->wpdb->prepare( " AND {$this->original_elements_language_table_alias}.language_code = %s ", $ref_lang_code)
			:  " AND {$this->translated_elements_language_table_alias}.source_language_code IS NOT NULL ";

		return " WHERE {$this->original_elements_table_alias}.{$this->element_type_column}
					IN (" . wpml_prepare_in( $element_types ) . ")
                    AND IFNULL({$this->correct_parent_table_alias}.{$this->parent_element_id_column}, 0)
                        != {$this->translated_elements_table_alias}.{$this->parent_id_column} " . $filter_originals_snippet;
	}
}