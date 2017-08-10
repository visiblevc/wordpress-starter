<?php

class WPML_Translate_Link_Targets_In_Posts_Global extends WPML_Translate_Link_Targets_In_Posts {

	protected function get_contents_with_links_needing_fix( $start_id = 0, $count = 0 ) {
		$this->content_to_fix = $this->wpdb->get_results( $this->get_sql( $start_id, $count, false) );
	}

	public function get_number_to_be_fixed( $start_id = 0 ) {
		return $this->wpdb->get_var( $this->get_sql( $start_id, 0, true ) );
	}

	protected function get_sql( $start_id , $count, $return_count_only ) {

		$limit = '';
		if ( $count > 0 ) {
			$limit = " LIMIT " . $count;
		}

		if ( $return_count_only ) {
			$sql = "SELECT COUNT(t.element_id)";
		} else {
			$sql = "SELECT t.element_id, t.language_code";
		}
		$sql = $this->wpdb->prepare( $sql .
			" FROM {$this->wpdb->prefix}icl_translations AS t
			INNER JOIN {$this->wpdb->prefix}icl_translation_status AS ts
			ON t.translation_id = ts.translation_id
			WHERE t.element_id IS NOT NULL
			AND ts.links_fixed = 0
			AND t.element_id >= %d
			AND t.element_type LIKE 'post_%%'
			ORDER BY t.element_id ASC" . $limit,
			$start_id
		);

		return $sql;
	}


}