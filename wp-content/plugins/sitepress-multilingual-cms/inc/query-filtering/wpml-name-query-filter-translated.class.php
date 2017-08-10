<?php

/**
 * Class WPML_Name_Query_Filter_Translated
 *
 * @package    wpml-core
 * @subpackage post-translation
 *
 * @since      3.2.3
 */
class WPML_Name_Query_Filter_Translated extends WPML_Name_Query_Filter {

	protected function select_best_match( $pages_with_name ) {
		foreach ( $this->active_languages as $lang_code ) {
			foreach ( $pages_with_name as $p_with_name ) {
				if ( $this->post_translation->get_element_lang_code( (int) $p_with_name ) === $lang_code ) {
					$pid = $p_with_name;
					break;
				}
			}
			if ( isset( $pid ) ) {
				break;
			}
		}

		return isset( $pid ) ? $pid : null;
	}

	/**
	 * Returns a SQL snippet for joining the posts table with icl translations filtered for the post_type
	 * of this class.
	 *
	 * @return string
	 */
	protected function get_from_join_snippet() {

		return " FROM {$this->wpdb->posts} p
	             JOIN {$this->wpdb->prefix}icl_translations t
					ON p.ID = t.element_id
						AND t.element_type = CONCAT('post_', p.post_type ) ";
	}
}